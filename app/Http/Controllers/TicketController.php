<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\NotificationService;
use App\Services\NotificationTicketService;
use App\Services\TicketCreationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function index(Request $request, TicketCreationService $ticketCreationService)
    {
        $recherche = $request->get('search', '');
        $statut    = $request->get('statut', '');
        $estAdmin  = $this->estAdmin($request);
        $estEtudiant = $request->user()->etudiant !== null;
        $materielsIncident = $estAdmin
            ? collect()
            : $ticketCreationService->materielsPourIncident($request->user());
        $peutDeclarerIncident = $materielsIncident->isNotEmpty();
        $typesMaterielIncident = $ticketCreationService->typesMaterielIncident();

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

    public function store(Request $request, TicketCreationService $ticketCreationService)
    {
        abort_if($this->estAdmin($request), 403, 'Les administrateurs ne peuvent pas creer de ticket.');

        $utilisateur = $request->user();
        $donneesValidees = $request->validate($ticketCreationService->reglesCreation($utilisateur));
        $resultat = $ticketCreationService->creer($utilisateur, $donneesValidees);

        if ($resultat['duplique']) {
            return redirect()
                ->route('tickets.index')
                ->with('info', __('messages.ticket_creation_deja_en_cours'));
        }

        $redirect = redirect()
            ->route('tickets.index')
            ->with('success', __('messages.ticket_cree'));

        return $resultat['emails_envoyes']
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

}
