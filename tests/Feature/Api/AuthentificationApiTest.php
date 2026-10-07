<?php

namespace Tests\Feature\Api;

use App\Models\Personnel;
use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FALaravel\Google2FA;
use Tests\TestCase;

class AuthentificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_utilisateur_peut_se_connecter_et_consulter_son_profil(): void
    {
        $utilisateur = User::factory()->create([
            'name' => 'Personnel Mobile',
            'email' => 'personnel.mobile@efeledu.com',
            'password' => 'Password1!',
            'mot_de_passe_change' => true,
        ]);
        $this->creerPersonnel($utilisateur);

        $connexion = $this->postJson('/api/login', [
            'email' => 'PERSONNEL.MOBILE@EFELEDU.COM',
            'password' => 'Password1!',
            'device_name' => 'Telephone de test',
        ]);

        $connexion
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.name', 'Personnel Mobile')
            ->assertJsonPath('user.role', 'personnel')
            ->assertJsonPath('user.must_change_password', false);

        $jeton = $connexion->json('token');

        $this->withToken($jeton)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'personnel.mobile@efeledu.com');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $utilisateur->id,
            'name' => 'Telephone de test',
        ]);
    }

    public function test_un_administrateur_ne_peut_pas_se_connecter_a_l_application_mobile(): void
    {
        $utilisateur = User::factory()->create([
            'email' => 'admin.mobile@efeledu.com',
            'password' => 'Password1!',
        ]);
        $this->creerPersonnel($utilisateur, 'admin');

        $this->postJson('/api/login', [
            'email' => $utilisateur->email,
            'password' => 'Password1!',
            'device_name' => 'Telephone admin',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', __('messages.application_mobile_acces_admin_interdit'));

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_un_ancien_jeton_administrateur_est_refuse_et_supprime(): void
    {
        $utilisateur = User::factory()->create();
        $this->creerPersonnel($utilisateur, 'admin');
        $jeton = $utilisateur->createToken('Ancienne connexion admin')->plainTextToken;

        $this->withToken($jeton)
            ->getJson('/api/me')
            ->assertForbidden()
            ->assertJsonPath('message', __('messages.application_mobile_acces_admin_interdit'));

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_des_identifiants_incorrects_ne_creent_pas_de_jeton(): void
    {
        $utilisateur = User::factory()->create([
            'email' => 'mobile@efeledu.com',
            'password' => 'Password1!',
        ]);

        $this->postJson('/api/login', [
            'email' => $utilisateur->email,
            'password' => 'MauvaisPassword1!',
            'device_name' => 'Telephone de test',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_un_compte_bloque_ne_peut_pas_obtenir_de_jeton(): void
    {
        $utilisateur = User::factory()->create([
            'email' => 'bloque.mobile@efeledu.com',
            'password' => 'Password1!',
            'acces_bloque' => true,
        ]);

        $this->postJson('/api/login', [
            'email' => $utilisateur->email,
            'password' => 'Password1!',
            'device_name' => 'Telephone bloque',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', __('messages.compte_acces_bloque'));

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_la_deconnexion_supprime_uniquement_le_jeton_courant(): void
    {
        $utilisateur = User::factory()->create([
            'email' => 'deconnexion.mobile@efeledu.com',
            'password' => 'Password1!',
        ]);

        $premierJeton = $utilisateur->createToken('Premier telephone')->plainTextToken;
        $utilisateur->createToken('Deuxieme telephone');

        $this->withToken($premierJeton)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', __('messages.deconnexion_mobile_reussie'));

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->app['auth']->forgetGuards();

        $this->withToken($premierJeton)
            ->getJson('/api/me')
            ->assertUnauthorized();
    }

    public function test_un_jeton_existant_est_revoque_si_le_compte_devient_bloque(): void
    {
        $utilisateur = User::factory()->create([
            'acces_bloque' => false,
        ]);
        $jeton = $utilisateur->createToken('Telephone bloque ensuite')->plainTextToken;

        $utilisateur->forceFill(['acces_bloque' => true])->save();

        $this->withToken($jeton)
            ->getJson('/api/me')
            ->assertForbidden()
            ->assertJsonPath('message', __('messages.compte_acces_bloque'));

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_un_mot_de_passe_temporaire_bloque_les_routes_metier(): void
    {
        $utilisateur = User::factory()->create([
            'password' => 'Password1!',
            'mot_de_passe_change' => false,
        ]);
        $this->creerPersonnel($utilisateur);
        $jeton = $utilisateur->createToken('Telephone')->plainTextToken;

        $this->withToken($jeton)
            ->getJson('/api/mon-materiel')
            ->assertStatus(428)
            ->assertJsonPath('message', __('messages.mot_de_passe_changement_requis'));

        $this->withToken($jeton)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.must_change_password', true);
    }

    public function test_un_utilisateur_peut_changer_son_mot_de_passe_temporaire(): void
    {
        $utilisateur = User::factory()->create([
            'password' => 'Password1!',
            'mot_de_passe_change' => false,
        ]);
        $this->creerPersonnel($utilisateur);
        $jeton = $utilisateur->createToken('Telephone')->plainTextToken;

        $this->withToken($jeton)
            ->patchJson('/api/changer-mot-de-passe', [
                'mot_de_passe_actuel' => 'Password1!',
                'nouveau_mot_de_passe' => 'Nouveau2!',
                'nouveau_mot_de_passe_confirmation' => 'Nouveau2!',
            ])
            ->assertOk()
            ->assertJsonPath('must_change_password', false)
            ->assertJsonPath('message', __('messages.mot_de_passe_change'));

        $this->assertTrue($utilisateur->fresh()->mot_de_passe_change);
        $this->assertTrue(password_verify('Nouveau2!', $utilisateur->fresh()->password));

        $this->withToken($jeton)
            ->getJson('/api/mon-materiel')
            ->assertOk();
    }

    public function test_un_mot_de_passe_actuel_incorrect_est_refuse(): void
    {
        $utilisateur = User::factory()->create([
            'password' => 'Password1!',
            'mot_de_passe_change' => false,
        ]);
        $this->creerPersonnel($utilisateur);

        $this->withToken($utilisateur->createToken('Telephone')->plainTextToken)
            ->patchJson('/api/changer-mot-de-passe', [
                'mot_de_passe_actuel' => 'Incorrect1!',
                'nouveau_mot_de_passe' => 'Nouveau2!',
                'nouveau_mot_de_passe_confirmation' => 'Nouveau2!',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('mot_de_passe_actuel');

        $this->assertFalse($utilisateur->fresh()->mot_de_passe_change);
    }

    public function test_la_connexion_mobile_attend_le_code_2fa_avant_de_creer_un_jeton(): void
    {
        $utilisateur = $this->creerUtilisateurAvecDoubleAuthentification();

        $reponse = $this->postJson('/api/login', [
            'email' => $utilisateur->email,
            'password' => 'Password1!',
            'device_name' => 'Telephone 2FA',
        ]);

        $reponse
            ->assertStatus(202)
            ->assertJsonPath('two_factor_required', true)
            ->assertJsonPath('expires_in', 300);

        $this->assertIsString($reponse->json('challenge_token'));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_un_code_2fa_valide_termine_la_connexion_mobile(): void
    {
        $utilisateur = $this->creerUtilisateurAvecDoubleAuthentification();
        $defi = $this->demarrerDefiDoubleAuthentification($utilisateur);
        $code = app(Google2FA::class)->getCurrentOtp($utilisateur->two_factor_secret);

        $this->postJson('/api/login/double-authentification', [
            'challenge_token' => $defi,
            'mode' => 'application',
            'code' => $code,
        ])
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', $utilisateur->email);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $utilisateur->id,
            'name' => 'Telephone 2FA',
        ]);

        $this->postJson('/api/login/double-authentification', [
            'challenge_token' => $defi,
            'mode' => 'application',
            'code' => $code,
        ])->assertStatus(410);
    }

    public function test_un_code_2fa_invalide_ne_cree_pas_de_jeton_mobile(): void
    {
        $utilisateur = $this->creerUtilisateurAvecDoubleAuthentification();
        $defi = $this->demarrerDefiDoubleAuthentification($utilisateur);

        $this->postJson('/api/login/double-authentification', [
            'challenge_token' => $defi,
            'mode' => 'application',
            'code' => '000000',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_un_code_de_recuperation_termine_la_connexion_mobile_et_est_consomme(): void
    {
        $utilisateur = $this->creerUtilisateurAvecDoubleAuthentification();
        $codeRecuperation = app(TwoFactorAuthService::class)
            ->regenererCodesRecuperation($utilisateur)[0];
        $defi = $this->demarrerDefiDoubleAuthentification($utilisateur->fresh());

        $this->postJson('/api/login/double-authentification', [
            'challenge_token' => $defi,
            'mode' => 'recuperation',
            'recovery_code' => $codeRecuperation,
        ])->assertOk();

        $this->assertCount(7, $utilisateur->fresh()->two_factor_recovery_codes);
    }

    private function creerUtilisateurAvecDoubleAuthentification(): User
    {
        $utilisateur = User::factory()->create([
            'email' => 'mobile.2fa@efeledu.com',
            'password' => 'Password1!',
        ]);
        $this->creerPersonnel($utilisateur);
        $service = app(TwoFactorAuthService::class);

        $utilisateur->forceFill([
            'two_factor_secret' => $service->genererSecret(),
            'two_factor_recovery_codes' => $service->hacherCodesRecuperation(
                $service->genererCodesRecuperation()
            ),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $utilisateur->fresh(['personnel', 'etudiant']);
    }

    private function demarrerDefiDoubleAuthentification(User $utilisateur): string
    {
        $reponse = $this->postJson('/api/login', [
            'email' => $utilisateur->email,
            'password' => 'Password1!',
            'device_name' => 'Telephone 2FA',
        ])->assertStatus(202);

        return $reponse->json('challenge_token');
    }

    private function creerPersonnel(User $utilisateur, string $role = 'personnel'): Personnel
    {
        return Personnel::create([
            'user_id' => $utilisateur->id,
            'service' => 'Support IT',
            'poste' => 'Technicien',
            'type_contrat' => 'cdi',
            'role' => $role,
        ]);
    }
}
