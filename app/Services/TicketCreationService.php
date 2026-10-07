<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\Emprunt;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class TicketCreationService
{
    private const TYPES_MATERIEL_INCIDENT = [
        'pc_portable' => 'PC portable',
        'mini_pc' => 'Mini PC',
        'ecran' => 'Ecran',
        'clavier' => 'Clavier',
        'souris' => 'Souris',
        'casque' => 'Casque',
    ];

    public function __construct(
        private readonly NotificationTicketService $notificationTicketService,
        private readonly NotificationService $notificationService
    ) {
    }

    public function reglesCreation(User $utilisateur): array
    {
        return [
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in($this->typesAutorises($utilisateur))],
            'priorite' => ['required', Rule::in(['haute', 'normale'])],
            'type_materiel' => [
                'required_if:type,incident',
                'nullable',
                Rule::in(array_keys(self::TYPES_MATERIEL_INCIDENT)),
            ],
            'numero_serie' => ['nullable', 'string'],
        ];
    }

    public function typesAutorises(User $utilisateur): array
    {
        $utilisateur->loadMissing(['personnel', 'etudiant']);

        return match (true) {
            $utilisateur->etudiant !== null => ['incident', 'emprunt'],
            $utilisateur->personnel !== null && $utilisateur->personnel->role !== 'admin' => ['incident', 'affectation'],
            default => abort(403, 'Profil utilisateur non autorise.'),
        };
    }

    public function typesMaterielIncident(): array
    {
        return self::TYPES_MATERIEL_INCIDENT;
    }

    public function materielsPourIncident(User $utilisateur): Collection
    {
        $utilisateur->loadMissing(['personnel', 'etudiant']);

        if ($utilisateur->personnel) {
            return Affectation::query()
                ->with([
                    'materiel.pcPortable',
                    'materiel.miniPc',
                    'materiel.ecran',
                    'materiel.clavier',
                    'materiel.souris',
                    'materiel.casque',
                ])
                ->where('personnel_id', $utilisateur->personnel->id)
                ->where('statut', 'active')
                ->get()
                ->map(function (Affectation $affectation) {
                    $materiel = $affectation->materiel;
                    $materielSpecifique = $this->materielSpecifique($materiel);

                    if (
                        ! $materiel
                        || ! $materielSpecifique
                        || ! array_key_exists($materiel->type_materiel, self::TYPES_MATERIEL_INCIDENT)
                    ) {
                        return null;
                    }

                    return $this->formaterMaterielIncident(
                        $materiel->id,
                        $materiel->type_materiel,
                        $materiel->nom,
                        $materielSpecifique->numero_serie
                    );
                })
                ->filter()
                ->unique('type')
                ->values();
        }

        if ($utilisateur->etudiant) {
            return Emprunt::query()
                ->with('pcPortable.materiel')
                ->where('etudiant_id', $utilisateur->etudiant->id)
                ->where('statut', '!=', 'rendu')
                ->get()
                ->map(function (Emprunt $emprunt) {
                    $pcPortable = $emprunt->pcPortable;
                    $materiel = $pcPortable?->materiel;

                    if (! $pcPortable || ! $materiel) {
                        return null;
                    }

                    return $this->formaterMaterielIncident(
                        $materiel->id,
                        'pc_portable',
                        $materiel->nom,
                        $pcPortable->numero_serie
                    );
                })
                ->filter()
                ->unique('type')
                ->values();
        }

        return collect();
    }

    public function creer(User $utilisateur, array $donnees): array
    {
        $materielId = $this->resoudreMaterielIncident($utilisateur, $donnees);
        $cleAntiDoublon = $this->cleAntiDoublon($utilisateur, $donnees, $materielId);

        if (! Cache::add($cleAntiDoublon, true, now()->addSeconds(15))) {
            return [
                'duplique' => true,
                'ticket' => null,
                'emails_envoyes' => false,
            ];
        }

        try {
            $ticket = DB::transaction(function () use ($utilisateur, $donnees, $materielId) {
                $ticket = Ticket::create([
                    'titre' => $donnees['titre'],
                    'description' => $donnees['description'] ?? null,
                    'priorite' => $donnees['priorite'],
                    'statut' => 'ouvert',
                    'demandeur_id' => $utilisateur->id,
                    'technicien_id' => null,
                    'materiel_id' => $materielId,
                ]);

                if ($donnees['type'] === 'incident') {
                    $ticket->incident()->create();
                } else {
                    $ticket->demande()->create([
                        'type_demande' => $donnees['type'],
                    ]);
                }

                $this->notificationService->notifierAdminsNouveauTicket($ticket);

                return $ticket;
            });
        } catch (Throwable $exception) {
            Cache::forget($cleAntiDoublon);

            throw $exception;
        }

        $emailDemandeurEnvoye = $this->notificationTicketService->envoyerCreation($ticket);
        $emailsAdminsEnvoyes = $this->notificationTicketService->envoyerCreationAuxAdmins($ticket);

        return [
            'duplique' => false,
            'ticket' => $ticket,
            'emails_envoyes' => $emailDemandeurEnvoye && $emailsAdminsEnvoyes,
        ];
    }

    private function resoudreMaterielIncident(User $utilisateur, array $donnees): ?int
    {
        if ($donnees['type'] !== 'incident') {
            return null;
        }

        $materiels = $this->materielsPourIncident($utilisateur);

        if ($materiels->isEmpty()) {
            throw ValidationException::withMessages([
                'type' => [__('messages.ticket_incident_aucun_materiel')],
            ]);
        }

        $materiel = $materiels->firstWhere('type', $donnees['type_materiel'] ?? null);

        if (! $materiel) {
            throw ValidationException::withMessages([
                'type_materiel' => [__('messages.ticket_incident_materiel_non_autorise')],
            ]);
        }

        return $materiel['materiel_id'];
    }

    private function cleAntiDoublon(User $utilisateur, array $donnees, ?int $materielId): string
    {
        $empreinte = hash('sha256', json_encode([
            'demandeur_id' => $utilisateur->id,
            'type' => $donnees['type'],
            'titre' => mb_strtolower(trim($donnees['titre'])),
            'description' => mb_strtolower(trim($donnees['description'] ?? '')),
            'priorite' => $donnees['priorite'],
            'materiel_id' => $materielId,
        ], JSON_UNESCAPED_UNICODE));

        return 'ticket-creation:'.$empreinte;
    }

    private function materielSpecifique($materiel): mixed
    {
        return match ($materiel?->type_materiel) {
            'pc_portable' => $materiel->pcPortable,
            'mini_pc' => $materiel->miniPc,
            'ecran' => $materiel->ecran,
            'clavier' => $materiel->clavier,
            'souris' => $materiel->souris,
            'casque' => $materiel->casque,
            default => null,
        };
    }

    private function formaterMaterielIncident(int $materielId, string $type, ?string $nom, string $numeroSerie): array
    {
        $libelleType = self::TYPES_MATERIEL_INCIDENT[$type] ?? 'Materiel';

        return [
            'materiel_id' => $materielId,
            'type' => $type,
            'numero_serie' => $numeroSerie,
            'libelle' => $libelleType.' - '.($nom ?: 'Sans nom').' ('.$numeroSerie.')',
        ];
    }
}
