<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NouveauTicketAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $typeTicket;

    public function __construct(public Ticket $ticket)
    {
        $this->ticket->loadMissing(['demandeur', 'incident', 'demande']);
        $this->typeTicket = $this->ticket->incident
            ? 'incident'
            : $this->ticket->demande->type_demande;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nouveau ticket : '.$this->ticket->titre);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tickets.nouveau-admin');
    }

    public function attachments(): array
    {
        return [];
    }
}
