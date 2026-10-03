<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\Cable;
use App\Models\Emprunt;
use App\Models\Materiel;
use App\Models\Notification;
use App\Models\Personnel;
use App\Models\Ticket;

class NotificationService
{
    public function notifierAdminsNouveauTicket(Ticket $ticket): void
    {
        $ticket->loadMissing('demandeur');

        $administrateurIds = Personnel::query()
            ->where('role', 'admin')
            ->pluck('user_id')
            ->unique();

        foreach ($administrateurIds as $administrateurId) {
            Notification::updateOrCreate(
                [
                    'user_id' => $administrateurId,
                    'ticket_id' => $ticket->id,
                    'type' => 'nouveau_ticket',
                ],
                [
                    'message' => __('messages.notification_nouveau_ticket', [
                        'demandeur' => $ticket->demandeur?->name ?? 'Un utilisateur',
                        'titre' => $ticket->titre,
                    ]),
                    'read_at' => null,
                ]
            );
        }
    }

    public function notifierDemandeurTicketAssigne(Ticket $ticket): void
    {
        $this->notifierDemandeur(
            $ticket,
            'ticket_assigne',
            __('messages.notification_ticket_assigne', ['titre' => $ticket->titre])
        );
    }

    public function notifierDemandeurAffectationAcceptee(Ticket $ticket): void
    {
        $this->notifierDemandeur(
            $ticket,
            'affectation_acceptee',
            __('messages.notification_affectation_acceptee', ['titre' => $ticket->titre])
        );
    }

    public function notifierDemandeurEmpruntAccepte(Ticket $ticket): void
    {
        $this->notifierDemandeur(
            $ticket,
            'emprunt_accepte',
            __('messages.notification_emprunt_accepte', ['titre' => $ticket->titre])
        );
    }

    public function notifierDemandeurTicketRefuse(Ticket $ticket, string $motif): void
    {
        $this->notifierDemandeur(
            $ticket,
            'ticket_refuse',
            __('messages.notification_ticket_refuse', [
                'titre' => $ticket->titre,
                'motif' => $motif,
            ])
        );
    }

    public function notifierDemandeurReponseIncident(Ticket $ticket, bool $estModification): void
    {
        $type = $estModification ? 'reponse_incident_modifiee' : 'reponse_incident';
        $message = $estModification
            ? __('messages.notification_reponse_incident_modifiee', ['titre' => $ticket->titre])
            : __('messages.notification_reponse_incident', ['titre' => $ticket->titre]);

        $this->notifierDemandeur($ticket, $type, $message);
    }

    public function notifierDemandeurTicketResolu(Ticket $ticket): void
    {
        $this->notifierDemandeur(
            $ticket,
            'ticket_resolu',
            __('messages.notification_ticket_resolu', ['titre' => $ticket->titre])
        );
    }

    public function notifierAdminsEcheanceAffectation(Affectation $affectation): void
    {
        $affectation->loadMissing([
            'personnel.user',
            'materiel.pcPortable',
            'materiel.miniPc',
            'materiel.ecran',
            'materiel.clavier',
            'materiel.souris',
            'materiel.casque',
        ]);

        $personne = $affectation->personnel?->user?->name ?? 'un collaborateur';
        $materiel = $this->libelleMateriel($affectation->materiel);
        $dateFin = $affectation->date_fin?->format('d/m/Y') ?? '-';

        foreach ($this->administrateurIds() as $administrateurId) {
            Notification::updateOrCreate(
                [
                    'user_id' => $administrateurId,
                    'affectation_id' => $affectation->id,
                    'type' => 'echeance_affectation_7_jours',
                ],
                [
                    'ticket_id' => null,
                    'emprunt_id' => null,
                    'message' => __('messages.notification_echeance_affectation', [
                        'materiel' => $materiel,
                        'personne' => $personne,
                        'date' => $dateFin,
                    ]),
                    'read_at' => null,
                ]
            );
        }
    }

    public function notifierAdminsEcheanceEmprunt(Emprunt $emprunt): void
    {
        $emprunt->loadMissing(['etudiant.user', 'pcPortable.materiel']);

        $personne = $emprunt->etudiant?->user?->name ?? 'un etudiant';
        $nomMateriel = $emprunt->pcPortable?->materiel?->nom ?? 'PC portable';
        $numeroSerie = $emprunt->pc_numero_serie;
        $materiel = trim($nomMateriel.' ('.$numeroSerie.')');
        $dateFin = $emprunt->date_fin_prevue?->format('d/m/Y') ?? '-';

        foreach ($this->administrateurIds() as $administrateurId) {
            Notification::updateOrCreate(
                [
                    'user_id' => $administrateurId,
                    'emprunt_id' => $emprunt->id,
                    'type' => 'echeance_emprunt_7_jours',
                ],
                [
                    'ticket_id' => null,
                    'affectation_id' => null,
                    'message' => __('messages.notification_echeance_emprunt', [
                        'materiel' => $materiel,
                        'personne' => $personne,
                        'date' => $dateFin,
                    ]),
                    'read_at' => null,
                ]
            );
        }
    }

    public function notifierAdminsStockCableBas(Cable $cable): void
    {
        $message = $cable->quantite_disponible === 0
            ? __('messages.notification_stock_cable_rupture', [
                'type' => $cable->type_cable,
                'reference' => $cable->reference,
            ])
            : __('messages.notification_stock_cable_bas', [
                'quantite' => $cable->quantite_disponible,
                'type' => $cable->type_cable,
                'reference' => $cable->reference,
                'seuil' => $cable->seuil_alerte,
            ]);

        foreach ($this->administrateurIds() as $administrateurId) {
            Notification::updateOrCreate(
                [
                    'user_id' => $administrateurId,
                    'cable_id' => $cable->id,
                    'type' => 'stock_cable_bas',
                ],
                [
                    'ticket_id' => null,
                    'affectation_id' => null,
                    'emprunt_id' => null,
                    'message' => $message,
                    'read_at' => null,
                ]
            );
        }
    }

    private function notifierDemandeur(Ticket $ticket, string $type, string $message): void
    {
        $ticket->loadMissing('demandeur');

        if (! $ticket->demandeur) {
            return;
        }

        Notification::updateOrCreate(
            [
                'user_id' => $ticket->demandeur->id,
                'ticket_id' => $ticket->id,
                'type' => $type,
            ],
            [
                'message' => $message,
                'read_at' => null,
            ]
        );
    }

    private function administrateurIds()
    {
        return Personnel::query()
            ->where('role', 'admin')
            ->pluck('user_id')
            ->unique();
    }

    private function libelleMateriel(?Materiel $materiel): string
    {
        if (! $materiel) {
            return 'Materiel';
        }

        $materielSpecifique = match ($materiel->type_materiel) {
            'pc_portable' => $materiel->pcPortable,
            'mini_pc' => $materiel->miniPc,
            'ecran' => $materiel->ecran,
            'clavier' => $materiel->clavier,
            'souris' => $materiel->souris,
            'casque' => $materiel->casque,
            default => null,
        };

        $numeroSerie = $materielSpecifique?->numero_serie;

        return $numeroSerie
            ? $materiel->nom.' ('.$numeroSerie.')'
            : $materiel->nom;
    }
}
