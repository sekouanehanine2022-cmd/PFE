<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifierAdresseEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

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
