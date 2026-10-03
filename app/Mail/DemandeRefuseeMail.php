<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemandeRefuseeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $typeDemande;

    public function __construct(public Ticket $ticket)
    {
        $this->ticket->loadMissing(['demandeur', 'demande']);
        $this->typeDemande = $this->ticket->demande->type_demande;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre demande ne peut pas etre acceptee');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tickets.demande-refusee');
    }

    public function attachments(): array
    {
        return [];
    }
}
