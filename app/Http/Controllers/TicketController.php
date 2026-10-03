<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Models\Emprunt;
use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\NotificationTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class TicketController extends Controller
{
    private const TYPES_MATERIEL_INCIDENT = [
        'pc_portable' => 'PC portable',
        'mini_pc' => 'Mini PC',
        'ecran' => 'Ecran',
        'clavier' => 'Clavier',
        'souris' => 'Souris',
        'casque' => 'Casque',
    ];

    public function index(Request $request)
    {
        $recherche = $request->get('search', '');
        $statut    = $request->get('statut', '');
        $estAdmin  = $this->estAdmin($request);
        $estEtudiant = $request->user()->etudiant !== null;
        $materielsIncident = $estAdmin
            ? collect()
            : $this->materielsPourIncident($request->user());
        $peutDeclarerIncident = $materielsIncident->isNotEmpty();
        $typesMaterielIncident = self::TYPES_MATERIEL_INCIDENT;

        $ticketsUtilisateur = Ticket::query()
            ->when(! $estAdmin, fn ($query) => $query->where('demandeur_id', $request->user()->id));

        $tickets = (clone $ticketsUtilisateur)->with([
            'demandeur.personnel',
            'technicien',
            'incident',
            'demande',
            'materiel.pcPortable',
            'materiel.miniPc',
            'materiel.ecran',
            'materiel.imprimante',
            'materiel.clavier',
            'materiel.souris',
            'materiel.casque',
        ])
            ->when($recherche, function ($query) use ($recherche) {
                $query->where(function ($q) use ($recherche) {
                    $q->where('titre', 'like', '%'.$recherche.'%')
                      ->orWhereHas('demandeur', function ($q2) use ($recherche) {
                          $q2->where('name', 'like', '%'.$recherche.'%');
                      });
                });
            })
            ->when($statut, function ($query) use ($statut) {
                $query->where('statut', $statut);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $total          = (clone $ticketsUtilisateur)->count();
        $ouverts        = (clone $ticketsUtilisateur)->where('statut', 'ouvert')->count();
        $enCours        = (clone $ticketsUtilisateur)->where('statut', 'en_cours')->count();
        $resolus        = (clone $ticketsUtilisateur)->where('statut', 'resolu')->count();
        $refuses        = (clone $ticketsUtilisateur)->where('statut', 'refuse')->count();
        $prioriteHaute  = (clone $ticketsUtilisateur)->where('priorite', 'haute')->count();

        return view('activite.tickets', compact(
            'tickets', 'total', 'ouverts', 'enCours', 'resolus', 'refuses', 'prioriteHaute', 'estAdmin',
            'estEtudiant', 'materielsIncident', 'peutDeclarerIncident', 'typesMaterielIncident'
        ));
    }

    public function store(
        Request $request,
        NotificationTicketService $notificationTicketService,
        NotificationService $notificationService
    )
    {
        abort_if($this->estAdmin($request), 403, 'Les administrateurs ne peuvent pas creer de ticket.');

        $utilisateur = $request->user();
        $typesAutorises = match (true) {
            $utilisateur->etudiant !== null => ['incident', 'emprunt'],
            $utilisateur->personnel !== null => ['incident', 'affectation'],
            default => abort(403, 'Profil utilisateur non autorise.'),
        };

        $donneesValidees = $request->validate([
            'titre'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'type'          => ['required', Rule::in($typesAutorises)],
            'priorite'      => 'required|in:haute,normale',
            'type_materiel' => [
                'required_if:type,incident',
                'nullable',
                Rule::in(array_keys(self::TYPES_MATERIEL_INCIDENT)),
            ],
            'numero_serie'  => 'nullable|string',
        ]);

        $materielId = null;

        if ($request->type === 'incident') {
            $materielsIncident = $this->materielsPourIncident($utilisateur);

            if ($materielsIncident->isEmpty()) {
                return back()
                    ->withErrors(['type' => __('messages.ticket_incident_aucun_materiel')])
                    ->withInput();
            }

            $materielAutorise = $materielsIncident->firstWhere('type', $request->type_materiel);

            if (! $materielAutorise) {
                return back()
                    ->withErrors(['type_materiel' => __('messages.ticket_incident_materiel_non_autorise')])
                    ->withInput();
            }

            $materielId = $materielAutorise['materiel_id'];
        }

        $empreinte = hash('sha256', json_encode([
            'demandeur_id' => $utilisateur->id,
            'type' => $donneesValidees['type'],
            'titre' => mb_strtolower(trim($donneesValidees['titre'])),
            'description' => mb_strtolower(trim($donneesValidees['description'] ?? '')),
            'priorite' => $donneesValidees['priorite'],
            'materiel_id' => $materielId,
        ], JSON_UNESCAPED_UNICODE));
        $cleAntiDoublon = 'ticket-creation:'.$empreinte;

        if (! Cache::add($cleAntiDoublon, true, now()->addSeconds(15))) {
            return redirect()
                ->route('tickets.index')
                ->with('info', __('messages.ticket_creation_deja_en_cours'));
        }

        try {
            $ticket = DB::transaction(function () use ($request, $utilisateur, $materielId, $notificationService) {
                $ticket = Ticket::create([
                    'titre'          => $request->titre,
                    'description'    => $request->description,
                    'priorite'       => $request->priorite,
                    'statut'         => 'ouvert',
                    'demandeur_id'   => $utilisateur->id,
                    'technicien_id'  => null,
                    'materiel_id'    => $materielId,
                ]);

                if ($request->type === 'incident') {
                    $ticket->incident()->create();
                } else {
                    $ticket->demande()->create([
                        'type_demande' => $request->type,
                    ]);
                }

                $notificationService->notifierAdminsNouveauTicket($ticket);

                return $ticket;
            });
        } catch (Throwable $exception) {
            Cache::forget($cleAntiDoublon);

            throw $exception;
        }

        $emailDemandeurEnvoye = $notificationTicketService->envoyerCreation($ticket);
        $emailsAdminsEnvoyes = $notificationTicketService->envoyerCreationAuxAdmins($ticket);

        $redirect = redirect()
            ->route('tickets.index')
            ->with('success', __('messages.ticket_cree'));

        return $emailDemandeurEnvoye && $emailsAdminsEnvoyes
            ? $redirect
            : $redirect->with('info', __('messages.ticket_email_non_envoye'));
    }

    public function assigner(
        Request $request,
        Ticket $ticket,
        NotificationTicketService $notificationTicketService,
        NotificationService $notificationService
    )
    {
        if (in_array($ticket->statut, ['resolu', 'ferme', 'refuse'], true)) {
            return redirect()
                ->route('tickets.index')
                ->withErrors(['ticket' => __('messages.ticket_assignation_interdite')]);
        }

        if ($ticket->technicien_id !== null) {
            return redirect()
                ->route('tickets.index')
                ->withErrors(['ticket' => __('messages.ticket_deja_assigne')]);
        }

        DB::transaction(function () use ($request, $ticket, $notificationService) {
            $ticket->update([
                'technicien_id' => $request->user()->id,
                'statut' => 'en_cours',
            ]);

            $notificationService->notifierDemandeurTicketAssigne($ticket);
        });

        $emailEnvoye = $notificationTicketService->envoyerAssignation($ticket);

        $redirect = redirect()
            ->route('tickets.index')
            ->with('success', __('messages.ticket_assigne'));

        return $emailEnvoye ? $redirect : $redirect->with('info', __('messages.ticket_email_non_envoye'));
    }

    public function resoudre(
        Request $request,
        Ticket $ticket,
        NotificationTicketService $notificationTicketService,
        NotificationService $notificationService
    )
    {
        $ticket->loadMissing('incident');

        if (! $ticket->incident) {
            return redirect()
                ->route('tickets.index')
                ->withErrors(['ticket' => __('messages.ticket_resolution_type_invalide')]);
        }

        if ($ticket->statut !== 'en_cours') {
            return redirect()
                ->route('tickets.index')
                ->withErrors(['ticket' => __('messages.ticket_resolution_statut_invalide')]);
        }

        if ($ticket->technicien_id !== $request->user()->id) {
            return redirect()
                ->route('tickets.index')
                ->withErrors(['ticket' => __('messages.ticket_resolution_non_autorisee')]);
        }

        if (blank($ticket->incident->reponse_admin)) {
            return redirect()
                ->route('tickets.index')
                ->withErrors(['ticket' => __('messages.ticket_resolution_reponse_requise')]);
        }

        DB::transaction(function () use ($ticket, $notificationService) {
            $ticket->update([
                'statut' => 'resolu',
                'date_resolution' => now(),
            ]);

            $notificationService->notifierDemandeurTicketResolu($ticket);
        });

        $emailEnvoye = $notificationTicketService->envoyerResolution($ticket);

        $redirect = redirect()
            ->route('tickets.index')
            ->with('success', __('messages.ticket_resolu'));

        return $emailEnvoye ? $redirect : $redirect->with('info', __('messages.ticket_email_non_envoye'));
    }

    public function refuser(
        Request $request,
        Ticket $ticket,
        NotificationTicketService $notificationTicketService,
        NotificationService $notificationService
    )
    {
        $ticket->loadMissing(['affectation', 'emprunt', 'demande']);

        if (! $ticket->demande || ! in_array($ticket->demande->type_demande, ['affectation', 'emprunt'], true)) {
            return back()->withErrors(['ticket' => __('messages.ticket_refus_type_invalide')])->withInput();
        }

        if ($ticket->statut !== 'en_cours' || $ticket->technicien_id !== $request->user()->id) {
            return back()->withErrors(['ticket' => __('messages.ticket_refus_non_autorise')])->withInput();
        }

        if ($ticket->affectation || $ticket->emprunt || $ticket->materiel_id) {
            return back()->withErrors(['ticket' => __('messages.ticket_deja_traite')])->withInput();
        }

        $donnees = $request->validate([
            'motif_refus' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $dateRefus = now();

        DB::transaction(function () use ($ticket, $donnees, $dateRefus, $notificationService) {
            $ticket->demande->update([
                'motif_refus' => $donnees['motif_refus'],
                'date_refus' => $dateRefus,
            ]);

            $ticket->update([
                'statut' => 'refuse',
            ]);

            $notificationService->notifierDemandeurTicketRefuse($ticket, $donnees['motif_refus']);
        });

        $emailEnvoye = $notificationTicketService->envoyerRefus($ticket);

        $redirect = redirect()
            ->route('tickets.index')
            ->with('success', __('messages.ticket_refuse'));

        return $emailEnvoye ? $redirect : $redirect->with('info', __('messages.ticket_email_non_envoye'));
    }

    public function repondre(
        Request $request,
        Ticket $ticket,
        NotificationTicketService $notificationTicketService,
        NotificationService $notificationService
    )
    {
        $ticket->loadMissing('incident');

        if (! $ticket->incident) {
            return back()->withErrors(['ticket' => __('messages.ticket_reponse_type_invalide')])->withInput();
        }

        if ($ticket->statut !== 'en_cours' || $ticket->technicien_id !== $request->user()->id) {
            return back()->withErrors(['ticket' => __('messages.ticket_reponse_non_autorisee')])->withInput();
        }

        $donnees = $request->validate([
            'reponse_admin' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $estModification = filled($ticket->incident->reponse_admin);
        $dateReponse = now();

        DB::transaction(function () use ($ticket, $donnees, $dateReponse, $estModification, $notificationService) {
            $ticket->incident->update([
                'reponse_admin' => $donnees['reponse_admin'],
                'date_reponse' => $dateReponse,
            ]);

            $notificationService->notifierDemandeurReponseIncident($ticket, $estModification);
        });

        $emailEnvoye = $notificationTicketService->envoyerReponseIncident($ticket, $estModification);
        $message = $estModification
            ? __('messages.ticket_reponse_modifiee')
            : __('messages.ticket_reponse_envoyee');
        $redirect = redirect()->route('tickets.index')->with('success', $message);

        return $emailEnvoye ? $redirect : $redirect->with('info', __('messages.ticket_email_non_envoye'));
    }

    private function estAdmin(Request $request): bool
    {
        $request->user()->loadMissing(['personnel', 'etudiant']);

        return $request->user()->personnel?->role === 'admin';
    }

    private function materielsPourIncident(User $utilisateur)
    {
        $utilisateur->loadMissing(['personnel', 'etudiant']);

        if ($utilisateur->personnel) {
            return Affectation::query()
                ->with([
                    'materiel.pcPortable',
                    'materiel.miniPc',
                    'materiel.ecran',
                    'materiel.imprimante',
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

    private function materielSpecifique($materiel)
    {
        return match ($materiel?->type_materiel) {
            'pc_portable' => $materiel->pcPortable,
            'mini_pc' => $materiel->miniPc,
            'ecran' => $materiel->ecran,
            'imprimante' => $materiel->imprimante,
            'clavier' => $materiel->clavier,
            'souris' => $materiel->souris,
            'casque' => $materiel->casque,
            default => null,
        };
    }

    private function formaterMaterielIncident(int $materielId, string $type, ?string $nom, string $numeroSerie): array
    {
        $libelleType = [
            'pc_portable' => 'PC portable',
            'mini_pc' => 'Mini PC',
            'ecran' => 'Ecran',
            'imprimante' => 'Imprimante',
            'clavier' => 'Clavier',
            'souris' => 'Souris',
            'casque' => 'Casque',
        ][$type] ?? 'Materiel';

        return [
            'materiel_id' => $materielId,
            'type' => $type,
            'numero_serie' => $numeroSerie,
            'libelle' => $libelleType.' - '.($nom ?: 'Sans nom').' ('.$numeroSerie.')',
        ];
    }
}
