<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifierAdresseEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_lien_de_verification_expire_apres_deux_minutes(): void
    {
        $this->assertSame(2, config('auth.verification.expire'));
    }

    public function test_un_utilisateur_non_verifie_est_redirige_vers_la_verification(): void
    {
        $utilisateur = User::factory()->unverified()->create([
            'mot_de_passe_change' => true,
        ]);

        $this->actingAs($utilisateur)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_le_changement_du_mot_de_passe_temporaire_passe_avant_la_verification(): void
    {
        $utilisateur = User::factory()->unverified()->create([
            'mot_de_passe_change' => false,
        ]);

        $this->actingAs($utilisateur)
            ->get(route('dashboard'))
            ->assertRedirect(route('mot-de-passe.edit'));

        $this->actingAs($utilisateur)
            ->get(route('mot-de-passe.edit'))
            ->assertOk();
    }

    public function test_la_page_de_verification_est_affichee_en_francais(): void
    {
        $utilisateur = User::factory()->unverified()->create([
            'mot_de_passe_change' => true,
        ]);

        $this->actingAs($utilisateur)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Verifiez votre boite mail')
            ->assertSee($utilisateur->email)
            ->assertSee('Renvoyer le lien de verification')
            ->assertSee('Se deconnecter');
    }

    public function test_la_route_d_etat_indique_si_l_adresse_est_verifiee(): void
    {
        $utilisateur = User::factory()->unverified()->create([
            'mot_de_passe_change' => true,
        ]);

        $this->actingAs($utilisateur)
            ->getJson(route('verification.status'))
            ->assertOk()
            ->assertJson([
                'verified' => false,
                'redirect' => route('dashboard'),
            ]);

        $utilisateur->markEmailAsVerified();

        $this->actingAs($utilisateur)
            ->getJson(route('verification.status'))
            ->assertOk()
            ->assertJson(['verified' => true]);
    }

    public function test_le_lien_valide_affiche_la_confirmation_verte(): void
    {
        $utilisateur = User::factory()->unverified()->create([
            'mot_de_passe_change' => true,
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(2),
            [
                'id' => $utilisateur->getKey(),
                'hash' => sha1($utilisateur->getEmailForVerification()),
            ]
        );

        $this->actingAs($utilisateur)
            ->get($url)
            ->assertOk()
            ->assertSee('Adresse e-mail verifiee')
            ->assertSee('Votre adresse a ete verifiee avec succes.')
            ->assertSee('id="verification-confirmation"', false)
            ->assertSee('js/verification-email.js');

        $this->assertNotNull($utilisateur->fresh()->email_verified_at);
    }

    public function test_un_utilisateur_recoit_la_notification_de_verification_en_francais(): void
    {
        Notification::fake();

        $utilisateur = User::factory()->unverified()->create();
        $utilisateur->sendEmailVerificationNotification();

        Notification::assertSentTo(
            $utilisateur,
            VerifierAdresseEmail::class,
            function (VerifierAdresseEmail $notification) use ($utilisateur) {
                $message = $notification->toMail($utilisateur);

                return $message->subject === 'Verification de votre adresse e-mail'
                    && $message->actionText === 'Verifier mon adresse e-mail';
            }
        );
    }
}
