<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketAssigneMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket)
    {
        $this->ticket->loadMissing(['demandeur', 'technicien']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre ticket est en cours de traitement');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tickets.assigne');
    }

    public function attachments(): array
    {
        return [];
    }
}
