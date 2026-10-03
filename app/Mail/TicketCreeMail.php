<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketCreeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket)
    {
        $this->ticket->loadMissing('demandeur');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre ticket a bien ete enregistre');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tickets.cree');
    }

    public function attachments(): array
    {
        return [];
    }
}
