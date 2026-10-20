<?php

namespace App\Services;

use App\Mail\DemandeAccepteeMail;
use App\Mail\DemandeRefuseeMail;
use App\Mail\NouveauTicketAdminMail;
use App\Mail\TicketAssigneMail;
use App\Mail\TicketCreeMail;
use App\Mail\TicketResoluMail;
use App\Mail\ReponseIncidentMail;
use App\Models\Ticket;
use App\Models\Personnel;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificationTicketService
{
    // Envoie l accuse de reception du ticket a son demandeur.
    public function envoyerCreation(Ticket $ticket): bool
    {
        return $this->envoyer($ticket, new TicketCreeMail($ticket), 'creation');
    }

    // Envoie aux administrateurs le mail signalant le nouveau ticket.
    public function envoyerCreationAuxAdmins(Ticket $ticket): bool
    {
        $administrateurs = Personnel::query()
            ->with('user')
            ->where('role', 'admin')
            ->get()
            ->pluck('user')
            ->filter(fn ($user) => filled($user?->email));

        if ($administrateurs->isEmpty()) {
            Log::warning('Aucun administrateur avec une adresse email pour le nouveau ticket.', [
                'ticket_id' => $ticket->id,
            ]);

            return false;
        }

        $tousEnvoyes = true;

        foreach ($administrateurs as $administrateur) {
            try {
                Mail::to($administrateur->email)->send(new NouveauTicketAdminMail($ticket));
            } catch (Throwable $exception) {
                $tousEnvoyes = false;

                Log::error('Echec de la notification d un nouveau ticket a un administrateur.', [
                    'ticket_id' => $ticket->id,
                    'administrateur_id' => $administrateur->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $tousEnvoyes;
    }

    // Envoie au demandeur le mail de prise en charge du ticket.
    public function envoyerAssignation(Ticket $ticket): bool
    {
        return $this->envoyer($ticket, new TicketAssigneMail($ticket), 'assignation');
    }

    // Envoie au demandeur le mail d acceptation de son affectation ou emprunt.
    public function envoyerAcceptation(Ticket $ticket): bool
    {
        return $this->envoyer($ticket, new DemandeAccepteeMail($ticket), 'acceptation');
    }

    // Envoie le refus et son motif au demandeur.
    public function envoyerRefus(Ticket $ticket): bool
    {
        return $this->envoyer($ticket, new DemandeRefuseeMail($ticket), 'refus');
    }

    // Envoie au demandeur le mail concernant la reponse a son incident.
    public function envoyerReponseIncident(Ticket $ticket, bool $estModification): bool
    {
        return $this->envoyer(
            $ticket,
            new ReponseIncidentMail($ticket, $estModification),
            $estModification ? 'modification_reponse_incident' : 'reponse_incident'
        );
    }

    // Envoie le mail de resolution au demandeur du ticket.
    public function envoyerResolution(Ticket $ticket): bool
    {
        return $this->envoyer($ticket, new TicketResoluMail($ticket), 'resolution');
    }

    // Envoie l'email du ticket. En cas d'echec, enregistre l'erreur sans annuler l'action sur le ticket.
    private function envoyer(Ticket $ticket, Mailable $mail, string $evenement): bool
    {
        $ticket->loadMissing('demandeur');
        $email = $ticket->demandeur?->email;

        if (! $email) {
            Log::warning('Email du demandeur introuvable pour une notification de ticket.', [
                'ticket_id' => $ticket->id,
                'evenement' => $evenement,
            ]);

            return false;
        }

        try {
            Mail::to($email)->send($mail);
        } catch (Throwable $exception) {
            Log::error('Echec de l envoi d une notification de ticket.', [
                'ticket_id' => $ticket->id,
                'evenement' => $evenement,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }

        return true;
    }
}
