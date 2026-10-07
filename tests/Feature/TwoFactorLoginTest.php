<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FALaravel\Google2FA;
use Tests\TestCase;

class TwoFactorLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_mot_de_passe_correct_suspend_la_connexion_si_la_2fa_est_active(): void
    {
        $utilisateur = $this->utilisateurAvecDoubleAuthentification();

        $this->post(route('login'), [
            'email' => $utilisateur->email,
            'password' => 'password',
        ])
            ->assertRedirect(route('double-authentification.challenge'))
            ->assertSessionHas('auth.two_factor_user_id', $utilisateur->id);

        $this->assertGuest();
    }

    public function test_un_code_totp_invalide_ne_connecte_pas_l_utilisateur(): void
    {
        $utilisateur = $this->utilisateurAvecDoubleAuthentification();
        $this->commencerConnexion($utilisateur);

        $this->post(route('double-authentification.verifier'), [
            'code_2fa' => '000000',
        ])->assertSessionHasErrors('code_2fa');

        $this->assertGuest();
    }

    public function test_un_code_totp_valide_termine_la_connexion(): void
    {
        $utilisateur = $this->utilisateurAvecDoubleAuthentification();
        $this->commencerConnexion($utilisateur);
        $code = app(Google2FA::class)->getCurrentOtp($utilisateur->two_factor_secret);

        $this->post(route('double-authentification.verifier'), [
            'code_2fa' => $code,
        ])->assertRedirect(route('mon-materiel.index'));

        $this->assertAuthenticatedAs($utilisateur);
        $this->assertNull(session('auth.two_factor_user_id'));
    }

    public function test_un_code_de_recuperation_valide_termine_la_connexion_et_est_consomme(): void
    {
        $utilisateur = $this->utilisateurAvecDoubleAuthentification();
        $codeRecuperation = app(TwoFactorAuthService::class)
            ->regenererCodesRecuperation($utilisateur)[0];
        $this->commencerConnexion($utilisateur->fresh());

        $this->post(route('double-authentification.recuperation'), [
            'code_recuperation' => $codeRecuperation,
        ])->assertRedirect(route('mon-materiel.index'));

        $this->assertAuthenticatedAs($utilisateur);
        $this->assertCount(7, $utilisateur->fresh()->two_factor_recovery_codes);
    }

    public function test_la_page_de_verification_est_inaccessible_sans_connexion_en_attente(): void
    {
        $this->get(route('double-authentification.challenge'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
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

    private function commencerConnexion(User $utilisateur): void
    {
        $this->post(route('login'), [
            'email' => $utilisateur->email,
            'password' => 'password',
        ])->assertRedirect(route('double-authentification.challenge'));
    }
}
