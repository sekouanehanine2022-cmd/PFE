<?php

namespace App\Mail;

use App\Models\Affectation;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RelanceAffectationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $nomCollaborateur;
    public string $nomMateriel;
    public string $dateFinPrevue;
    public int $joursRestants;

    public function __construct(public Affectation $affectation)
    {
        $this->affectation->loadMissing(['personnel.user', 'materiel']);

        $this->nomCollaborateur = $this->affectation->personnel->user->name ?? 'collaborateur';
        $this->nomMateriel = $this->affectation->materiel->nom ?? 'materiel affecte';

        $dateFin = Carbon::parse($this->affectation->date_fin)->startOfDay();
        $this->dateFinPrevue = $dateFin->format('d/m/Y');
        $this->joursRestants = (int) now()->startOfDay()->diffInDays($dateFin, false);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Rappel de retour de materiel');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.relance-affectation');
    }

    public function attachments(): array
    {
        return [];
    }
}
