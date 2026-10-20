<?php

namespace App\Http\Controllers;

use App\Mail\RelanceEmpruntMail;
use App\Models\Casque;
use App\Models\Clavier;
use App\Models\Ecran;
use App\Models\Emprunt;
use App\Models\Etudiant;
use App\Models\MiniPc;
use App\Models\PcPortable;
use App\Models\Souris;
use App\Models\Ticket;
use App\Services\NotificationService;
use App\Services\PaginationService;
use App\Services\NotificationTicketService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class EmpruntController extends Controller
{
    // Recupere la liste et les compteurs des emprunts pour afficher la page.
    public function index(Request $request)
    {
        $recherche = $request->get('search', '');
        $statut    = $request->get('statut', '');
        $aujourdhui = now()->startOfDay();
        $dansSeptJours = now()->copy()->addDays(7)->endOfDay();

        $emprunts = Emprunt::with([
                'etudiant.user',
                'pcPortable.materiel',
            ])
            ->when($recherche, function($query) use ($recherche) {
                $query->whereHas('etudiant.user', function($q) use ($recherche) {
                    $q->where('name', 'like', '%'.$recherche.'%');
                });
            })
            ->when($statut, function($query) use ($statut, $aujourdhui, $dansSeptJours) {
                if ($statut === 'echeance_proche') {
                    $query->where('statut', '!=', 'rendu')
                        ->whereBetween('date_fin_prevue', [$aujourdhui, $dansSeptJours]);
                } elseif ($statut === 'en_retard') {
                    $query->where('statut', '!=', 'rendu')
                        ->whereDate('date_fin_prevue', '<', $aujourdhui);
                } elseif ($statut === 'en_cours') {
                    $query->where('statut', '!=', 'rendu')
                        ->whereDate('date_fin_prevue', '>', $dansSeptJours);
                } elseif ($statut === 'rendu') {
                    $query->where('statut', 'rendu');
                }
            });

        $emprunts = app(PaginationService::class)->paginerActivite($emprunts, $request, 'emprunt');

        $total = Emprunt::count();
        $enCours = Emprunt::where('statut', '!=', 'rendu')->count();
        $echeanceProche = Emprunt::where('statut', '!=', 'rendu')
            ->whereBetween('date_fin_prevue', [$aujourdhui, $dansSeptJours])
            ->count();
        $enRetard = Emprunt::where('statut', '!=', 'rendu')
            ->whereDate('date_fin_prevue', '<', $aujourdhui)
            ->count();
        $rendusCeMois = Emprunt::where('statut', 'rendu')
            ->whereMonth('date_retour', now()->month)
            ->whereYear('date_retour', now()->year)
            ->count();

        return view('activite.emprunts', compact(
            'emprunts', 'total', 'enCours', 'echeanceProche', 'enRetard', 'rendusCeMois'
        ));
    }

    // Verifie le formulaire puis enregistre la creation (emprunts).
    public function store(Request $request)
    {
        $request->validate([
            'etudiant_id'      => 'required|exists:etudiants,user_id',
            'materiel_type'    => ['required', Rule::in(['pc-portable'])],
            'materiel_numero_serie' => 'required|string|max:100',
            'date_debut'       => 'required|date',
            'date_fin_prevue'  => 'required|date|after_or_equal:date_debut',
        ]);

        $etudiant = Etudiant::findOrFail($request->integer('etudiant_id'));
        $dateDebut = $this->dateDebutPourEtudiant($etudiant, $request->date_debut);
        $dateFinPrevue = $this->dateFinPrevuePourEtudiant(
            $etudiant,
            $dateDebut,
            $request->date_fin_prevue
        );

        if ($this->etudiantPossedePcEnCours((int) $request->etudiant_id)) {
            return back()
                ->withErrors(['etudiant_id' => __('messages.etudiant_pc_deja_emprunte')])
                ->withInput();
        }

        $materiel = PcPortable::where('numero_serie', $request->materiel_numero_serie)
            ->with('materiel')
            ->first();

        if (! $materiel || ! $materiel->materiel_id) {
            return back()
                ->withErrors(['materiel_numero_serie' => __('messages.materiel_numero_serie_introuvable')])
                ->withInput();
        }

        DB::transaction(function () use ($request, $materiel, $dateDebut, $dateFinPrevue) {
            // Verrouille la fiche centrale avant de verifier et reserver sa disponibilite.
            $materielCentral = $materiel->materiel()->lockForUpdate()->first();

            if (! $materielCentral || $materielCentral->etat !== 'disponible') {
                throw ValidationException::withMessages([
                    'materiel_numero_serie' => __('messages.materiel_indisponible'),
                ]);
            }

            Emprunt::create([
                'etudiant_id'     => $request->etudiant_id,
                'materiel_id'     => $materiel->materiel_id,
                'date_debut'      => $dateDebut,
                'date_fin_prevue' => $dateFinPrevue,
                'statut'          => 'en_cours',
            ]);

            $materielCentral->update(['etat' => 'emprunte']);
        });

        return redirect()->route('emprunts.index')->with('success', __('messages.emprunt_cree'));
    }

    // Cree l'affectation ou l'emprunt depuis le ticket et y enregistre le materiel choisi.
    public function storeDepuisTicket(
        Request $request,
        Ticket $ticket,
        NotificationTicketService $notificationTicketService,
        NotificationService $notificationService
    )
    {
        $ticket->loadMissing(['demandeur.etudiant', 'emprunt', 'demande']);

        if (! $ticket->demande || $ticket->demande->type_demande !== 'emprunt') {
            return back()->withErrors(['ticket' => __('messages.ticket_emprunt_type_invalide')])->withInput();
        }

        if ($ticket->statut !== 'en_cours' || $ticket->technicien_id !== $request->user()->id) {
            return back()->withErrors(['ticket' => __('messages.ticket_traitement_non_autorise')])->withInput();
        }

        if ($ticket->emprunt || $ticket->materiel_id) {
            return back()->withErrors(['ticket' => __('messages.ticket_deja_traite')])->withInput();
        }

        $etudiant = $ticket->demandeur?->etudiant;

        if (! $etudiant) {
            return back()->withErrors(['ticket' => __('messages.ticket_etudiant_introuvable')])->withInput();
        }

        $request->validate([
            'materiel_type' => ['required', Rule::in(['pc-portable'])],
            'materiel_numero_serie' => ['required', 'string', 'max:100'],
            'date_debut' => ['required', 'date'],
            'date_fin_prevue' => ['required', 'date', 'after_or_equal:date_debut'],
        ]);

        $dateDebut = $this->dateDebutPourEtudiant($etudiant, $request->date_debut);
        $dateFinPrevue = $this->dateFinPrevuePourEtudiant(
            $etudiant,
            $dateDebut,
            $request->date_fin_prevue
        );

        if ($this->etudiantPossedePcEnCours($etudiant->user_id)) {
            return back()
                ->withErrors(['materiel_numero_serie' => __('messages.etudiant_pc_deja_emprunte')])
                ->withInput();
        }

        $materiel = PcPortable::where('numero_serie', $request->materiel_numero_serie)
            ->with('materiel')
            ->first();

        if (! $materiel || ! $materiel->materiel_id) {
            return back()
                ->withErrors(['materiel_numero_serie' => __('messages.materiel_numero_serie_introuvable')])
                ->withInput();
        }

        DB::transaction(function () use ($request, $ticket, $etudiant, $materiel, $notificationService, $dateDebut, $dateFinPrevue) {
            $materielCentral = $materiel->materiel()->lockForUpdate()->first();

            if (! $materielCentral || $materielCentral->etat !== 'disponible') {
                throw ValidationException::withMessages([
                    'materiel_numero_serie' => __('messages.materiel_indisponible'),
                ]);
            }

            Emprunt::create([
                'etudiant_id' => $etudiant->user_id,
                'ticket_id' => $ticket->id,
                'materiel_id' => $materiel->materiel_id,
                'date_debut' => $dateDebut,
                'date_fin_prevue' => $dateFinPrevue,
                'statut' => 'en_cours',
            ]);

            $materielCentral->update(['etat' => 'emprunte']);
            $ticket->update([
                'materiel_id' => $materiel->materiel_id,
                'statut' => 'resolu',
            ]);

            $notificationService->notifierDemandeurEmpruntAccepte($ticket);
        });

        $emailEnvoye = $notificationTicketService->envoyerAcceptation($ticket);

        $redirect = redirect()
            ->route('tickets.index')
            ->with('success', __('messages.ticket_emprunt_cree'));

        return $emailEnvoye ? $redirect : $redirect->with('info', __('messages.ticket_email_non_envoye'));
    }

    // Enregistre le retour et rend le materiel disponible. Si la transaction echoue, ces changements sont annules.
    public function validerRetour(Emprunt $emprunt)
    {
        if ($emprunt->statut === 'rendu') {
            return redirect()->route('emprunts.index')->with('info', __('messages.emprunt_deja_rendu'));
        }

        DB::transaction(function () use ($emprunt) {
            $emprunt->update([
                'date_retour' => now(),
                'statut' => 'rendu',
            ]);

            $materiel = $emprunt->pcPortable?->materiel;
            if ($materiel) {
                $materiel->update(['etat' => 'disponible']);
            }
        });

        return redirect()->route('emprunts.index')->with('success', __('messages.emprunt_retour_valide'));
    }

    // Verifie si la prolongation est autorisee, puis change la date de fin.
    public function prolonger(Request $request, Emprunt $emprunt)
    {
        if ($emprunt->statut === 'rendu') {
            return redirect()->route('emprunts.index')->with('info', __('messages.emprunt_deja_rendu_prolongation'));
        }

        $emprunt->loadMissing('etudiant');

        if ($emprunt->etudiant?->type === 'etud_initial') {
            return back()->withErrors([
                'date_fin_prevue' => __('messages.emprunt_initial_prolongation_interdite'),
            ]);
        }

        $request->validate([
            'date_fin_prevue' => 'required|date',
        ]);

        $ancienneDate = $emprunt->date_fin_prevue
            ? Carbon::parse($emprunt->date_fin_prevue)->startOfDay()
            : null;
        $nouvelleDate = Carbon::parse($request->date_fin_prevue)->startOfDay();

        if ($ancienneDate && $nouvelleDate->lessThanOrEqualTo($ancienneDate)) {
            return back()
                ->withErrors(['date_fin_prevue' => __('messages.emprunt_date_prolongation_invalide')])
                ->withInput();
        }

        $emprunt->update([
            'date_fin_prevue' => $nouvelleDate,
            'statut' => 'en_cours',
        ]);

        return redirect()->route('emprunts.index')->with('success', __('messages.emprunt_prolonge'));
    }

    // Envoie un email de rappel quand l'administrateur clique sur Relancer.
    public function relancer(Emprunt $emprunt)
    {
        if ($emprunt->statut === 'rendu') {
            return redirect()->route('emprunts.index')->with('info', __('messages.emprunt_relance_deja_rendu'));
        }

        $emprunt->loadMissing(['etudiant.user', 'pcPortable.materiel']);

        $email = $emprunt->etudiant->user->email ?? null;

        if (! $email) {
            return back()->withErrors(['email' => __('messages.emprunt_relance_email_introuvable')]);
        }

        try {
            Mail::to($email)->send(new RelanceEmpruntMail($emprunt));
        } catch (Throwable $exception) {
            Log::error('Erreur pendant l envoi de la relance emprunt.', [
                'emprunt_id' => $emprunt->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors(['email' => __('messages.emprunt_relance_erreur')]);
        }

        return redirect()->route('emprunts.index')->with('success', __('messages.emprunt_relance_envoyee'));
    }

    // Verifie si la suppression est autorisee, puis supprime l'element (emprunts).
    public function destroy(Emprunt $emprunt)
    {
        DB::transaction(function () use ($emprunt) {
            if ($emprunt->statut !== 'rendu') {
                $materiel = $emprunt->pcPortable?->materiel;
                if ($materiel) {
                    $materiel->update(['etat' => 'disponible']);
                }
            }

            $emprunt->delete();
        });

        return redirect()->route('emprunts.index')->with('success', __('messages.emprunt_supprime'));
    }

    // Renvoie les PC disponibles pour alimenter la selection du formulaire d emprunt.
    public function materielDisponible($type)
    {
        $typesMateriel = [
            'pc-portable' => PcPortable::class,
            'mini-pc'     => MiniPc::class,
            'ecran'       => Ecran::class,
            'clavier'     => Clavier::class,
            'souris'      => Souris::class,
            'casque'      => Casque::class,
        ];

        $classeMateriel = $typesMateriel[$type] ?? null;

        if (! $classeMateriel) {
            return response()->json([]);
        }

        $materiels = $classeMateriel::with('materiel')
            ->whereHas('materiel', function ($query) {
                $query->where('etat', 'disponible');
            })
            ->get()
            ->map(function ($materiel) {
                return [
                    'numero_serie' => $materiel->numero_serie,
                    'nom' => $materiel->nom,
                ];
            })
            ->values();

        return response()->json($materiels);
    }

    // Detecte un PC deja emprunte et non rendu pour cet etudiant.
    private function etudiantPossedePcEnCours(int $etudiantId): bool
    {
        return Emprunt::query()
            ->where('etudiant_id', $etudiantId)
            ->where('statut', '!=', 'rendu')
            ->exists();
    }

    // Impose le retour le jour meme aux etudiants en formation initiale.
    private function dateFinPrevuePourEtudiant(
        Etudiant $etudiant,
        string $dateDebut,
        ?string $dateFinPrevue
    ): string {
        if ($etudiant->type === 'etud_initial') {
            return Carbon::parse($dateDebut)->toDateString();
        }

        return Carbon::parse($dateFinPrevue)->toDateString();
    }

    // Impose la date du jour pour un emprunt en formation initiale.
    private function dateDebutPourEtudiant(Etudiant $etudiant, string $dateDebut): string
    {
        if ($etudiant->type === 'etud_initial') {
            return today()->toDateString();
        }

        return Carbon::parse($dateDebut)->toDateString();
    }

}
