<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifierAdresseEmailMobile extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiration = config('auth.verification.expire', 60);
        $url = URL::temporarySignedRoute(
            'verification.mobile.verify',
            now()->addMinutes($expiration),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        return (new MailMessage)
            ->subject('Verification de votre adresse e-mail')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Cliquez sur le bouton ci-dessous pour verifier votre adresse e-mail.')
            ->action('Verifier mon adresse e-mail', $url)
            ->line('Ce lien de verification expire dans '.$expiration.' minutes.')
            ->line('Revenez ensuite dans l application mobile pour continuer.')
            ->salutation('L equipe support IT');
    }
}
