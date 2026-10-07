<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CodeReinitialisationMotDePasseMobile extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly int $expiration
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

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
