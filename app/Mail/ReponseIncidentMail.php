<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReponseIncidentMail extends Mailable
{
    use Queueable, SerializesModels;

    // Recoit et garde les objets ou les valeurs utilises par les autres methodes.
    public function __construct(public Ticket $ticket, public bool $estModification)
    {
        $this->ticket->loadMissing(['demandeur', 'incident']);
    }

    // Definit l objet de cet e-mail.
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->estModification
                ? 'La reponse a votre incident a ete mise a jour'
                : 'Le support IT a repondu a votre incident'
        );
    }

    // Selectionne la vue Blade utilisee pour le corps de cet e-mail.
    public function content(): Content
    {
        return new Content(view: 'emails.tickets.reponse-incident');
    }

    // Declare les pieces jointes de cet e-mail ; aucune si la liste est vide.
    public function attachments(): array
    {
        return [];
    }
}
