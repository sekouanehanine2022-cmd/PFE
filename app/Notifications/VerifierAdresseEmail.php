<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifierAdresseEmail extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);
        $expiration = config('auth.verification.expire', 60);

        return (new MailMessage)
            ->subject('Verification de votre adresse e-mail')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Votre compte a bien ete cree.')
            ->line('Cliquez sur le bouton ci-dessous pour verifier votre adresse e-mail.')
            ->action('Verifier mon adresse e-mail', $url)
            ->line('Ce lien de verification expire dans '.$expiration.' minutes.')
            ->line('Si vous n etes pas a l origine de cette demande, vous pouvez ignorer cet e-mail.')
            ->salutation('L equipe support IT');
    }
}
