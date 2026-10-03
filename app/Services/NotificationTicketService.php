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
    public function envoyerCreation(Ticket $ticket): bool
    {
        return $this->envoyer($ticket, new TicketCreeMail($ticket), 'creation');
    }

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

    public function envoyerAssignation(Ticket $ticket): bool
    {
        return $this->envoyer($ticket, new TicketAssigneMail($ticket), 'assignation');
    }

    public function envoyerAcceptation(Ticket $ticket): bool
    {
        return $this->envoyer($ticket, new DemandeAccepteeMail($ticket), 'acceptation');
    }

    public function envoyerRefus(Ticket $ticket): bool
    {
        return $this->envoyer($ticket, new DemandeRefuseeMail($ticket), 'refus');
    }

    public function envoyerReponseIncident(Ticket $ticket, bool $estModification): bool
    {
        return $this->envoyer(
            $ticket,
            new ReponseIncidentMail($ticket, $estModification),
            $estModification ? 'modification_reponse_incident' : 'reponse_incident'
        );
    }

    public function envoyerResolution(Ticket $ticket): bool
    {
        return $this->envoyer($ticket, new TicketResoluMail($ticket), 'resolution');
    }

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
