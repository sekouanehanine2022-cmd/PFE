<?php

namespace Tests\Feature;

use App\Models\Personnel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MotDePasseSansNomTest extends TestCase
{
    use RefreshDatabase;

    public static function parcours(): array
    {
        $cas = [];
        foreach (['parametres', 'premiere_connexion_web', 'changement_mobile', 'creation_admin', 'reset_admin', 'oubli_web', 'oubli_mobile'] as $parcours) {
            foreach (['ELodie8!', 'Martin8!'] as $motDePasse) {
                $cas[$parcours.' '.$motDePasse] = [$parcours, $motDePasse];
            }
        }
        return $cas;
    }

    #[DataProvider('parcours')]
    public function test_le_nom_et_le_prenom_sont_refuses_dans_tous_les_parcours(string $parcours, string $motDePasse): void
    {
        $utilisateur = User::factory()->create([
            'name' => 'Elodie Martin',
            'email' => 'e.martin@efeledu.com',
            'password' => 'Ancien8!',
            'mot_de_passe_change' => $parcours !== 'premiere_connexion_web',
        ]);
        Personnel::create(['user_id' => $utilisateur->id, 'role' => 'personnel', 'type_contrat' => 'cdi']);
        $hashAvant = $utilisateur->password;
        $changement = [
            'mot_de_passe_actuel' => 'Ancien8!',
            'nouveau_mot_de_passe' => $motDePasse,
            'nouveau_mot_de_passe_confirmation' => $motDePasse,
        ];

        if (in_array($parcours, ['creation_admin', 'reset_admin'], true)) {
            $admin = User::factory()->create(['mot_de_passe_change' => true]);
            Personnel::create(['user_id' => $admin->id, 'role' => 'admin', 'type_contrat' => 'cdi']);
            $this->actingAs($admin);
        } elseif (! str_starts_with($parcours, 'oubli_')) {
            $this->actingAs($utilisateur);
        }

        switch ($parcours) {
            case 'parametres':
                $this->patch(route('parametres.mot-de-passe'), $changement)->assertSessionHasErrors('nouveau_mot_de_passe');
                break;
            case 'premiere_connexion_web':
                $this->patch(route('mot-de-passe.update'), $changement)->assertSessionHasErrors('nouveau_mot_de_passe');
                $this->assertFalse($utilisateur->fresh()->mot_de_passe_change);
                break;
            case 'changement_mobile':
                $this->patchJson('/api/changer-mot-de-passe', $changement)->assertUnprocessable()->assertJsonValidationErrors('nouveau_mot_de_passe');
                break;
            case 'creation_admin':
                $this->post(route('utilisateurs.store'), [
                    'type_compte' => 'personnel', 'name' => 'Elodie Martin', 'email' => 'nouveau@efeledu.com',
                    'password_temporaire' => $motDePasse, 'password_temporaire_confirmation' => $motDePasse,
                    'service' => 'IT', 'poste' => 'Support', 'type_contrat' => 'cdi',
                ])->assertSessionHasErrors('password_temporaire');
                $this->assertDatabaseMissing('users', ['email' => 'nouveau@efeledu.com']);
                break;
            case 'reset_admin':
                $this->patch(route('utilisateurs.mot-de-passe', $utilisateur), $changement)
                    ->assertSessionHasErrors('nouveau_mot_de_passe', null, 'reinitialisationMotDePasse');
                break;
            case 'oubli_web':
                $token = Password::broker()->createToken($utilisateur);
                $this->post('/password/reset', ['email' => $utilisateur->email, 'token' => $token,
                    'password' => $motDePasse, 'password_confirmation' => $motDePasse])->assertSessionHasErrors('password');
                break;
            case 'oubli_mobile':
                DB::table('password_reset_tokens')->insert(['email' => $utilisateur->email, 'token' => Hash::make('123456'), 'created_at' => now()]);
                $this->postJson('/api/mot-de-passe/reinitialiser', ['email' => $utilisateur->email, 'code' => '123456',
                    'password' => $motDePasse, 'password_confirmation' => $motDePasse])->assertUnprocessable()->assertJsonValidationErrors('password');
                $this->assertDatabaseHas('password_reset_tokens', ['email' => $utilisateur->email]);
                break;
        }

        $this->assertSame($hashAvant, $utilisateur->fresh()->password);
    }
}
