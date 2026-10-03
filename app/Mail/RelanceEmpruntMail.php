<?php

namespace App\Mail;

use App\Models\Emprunt;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RelanceEmpruntMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $nomEtudiant;
    public string $nomMateriel;
    public string $dateFinPrevue;
    public int $joursRestants;

    public function __construct(public Emprunt $emprunt)
    {
        $this->emprunt->loadMissing(['etudiant.user', 'pcPortable.materiel']);

        $this->nomEtudiant = $this->emprunt->etudiant->user->name ?? 'etudiant';
        $this->nomMateriel = $this->emprunt->pcPortable?->materiel?->nom ?? 'PC portable emprunte';

        $dateFin = Carbon::parse($this->emprunt->date_fin_prevue)->startOfDay();

        $this->dateFinPrevue = $dateFin->format('d/m/Y');
        $this->joursRestants = (int) now()->startOfDay()->diffInDays($dateFin, false);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rappel de retour de materiel'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.relance-emprunt'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
