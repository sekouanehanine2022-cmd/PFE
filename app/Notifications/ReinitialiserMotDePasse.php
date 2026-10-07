<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ReinitialiserMotDePasse extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
        $expiration = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject('Reinitialisation de votre mot de passe')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Nous avons recu une demande de reinitialisation de votre mot de passe.')
            ->action('Reinitialiser mon mot de passe', $url)
            ->line('Ce lien expire dans '.$expiration.' minutes.')
            ->line('Si vous n etes pas a l origine de cette demande, ignorez cet e-mail.')
            ->salutation('L equipe support IT');
    }
}
