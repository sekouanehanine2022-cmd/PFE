<?php

namespace App\Http\Controllers;

use App\Mail\RelanceAffectationMail;
use App\Models\Affectation;
use App\Models\Casque;
use App\Models\Clavier;
use App\Models\Ecran;
use App\Models\MiniPc;
use App\Models\PcPortable;
use App\Models\Personnel;
use App\Models\Souris;
use App\Models\Ticket;
use App\Services\AffectationService;
use App\Services\NotificationService;
use App\Services\NotificationTicketService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class AffectationController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->get('search', '');
        $statut = $request->get('statut', '');
        $aujourdhui = now()->toDateString();
        $dansSeptJours = now()->addDays(7)->toDateString();

        $affectations = Affectation::with([
                'personnel.user',
                'materiel.pcPortable',
                'materiel.miniPc',
                'materiel.ecran',
                'materiel.clavier',
                'materiel.souris',
                'materiel.casque',
            ])
            ->when($recherche, function ($query) use ($recherche) {
                $query->whereHas('personnel.user', function ($q) use ($recherche) {
                    $q->where('name', 'like', '%' . $recherche . '%');
                });
            })
            ->when($statut, function ($query) use ($statut, $aujourdhui, $dansSeptJours) {
                match ($statut) {
                    'active' => $query->where('statut', 'active'),
                    'en_cours' => $query->where('statut', 'active')
                        ->where(function ($q) use ($dansSeptJours) {
                            $q->whereNull('date_fin')
                                ->orWhereDate('date_fin', '>', $dansSeptJours);
                        }),
                    'echeance_proche' => $query->where('statut', 'active')
                        ->whereBetween('date_fin', [$aujourdhui, $dansSeptJours]),
                    'en_retard' => $query->where('statut', 'active')
                        ->whereDate('date_fin', '<', $aujourdhui),
                    'rendu' => $query->where('statut', 'rendu'),
                    default => null,
                };
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $total = Affectation::count();
        $actives = Affectation::where('statut', 'active')->count();
        $enCours = Affectation::where('statut', 'active')
            ->where(function ($query) use ($dansSeptJours) {
                $query->whereNull('date_fin')
                    ->orWhereDate('date_fin', '>', $dansSeptJours);
            })
            ->count();
        $echeanceProche = Affectation::where('statut', 'active')
            ->whereBetween('date_fin', [$aujourdhui, $dansSeptJours])
            ->count();
        $enRetard = Affectation::where('statut', 'active')
            ->whereDate('date_fin', '<', $aujourdhui)
            ->count();
        $rendus = Affectation::where('statut', 'rendu')->count();

        return view('activite.affectations', compact(
            'affectations',
            'total',
            'actives',
            'enCours',
            'echeanceProche',
            'enRetard',
            'rendus'
        ));
    }

    public function store(Request $request, AffectationService $affectationService)
    {
        $personnel = $this->validerPersonnelEtDates($request);

        $request->validate([
            'materiel_type' => 'required|string',
            'materiel_numero_serie' => 'required|string|max:100',
        ]);

        $classeMateriel = $this->classeMateriel($request->materiel_type);

        if (! $classeMateriel) {
            return back()->withErrors(['materiel_type' => __('messages.materiel_type_invalide')]);
        }

        if ($affectationService->existeAffectationActivePourType($request->personnel_id, $classeMateriel)) {
            $libelleType = $affectationService->libelleType($classeMateriel);

            return back()
                ->withErrors(['personnel_id' => __('messages.collaborateur_deja_type_affecte', ['type' => $libelleType])])
                ->withInput();
        }

        $materiel = $classeMateriel::where('numero_serie', $request->materiel_numero_serie)
            ->with('materiel')
            ->first();

        if (! $materiel || ! $materiel->materiel_id) {
            return back()
                ->withErrors(['materiel_numero_serie' => __('messages.materiel_numero_serie_introuvable')])
                ->withInput();
        }

        DB::transaction(function () use ($request, $materiel, $personnel) {
            Affectation::create([
                'personnel_id' => $request->personnel_id,
                'materiel_id' => $materiel->materiel_id,
                'date_debut' => $request->date_debut,
                'date_fin' => $personnel->type_contrat === 'cdi' ? null : $request->date_fin,
                'statut' => 'active',
            ]);

            $this->mettreAJourEtatMateriel($materiel, 'affecte');
        });

        return redirect()->route('affectations.index')->with('success', __('messages.affectation_creee'));
    }

    public function storeDepuisTicket(
        Request $request,
        Ticket $ticket,
        AffectationService $affectationService,
        NotificationTicketService $notificationTicketService,
        NotificationService $notificationService
    ) {
        $ticket->loadMissing(['demandeur.personnel', 'affectation', 'demande']);

        if (! $ticket->demande || $ticket->demande->type_demande !== 'affectation') {
            return back()->withErrors(['ticket' => __('messages.ticket_affectation_type_invalide')])->withInput();
        }

        if ($ticket->statut !== 'en_cours' || $ticket->technicien_id !== $request->user()->id) {
            return back()->withErrors(['ticket' => __('messages.ticket_traitement_non_autorise')])->withInput();
        }

        if ($ticket->affectation || $ticket->materiel_id) {
            return back()->withErrors(['ticket' => __('messages.ticket_deja_traite')])->withInput();
        }

        $personnel = $ticket->demandeur?->personnel;

        if (! $personnel) {
            return back()->withErrors(['ticket' => __('messages.ticket_personnel_introuvable')])->withInput();
        }

        $request->validate([
            'materiel_type' => ['required', 'string'],
            'materiel_numero_serie' => ['required', 'string', 'max:100'],
            'date_debut' => ['required', 'date'],
            'date_fin' => [
                Rule::requiredIf($personnel->type_contrat !== 'cdi'),
                'nullable',
                'date',
                'after_or_equal:date_debut',
            ],
        ]);

        $classeMateriel = $this->classeMateriel($request->materiel_type);

        if (! $classeMateriel) {
            return back()->withErrors(['materiel_type' => __('messages.materiel_type_invalide')])->withInput();
        }

        if ($affectationService->existeAffectationActivePourType($personnel->id, $classeMateriel)) {
            return back()->withErrors([
                'materiel_type' => __('messages.collaborateur_deja_type_affecte', [
                    'type' => $affectationService->libelleType($classeMateriel),
                ]),
            ])->withInput();
        }

        $materiel = $classeMateriel::where('numero_serie', $request->materiel_numero_serie)
            ->with('materiel')
            ->first();

        if (! $materiel || ! $materiel->materiel_id) {
            return back()
                ->withErrors(['materiel_numero_serie' => __('messages.materiel_numero_serie_introuvable')])
                ->withInput();
        }

        DB::transaction(function () use ($request, $ticket, $personnel, $materiel, $notificationService) {
            $materielCentral = $materiel->materiel()->lockForUpdate()->first();

            if (! $materielCentral || $materielCentral->etat !== 'disponible') {
                throw ValidationException::withMessages([
                    'materiel_numero_serie' => __('messages.materiel_indisponible'),
                ]);
            }

            Affectation::create([
                'personnel_id' => $personnel->id,
                'ticket_id' => $ticket->id,
                'materiel_id' => $materiel->materiel_id,
                'date_debut' => $request->date_debut,
                'date_fin' => $personnel->type_contrat === 'cdi' ? null : $request->date_fin,
                'statut' => 'active',
            ]);

            $materielCentral->update(['etat' => 'affecte']);
            $ticket->update([
                'materiel_id' => $materiel->materiel_id,
                'statut' => 'resolu',
            ]);

            $notificationService->notifierDemandeurAffectationAcceptee($ticket);
        });

        $emailEnvoye = $notificationTicketService->envoyerAcceptation($ticket);

        $redirect = redirect()
            ->route('tickets.index')
            ->with('success', __('messages.ticket_affectation_creee'));

        return $emailEnvoye ? $redirect : $redirect->with('info', __('messages.ticket_email_non_envoye'));
    }

    public function validerRetour(Affectation $affectation)
    {
        if ($affectation->statut === 'rendu') {
            return redirect()
                ->route('affectations.index')
                ->with('info', __('messages.affectation_deja_rendue'));
        }

        DB::transaction(function () use ($affectation) {
            $affectation->update([
                'statut' => 'rendu',
                'date_retour' => now(),
            ]);

            $materiel = $affectation->materiel;

            if ($materiel) {
                $this->mettreAJourEtatMateriel($materiel, 'disponible');
            }
        });

        return redirect()->route('affectations.index')->with('success', __('messages.affectation_retour_valide'));
    }

    public function prolonger(Request $request, Affectation $affectation)
    {
        if ($affectation->statut === 'rendu') {
            return redirect()->route('affectations.index')->with('info', __('messages.affectation_deja_rendue_prolongation'));
        }

        if (! $affectation->date_fin) {
            return back()->withErrors(['date_fin' => __('messages.affectation_sans_date_fin')]);
        }

        $request->validate(['date_fin' => ['required', 'date']]);

        $ancienneDate = Carbon::parse($affectation->date_fin)->startOfDay();
        $nouvelleDate = Carbon::parse($request->date_fin)->startOfDay();

        if ($nouvelleDate->lessThanOrEqualTo($ancienneDate)) {
            return back()->withErrors(['date_fin' => __('messages.affectation_date_prolongation_invalide')])->withInput();
        }

        $affectation->update(['date_fin' => $nouvelleDate]);

        return redirect()->route('affectations.index')->with('success', __('messages.affectation_prolongee'));
    }

    public function relancer(Affectation $affectation)
    {
        if ($affectation->statut === 'rendu') {
            return redirect()->route('affectations.index')->with('info', __('messages.affectation_relance_deja_rendue'));
        }

        if (! $affectation->date_fin) {
            return back()->withErrors(['date_fin' => __('messages.affectation_sans_date_fin')]);
        }

        $affectation->loadMissing(['personnel.user', 'materiel']);
        $email = $affectation->personnel->user->email ?? null;

        if (! $email) {
            return back()->withErrors(['email' => __('messages.affectation_relance_email_introuvable')]);
        }

        try {
            Mail::to($email)->send(new RelanceAffectationMail($affectation));
        } catch (Throwable $exception) {
            Log::error('Erreur pendant l envoi de la relance affectation.', [
                'affectation_id' => $affectation->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors(['email' => __('messages.affectation_relance_erreur')]);
        }

        return redirect()->route('affectations.index')->with('success', __('messages.affectation_relance_envoyee'));
    }

    public function destroy(Affectation $affectation)
    {
        if ($affectation->statut === 'active') {
            return redirect()
                ->route('affectations.index')
                ->withErrors(['affectation' => __('messages.affectation_active_suppression_interdite')]);
        }

        $affectation->delete();

        return redirect()->route('affectations.index')->with('success', __('messages.affectation_supprimee'));
    }

    private function classeMateriel(string $type): ?string
    {
        $typesMateriel = [
            'pc-portable' => PcPortable::class,
            'mini-pc' => MiniPc::class,
            'ecran' => Ecran::class,
            'clavier' => Clavier::class,
            'souris' => Souris::class,
            'casque' => Casque::class,
        ];

        return $typesMateriel[$type] ?? null;
    }

    private function validerPersonnelEtDates(Request $request): Personnel
    {
        $request->validate(['personnel_id' => ['required', 'exists:personnels,id']]);

        $personnel = Personnel::findOrFail($request->personnel_id);

        $request->validate([
            'date_debut' => ['required', 'date'],
            'date_fin' => [Rule::requiredIf($personnel->type_contrat !== 'cdi'), 'nullable', 'date', 'after_or_equal:date_debut'],
        ]);

        return $personnel;
    }

    private function mettreAJourEtatMateriel(object $materiel, string $etat): void
    {
        if (method_exists($materiel, 'materiel') && $materiel->materiel) {
            $materiel->materiel()->update(['etat' => $etat]);

            return;
        }

        $materiel->etat = $etat;
        $materiel->save();
    }
}
