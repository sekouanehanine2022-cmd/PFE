<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\Cable;
use App\Models\Emprunt;
use App\Models\Notification;
use App\Models\Personnel;
use App\Models\Ticket;

class NotificationService
{
    // Recoit et garde les objets ou les valeurs utilises par les autres methodes.
    public function __construct(private readonly MaterielService $materielService)
    {
    }

    // Cree une notification interne de nouveau ticket pour chaque administrateur.
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

    // Previent le demandeur que son ticket est en cours de traitement.
    public function notifierDemandeurTicketAssigne(Ticket $ticket): void
    {
        $this->notifierDemandeur(
            $ticket,
            'ticket_assigne',
            __('messages.notification_ticket_assigne', ['titre' => $ticket->titre])
        );
    }

    // Previent le demandeur que sa demande d affectation est acceptee.
    public function notifierDemandeurAffectationAcceptee(Ticket $ticket): void
    {
        $this->notifierDemandeur(
            $ticket,
            'affectation_acceptee',
            __('messages.notification_affectation_acceptee', ['titre' => $ticket->titre])
        );
    }

    // Previent l etudiant que sa demande d emprunt est acceptee.
    public function notifierDemandeurEmpruntAccepte(Ticket $ticket): void
    {
        $this->notifierDemandeur(
            $ticket,
            'emprunt_accepte',
            __('messages.notification_emprunt_accepte', ['titre' => $ticket->titre])
        );
    }

    // Previent le demandeur du refus avec son motif.
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

    // Signale une nouvelle reponse ou sa modification au demandeur de l incident.
    public function notifierDemandeurReponseIncident(Ticket $ticket, bool $estModification): void
    {
        $type = $estModification ? 'reponse_incident_modifiee' : 'reponse_incident';
        $message = $estModification
            ? __('messages.notification_reponse_incident_modifiee', ['titre' => $ticket->titre])
            : __('messages.notification_reponse_incident', ['titre' => $ticket->titre]);

        $this->notifierDemandeur($ticket, $type, $message);
    }

    // Previent le demandeur de la resolution de son ticket.
    public function notifierDemandeurTicketResolu(Ticket $ticket): void
    {
        $this->notifierDemandeur(
            $ticket,
            'ticket_resolu',
            __('messages.notification_ticket_resolu', ['titre' => $ticket->titre])
        );
    }

    // Alerte chaque administrateur une seule fois par affectation et par date de retour depassee.
    public function notifierAdminsRetardAffectation(Affectation $affectation): void
    {
        $affectation->loadMissing(array_merge(
            ['personnel.user'],
            $this->materielService->relationsAffectation()
        ));

        $personne = $affectation->personnel?->user?->name ?? 'un collaborateur';
        $materiel = $this->materielService->libelleAvecNumero($affectation->materiel);
        $dateFin = $affectation->date_fin?->format('d/m/Y') ?? '-';

        foreach ($this->administrateurIds() as $administrateurId) {
            Notification::firstOrCreate(
                [
                    'user_id' => $administrateurId,
                    'affectation_id' => $affectation->id,
                    // La date de fin permet de creer une nouvelle alerte si le retour a ete prolonge.
                    'type' => 'retard_affectation',
                    'date_echeance' => $affectation->date_fin->toDateString(),
                ],
                [
                    'ticket_id' => null,
                    'emprunt_id' => null,
                    'message' => __('messages.notification_retard_affectation', [
                        'materiel' => $materiel,
                        'personne' => $personne,
                        'date' => $dateFin,
                    ]),
                    'read_at' => null,
                ]
            );
        }
    }

    // Alerte chaque administrateur une seule fois par emprunt et par date de retour depassee.
    public function notifierAdminsRetardEmprunt(Emprunt $emprunt): void
    {
        $emprunt->loadMissing(['etudiant.user', 'pcPortable.materiel']);

        $personne = $emprunt->etudiant?->user?->name ?? 'un etudiant';
        $nomMateriel = $emprunt->pcPortable?->materiel?->nom ?? 'PC portable';
        $numeroSerie = $emprunt->pcPortable?->numero_serie ?? '-';
        $materiel = trim($nomMateriel.' ('.$numeroSerie.')');
        $dateFin = $emprunt->date_fin_prevue?->format('d/m/Y') ?? '-';

        foreach ($this->administrateurIds() as $administrateurId) {
            Notification::firstOrCreate(
                [
                    'user_id' => $administrateurId,
                    'emprunt_id' => $emprunt->id,
                    'type' => 'retard_emprunt',
                    'date_echeance' => $emprunt->date_fin_prevue->toDateString(),
                ],
                [
                    'ticket_id' => null,
                    'affectation_id' => null,
                    'message' => __('messages.notification_retard_emprunt', [
                        'materiel' => $materiel,
                        'personne' => $personne,
                        'date' => $dateFin,
                    ]),
                    'read_at' => null,
                ]
            );
        }
    }

    // Cree ou actualise l alerte de stock bas ou de rupture pour chaque administrateur.
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

    // Ajoute ou met a jour une notification dans l'application pour la personne qui a cree le ticket.
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

    // Retrouve les identifiants des comptes ayant le role administrateur.
    private function administrateurIds()
    {
        return Personnel::query()
            ->where('role', 'admin')
            ->pluck('user_id')
            ->unique();
    }

    // Cree le rappel pour cet utilisateur, ce delai et cette date de fin s'il n'existe pas deja.
    public function notifierRelance(Affectation|Emprunt $operation, int $palier): Notification
    {
        $estAffectation = $operation instanceof Affectation;
        $date = $estAffectation ? $operation->date_fin : $operation->date_fin_prevue;
        $materiel = $this->materielService->libelleAvecNumero($operation->materiel);

        return Notification::firstOrCreate([
            'user_id' => $estAffectation ? $operation->personnel_id : $operation->etudiant_id,
            $estAffectation ? 'affectation_id' : 'emprunt_id' => $operation->id,
            'type' => ($estAffectation ? 'relance_affectation_j' : 'relance_emprunt_j').$palier,
            'date_echeance' => $date->toDateString(),
        ], [
            'message' => __('messages.notification_relance_retour', [
                'materiel' => $materiel,
                'delai' => $palier === 1 ? 'demain' : 'dans '.$palier.' jours',
                'date' => $date->format('d/m/Y'),
            ]),
        ]);
    }

}
