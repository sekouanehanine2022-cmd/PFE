<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemandeAccepteeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $typeDemande;

    public function __construct(public Ticket $ticket)
    {
        $this->ticket->loadMissing(['demandeur', 'materiel', 'demande']);
        $this->typeDemande = $this->ticket->demande->type_demande;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre demande a ete acceptee');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tickets.demande-acceptee');
    }

    public function attachments(): array
    {
        return [];
    }
}
