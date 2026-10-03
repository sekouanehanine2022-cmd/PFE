<?php

namespace App\Http\Controllers;

use App\Mail\RelanceEmpruntMail;
use App\Models\Casque;
use App\Models\Clavier;
use App\Models\Ecran;
use App\Models\Emprunt;
use App\Models\MiniPc;
use App\Models\PcPortable;
use App\Models\Souris;
use App\Models\Ticket;
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

class EmpruntController extends Controller
{
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
            })
            ->orderBy('created_at', 'desc')
            ->get();

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

    public function store(Request $request)
    {
        $request->validate([
            'etudiant_id'      => 'required|exists:etudiants,id',
            'materiel_type'    => ['required', Rule::in(['pc-portable'])],
            'materiel_numero_serie' => 'required|string|max:100',
            'date_debut'       => 'required|date',
            'date_fin_prevue'  => 'required|date|after_or_equal:date_debut',
        ]);

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

        DB::transaction(function () use ($request, $materiel) {
            $materielCentral = $materiel->materiel()->lockForUpdate()->first();

            if (! $materielCentral || $materielCentral->etat !== 'disponible') {
                throw ValidationException::withMessages([
                    'materiel_numero_serie' => __('messages.materiel_indisponible'),
                ]);
            }

            Emprunt::create([
                'etudiant_id'     => $request->etudiant_id,
                'pc_numero_serie' => $materiel->numero_serie,
                'date_debut'      => $request->date_debut,
                'date_fin_prevue' => $request->date_fin_prevue,
                'statut'          => 'en_cours',
            ]);

            $materielCentral->update(['etat' => 'emprunte']);
        });

        return redirect()->route('emprunts.index')->with('success', __('messages.emprunt_cree'));
    }

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

        if ($this->etudiantPossedePcEnCours($etudiant->id)) {
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

        DB::transaction(function () use ($request, $ticket, $etudiant, $materiel, $notificationService) {
            $materielCentral = $materiel->materiel()->lockForUpdate()->first();

            if (! $materielCentral || $materielCentral->etat !== 'disponible') {
                throw ValidationException::withMessages([
                    'materiel_numero_serie' => __('messages.materiel_indisponible'),
                ]);
            }

            Emprunt::create([
                'etudiant_id' => $etudiant->id,
                'ticket_id' => $ticket->id,
                'pc_numero_serie' => $materiel->numero_serie,
                'date_debut' => $request->date_debut,
                'date_fin_prevue' => $request->date_fin_prevue,
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

    public function prolonger(Request $request, Emprunt $emprunt)
    {
        if ($emprunt->statut === 'rendu') {
            return redirect()->route('emprunts.index')->with('info', __('messages.emprunt_deja_rendu_prolongation'));
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

    private function etudiantPossedePcEnCours(int $etudiantId): bool
    {
        return Emprunt::query()
            ->where('etudiant_id', $etudiantId)
            ->where('statut', '!=', 'rendu')
            ->exists();
    }

}

