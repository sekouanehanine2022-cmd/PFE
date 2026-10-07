<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FALaravel\Google2FA;
use Tests\TestCase;

class TwoFactorActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_utilisateur_non_connecte_ne_peut_pas_preparer_la_double_authentification(): void
    {
        $this->post(route('parametres.double-authentification.preparer'))
            ->assertRedirect(route('login'));
    }

    public function test_un_utilisateur_peut_preparer_la_double_authentification(): void
    {
        $utilisateur = User::factory()->create();

        $this->actingAs($utilisateur)
            ->post(route('parametres.double-authentification.preparer'))
            ->assertRedirect(route('parametres.index'));

        $utilisateur->refresh();

        $this->assertNotNull($utilisateur->two_factor_secret);
        $this->assertNull($utilisateur->two_factor_confirmed_at);

        $this->actingAs($utilisateur)
            ->get(route('parametres.index'))
            ->assertOk()
            ->assertSee('Scannez ce QR Code')
            ->assertSee('<svg', false);
    }

    public function test_un_code_invalide_ne_termine_pas_l_activation(): void
    {
        $utilisateur = User::factory()->create();
        $utilisateur->forceFill([
            'two_factor_secret' => app(TwoFactorAuthService::class)->genererSecret(),
        ])->save();

        $this->actingAs($utilisateur)
            ->post(route('parametres.double-authentification.confirmer'), [
                'code_2fa' => '000000',
            ])
            ->assertSessionHasErrors('code_2fa');

        $this->assertFalse($utilisateur->fresh()->doubleAuthentificationActive());
    }

    public function test_un_code_valide_active_la_double_authentification_et_affiche_les_codes_de_recuperation(): void
    {
        $utilisateur = User::factory()->create();
        $secret = app(TwoFactorAuthService::class)->genererSecret();
        $utilisateur->forceFill(['two_factor_secret' => $secret])->save();
        $code = app(Google2FA::class)->getCurrentOtp($secret);

        $reponse = $this->actingAs($utilisateur)
            ->post(route('parametres.double-authentification.confirmer'), [
                'code_2fa' => $code,
            ]);

        $reponse
            ->assertRedirect(route('parametres.index'))
            ->assertSessionHas('codes_recuperation_2fa', fn (array $codes) => count($codes) === 8);

        $utilisateur->refresh();

        $this->assertTrue($utilisateur->doubleAuthentificationActive());
        $this->assertNotNull($utilisateur->two_factor_confirmed_at);
        $this->assertCount(8, $utilisateur->two_factor_recovery_codes);

        foreach (session('codes_recuperation_2fa') as $index => $codeRecuperation) {
            $this->assertTrue(Hash::check(
                $codeRecuperation,
                $utilisateur->two_factor_recovery_codes[$index]
            ));
        }
    }

    public function test_un_utilisateur_peut_annuler_une_activation_non_confirmee(): void
    {
        $utilisateur = User::factory()->create();
        $utilisateur->forceFill([
            'two_factor_secret' => app(TwoFactorAuthService::class)->genererSecret(),
        ])->save();

        $this->actingAs($utilisateur)
            ->delete(route('parametres.double-authentification.annuler'))
            ->assertRedirect(route('parametres.index'));

        $this->assertNull($utilisateur->fresh()->two_factor_secret);
    }

    public function test_un_utilisateur_peut_regenerer_ses_codes_avec_son_mot_de_passe(): void
    {
        $utilisateur = $this->utilisateurAvecDoubleAuthentification();
        $anciensCodes = $utilisateur->two_factor_recovery_codes;

        $reponse = $this->actingAs($utilisateur)
            ->post(route('parametres.double-authentification.codes-regenerer'), [
                'mot_de_passe_2fa' => 'password',
            ]);

        $reponse
            ->assertRedirect(route('parametres.index'))
            ->assertSessionHas('codes_recuperation_2fa', fn (array $codes) => count($codes) === 8);

        $this->assertNotSame($anciensCodes, $utilisateur->fresh()->two_factor_recovery_codes);
    }

    public function test_un_mauvais_mot_de_passe_ne_regenere_pas_les_codes(): void
    {
        $utilisateur = $this->utilisateurAvecDoubleAuthentification();
        $anciensCodes = $utilisateur->two_factor_recovery_codes;

        $this->actingAs($utilisateur)
            ->post(route('parametres.double-authentification.codes-regenerer'), [
                'mot_de_passe_2fa' => 'incorrect',
            ])
            ->assertSessionHasErrors('mot_de_passe_2fa')
            ->assertSessionHas('modal_2fa', 'regenerer');

        $this->assertSame($anciensCodes, $utilisateur->fresh()->two_factor_recovery_codes);
        $this->assertNull(session('_old_input.mot_de_passe_2fa'));
    }

    public function test_un_utilisateur_peut_desactiver_la_2fa_avec_son_mot_de_passe(): void
    {
        $utilisateur = $this->utilisateurAvecDoubleAuthentification();

        $this->actingAs($utilisateur)
            ->delete(route('parametres.double-authentification.desactiver'), [
                'mot_de_passe_2fa' => 'password',
            ])
            ->assertRedirect(route('parametres.index'));

        $utilisateur->refresh();
        $this->assertFalse($utilisateur->doubleAuthentificationActive());
        $this->assertNull($utilisateur->two_factor_secret);
        $this->assertNull($utilisateur->two_factor_recovery_codes);
    }

    public function test_un_mauvais_mot_de_passe_ne_desactive_pas_la_2fa(): void
    {
        $utilisateur = $this->utilisateurAvecDoubleAuthentification();

        $this->actingAs($utilisateur)
            ->delete(route('parametres.double-authentification.desactiver'), [
                'mot_de_passe_2fa' => 'incorrect',
            ])
            ->assertSessionHasErrors('mot_de_passe_2fa')
            ->assertSessionHas('modal_2fa', 'desactiver');

        $this->assertTrue($utilisateur->fresh()->doubleAuthentificationActive());
        $this->assertNull(session('_old_input.mot_de_passe_2fa'));
    }

    private function utilisateurAvecDoubleAuthentification(): User
    {
        $utilisateur = User::factory()->create([
            'password' => 'password',
        ]);
        $service = app(TwoFactorAuthService::class);

        $utilisateur->forceFill([
            'two_factor_secret' => $service->genererSecret(),
            'two_factor_recovery_codes' => $service->hacherCodesRecuperation(
                $service->genererCodesRecuperation()
            ),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $utilisateur->fresh();
    }
}
