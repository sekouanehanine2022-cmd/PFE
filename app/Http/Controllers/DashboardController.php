<?php

namespace App\Http\Controllers;

use App\Models\Materiel;
use App\Models\Cable;
use App\Models\Ticket;
use App\Models\Emprunt;
use App\Models\Affectation;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $utilisateur = $request->user();
        $utilisateur->loadMissing('personnel');

        if ($utilisateur->personnel?->role !== 'admin') {
            return redirect()->route('mon-materiel.index');
        }

        // ---- Cartes statistiques globales ----

        $totalMateriel = Materiel::count();

        $ticketsOuverts = Ticket::whereIn('statut', ['ouvert', 'en_cours'])->count();

        $empruntsEnCours = Emprunt::where('statut', 'en_cours')->count();

        $affectationsActives = Affectation::where('statut', 'active')->count();

        // ---- Alertes ----

        $materielEnPanne = Materiel::where('etat', 'en_panne')->count();

        $cablesEnAlerte = Cable::get()->filter(function ($cable) {
            return $cable->quantite_disponible <= $cable->seuil_alerte;
        })->count();

        $empruntsEnRetard = Emprunt::where('statut', 'en_retard')->count();

        $ticketsPrioriteHaute = Ticket::where('priorite', 'haute')
            ->whereIn('statut', ['ouvert', 'en_cours'])
            ->count();

        // ---- Activité récente (10 derniers événements, tous types confondus) ----

        $derniersTickets = Ticket::with('demandeur')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($ticket) {
                return [
                    'type'        => 'ticket',
                    'titre'       => $ticket->titre,
                    'personne'    => $ticket->demandeur->name ?? '-',
                    'date'        => $ticket->created_at,
                    'lien'        => route('tickets.index'),
                ];
            });

        $derniersEmprunts = Emprunt::with('etudiant.user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($emprunt) {
                return [
                    'type'        => 'emprunt',
                    'titre'       => 'Emprunt de matériel',
                    'personne'    => $emprunt->etudiant->user->name ?? '-',
                    'date'        => $emprunt->created_at,
                    'lien'        => route('emprunts.index'),
                ];
            });

        $dernieresAffectations = Affectation::with('personnel.user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($affectation) {
                return [
                    'type'        => 'affectation',
                    'titre'       => 'Affectation de matériel',
                    'personne'    => $affectation->personnel->user->name ?? '-',
                    'date'        => $affectation->created_at,
                    'lien'        => route('affectations.index'),
                ];
            });

        $activiteRecente = $derniersTickets
            ->concat($derniersEmprunts)
            ->concat($dernieresAffectations)
            ->sortByDesc('date')
            ->take(10)
            ->values();

        return view('dashboard', compact(
            'totalMateriel',
            'ticketsOuverts',
            'empruntsEnCours',
            'affectationsActives',
            'materielEnPanne',
            'cablesEnAlerte',
            'empruntsEnRetard',
            'ticketsPrioriteHaute',
            'activiteRecente'
        ));
    }
}
