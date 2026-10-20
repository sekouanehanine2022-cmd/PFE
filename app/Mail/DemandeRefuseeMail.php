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

    // Recoit et garde les objets ou les valeurs utilises par les autres methodes.
    public function __construct(public Ticket $ticket)
    {
        $this->ticket->loadMissing(['demandeur', 'demande']);
        $this->typeDemande = $this->ticket->demande->type_demande;
    }

    // Definit l objet de cet e-mail.
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre demande ne peut pas etre acceptee');
    }

    // Selectionne la vue Blade utilisee pour le corps de cet e-mail.
    public function content(): Content
    {
        return new Content(view: 'emails.tickets.demande-refusee');
    }

    // Declare les pieces jointes de cet e-mail ; aucune si la liste est vide.
    public function attachments(): array
    {
        return [];
    }
}
