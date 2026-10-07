<?php

namespace Tests\Feature\Api;

use App\Models\Personnel;
use App\Models\User;
use App\Notifications\VerifierAdresseEmailMobile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class VerificationEmailApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_statut_retourne_la_verification_enregistree_en_base(): void
    {
        $utilisateur = $this->creerPersonnelNonVerifie();
        $jeton = $utilisateur->createToken('Telephone')->plainTextToken;

        $this->withToken($jeton)
            ->getJson('/api/email/verification-status')
            ->assertOk()
            ->assertJsonPath('email_verified', false);

        $utilisateur->markEmailAsVerified();

        $this->withToken($jeton)
            ->getJson('/api/email/verification-status')
            ->assertOk()
            ->assertJsonPath('email_verified', true);
    }

    public function test_un_utilisateur_peut_demander_un_nouveau_lien_mobile(): void
    {
        Notification::fake();
        $utilisateur = $this->creerPersonnelNonVerifie();

        $this->withToken($utilisateur->createToken('Telephone')->plainTextToken)
            ->postJson('/api/email/verification-notification')
            ->assertOk()
            ->assertJsonPath('email_verified', false);

        Notification::assertSentTo($utilisateur, VerifierAdresseEmailMobile::class);
    }

    public function test_le_lien_signe_mobile_verifie_le_compte_sans_session_web(): void
    {
        $utilisateur = $this->creerPersonnelNonVerifie();
        $url = URL::temporarySignedRoute(
            'verification.mobile.verify',
            now()->addMinutes(2),
            [
                'id' => $utilisateur->id,
                'hash' => sha1($utilisateur->email),
            ]
        );

        $this->get($url)
            ->assertOk()
            ->assertSee('Adresse e-mail verifiee');

        $this->assertNotNull($utilisateur->fresh()->email_verified_at);
    }

    public function test_les_routes_metier_sont_bloquees_avant_verification(): void
    {
        $utilisateur = $this->creerPersonnelNonVerifie();

        $this->withToken($utilisateur->createToken('Telephone')->plainTextToken)
            ->getJson('/api/mon-materiel')
            ->assertForbidden();
    }

    private function creerPersonnelNonVerifie(): User
    {
        $utilisateur = User::factory()->unverified()->create([
            'mot_de_passe_change' => true,
        ]);
        Personnel::create([
            'user_id' => $utilisateur->id,
            'service' => 'Pedagogie',
            'poste' => 'Formateur',
            'type_contrat' => 'cdi',
            'role' => 'personnel',
        ]);

        return $utilisateur;
    }
}
