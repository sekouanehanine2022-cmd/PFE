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

    // Recoit et garde les objets ou les valeurs utilises par les autres methodes.
    public function __construct(public Affectation $affectation)
    {
        $this->affectation->loadMissing(['personnel.user', 'materiel']);

        $this->nomCollaborateur = $this->affectation->personnel->user->name ?? 'collaborateur';
        $this->nomMateriel = app(\App\Services\MaterielService::class)->libelleAvecNumero($this->affectation->materiel);

        $dateFin = Carbon::parse($this->affectation->date_fin->toDateString(), 'Europe/Paris')->startOfDay();
        $this->dateFinPrevue = $dateFin->format('d/m/Y');
        $this->joursRestants = (int) now('Europe/Paris')->startOfDay()->diffInDays($dateFin, false);
    }

    // Definit l objet de cet e-mail.
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Rappel de retour de materiel');
    }

    // Selectionne la vue Blade utilisee pour le corps de cet e-mail.
    public function content(): Content
    {
        return new Content(view: 'emails.relance-affectation');
    }

    // Declare les pieces jointes de cet e-mail ; aucune si la liste est vide.
    public function attachments(): array
    {
        return [];
    }
}
