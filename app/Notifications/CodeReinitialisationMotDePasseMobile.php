<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CodeReinitialisationMotDePasseMobile extends Notification
{
    use Queueable;

    // Recoit et garde les objets ou les valeurs utilises par les autres methodes.
    public function __construct(
        public readonly string $code,
        public readonly int $expiration
    ) {}

    // Indique comment envoyer cette notification Laravel, par exemple par email.
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    // Prepare le contenu de l'email avec le lien ou le code a envoyer.
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Code de reinitialisation de votre mot de passe')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Vous avez demande la reinitialisation de votre mot de passe depuis l application mobile.')
            ->line('Votre code de verification est : '.$this->code)
            ->line('Ce code expire dans '.$this->expiration.' minutes.')
            ->line('Si vous n etes pas a l origine de cette demande, ignorez cet e-mail.')
            ->salutation('L equipe support IT');
    }
}
