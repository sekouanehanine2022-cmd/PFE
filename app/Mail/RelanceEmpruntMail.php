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

    // Recoit et garde les objets ou les valeurs utilises par les autres methodes.
    public function __construct(public Emprunt $emprunt)
    {
        $this->emprunt->loadMissing(['etudiant.user', 'pcPortable.materiel']);

        $this->nomEtudiant = $this->emprunt->etudiant->user->name ?? 'etudiant';
        $this->nomMateriel = app(\App\Services\MaterielService::class)->libelleAvecNumero($this->emprunt->materiel);

        $dateFin = Carbon::parse($this->emprunt->date_fin_prevue->toDateString(), 'Europe/Paris')->startOfDay();

        $this->dateFinPrevue = $dateFin->format('d/m/Y');
        $this->joursRestants = (int) now('Europe/Paris')->startOfDay()->diffInDays($dateFin, false);
    }

    // Definit l objet de cet e-mail.
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rappel de retour de materiel'
        );
    }

    // Selectionne la vue Blade utilisee pour le corps de cet e-mail.
    public function content(): Content
    {
        return new Content(
            view: 'emails.relance-emprunt'
        );
    }

    // Declare les pieces jointes de cet e-mail ; aucune si la liste est vide.
    public function attachments(): array
    {
        return [];
    }
}
