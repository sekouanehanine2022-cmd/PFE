<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\TicketCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MesTicketsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tickets = Ticket::query()
            ->with(['incident', 'demande'])
            ->where('demandeur_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (Ticket $ticket) => $this->formaterTicket($ticket));

        return response()->json([
            'total' => $tickets->count(),
            'tickets' => $tickets->values(),
        ]);
    }

    public function options(Request $request, TicketCreationService $ticketCreationService): JsonResponse
    {
        $utilisateur = $request->user();
        $materiels = $ticketCreationService->materielsPourIncident($utilisateur);
        $types = collect($ticketCreationService->typesAutorises($utilisateur))
            ->when($materiels->isEmpty(), fn ($collection) => $collection->reject(fn ($type) => $type === 'incident'))
            ->map(fn ($type) => [
                'value' => $type,
                'label' => $this->libelleType($type),
            ])
            ->values();

        return response()->json([
            'types' => $types,
            'priorites' => [
                ['value' => 'normale', 'label' => 'Normale'],
                ['value' => 'haute', 'label' => 'Haute'],
            ],
            'peut_declarer_incident' => $materiels->isNotEmpty(),
            'materiels' => $materiels->map(fn ($materiel) => [
                'type' => $materiel['type'],
                'numero_serie' => $materiel['numero_serie'],
                'libelle' => $materiel['libelle'],
            ])->values(),
        ]);
    }

    public function store(Request $request, TicketCreationService $ticketCreationService): JsonResponse
    {
        $utilisateur = $request->user();
        $donnees = $request->validate($ticketCreationService->reglesCreation($utilisateur));
        $resultat = $ticketCreationService->creer($utilisateur, $donnees);

        if ($resultat['duplique']) {
            return response()->json([
                'message' => __('messages.ticket_creation_deja_en_cours'),
            ], Response::HTTP_CONFLICT);
        }

        $ticket = $resultat['ticket']->load(['incident', 'demande']);

        return response()->json([
            'message' => __('messages.ticket_cree'),
            'email_envoye' => $resultat['emails_envoyes'],
            'ticket' => $this->formaterTicket($ticket),
        ], Response::HTTP_CREATED);
    }

    private function formaterTicket(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'type' => $ticket->incident
                ? 'incident'
                : $ticket->demande?->type_demande,
            'titre' => $ticket->titre,
            'description' => $ticket->description,
            'priorite' => $ticket->priorite,
            'statut' => $ticket->statut,
            'reponse_admin' => $ticket->incident?->reponse_admin,
            'motif_refus' => $ticket->demande?->motif_refus,
            'date_creation' => $ticket->created_at?->toIso8601String(),
            'date_reponse' => $ticket->incident?->date_reponse?->toIso8601String(),
            'date_refus' => $ticket->demande?->date_refus?->toIso8601String(),
            'date_resolution' => $ticket->date_resolution?->toIso8601String(),
        ];
    }

    private function libelleType(string $type): string
    {
        return match ($type) {
            'affectation' => 'Demande d affectation',
            'emprunt' => 'Demande d emprunt',
            default => 'Incident',
        };
    }
}
