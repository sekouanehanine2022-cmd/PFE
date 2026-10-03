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

    public function __construct(public Ticket $ticket, public bool $estModification)
    {
        $this->ticket->loadMissing(['demandeur', 'incident']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->estModification
                ? 'La reponse a votre incident a ete mise a jour'
                : 'Le support IT a repondu a votre incident'
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tickets.reponse-incident');
    }

    public function attachments(): array
    {
        return [];
    }
}
