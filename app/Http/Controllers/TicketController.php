<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->get('search', '');
        $statut    = $request->get('statut', '');

        $tickets = Ticket::with(['demandeur', 'technicien', 'materiel'])
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

        $total          = Ticket::count();
        $ouverts        = Ticket::where('statut', 'ouvert')->count();
        $enCours        = Ticket::where('statut', 'en_cours')->count();
        $resolus        = Ticket::where('statut', 'resolu')->count();
        $fermes         = Ticket::where('statut', 'ferme')->count();
        $prioriteHaute  = Ticket::where('priorite', 'haute')->count();

        return view('activite.tickets', compact(
            'tickets', 'total', 'ouverts', 'enCours', 'resolus', 'fermes', 'prioriteHaute'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'titre'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'type'          => 'required|in:incident,affectation,emprunt',
            'priorite'      => 'required|in:haute,normale,basse',
            'numero_serie'  => 'required_if:type,incident|nullable|string',
        ]);

        $materielType = null;
        $materielId   = null;

        // Le numéro de série n'a de sens que pour un incident (un matériel
        // précis déjà en service). Pour une demande d'affectation/emprunt,
        // on l'ignore même si le champ contenait encore une valeur.
        if ($request->type === 'incident' && $request->filled('numero_serie')) {
            $materiel = $this->trouverMaterielParNumeroSerie($request->numero_serie);

            if (! $materiel) {
                return back()
                    ->withErrors(['numero_serie' => "Aucun matériel trouvé avec ce numéro de série."])
                    ->withInput();
            }

            $materielType = get_class($materiel);
            $materielId   = $materiel->id;
        }

        Ticket::create([
            'titre'          => $request->titre,
            'description'    => $request->description,
            'type'           => $request->type,
            'priorite'       => $request->priorite,
            'statut'         => 'ouvert',
            'demandeur_id'   => auth()->id(),
            'technicien_id'  => null,
            'materiel_type'  => $materielType,
            'materiel_id'    => $materielId,
        ]);

        return redirect()->route('tickets.index')->with('success', 'Ticket créé avec succès.');
    }

    // Cherche un matériel par numéro de série dans toutes les tables
    // matériel (un seul numéro de série peut correspondre, tous types confondus).
    private function trouverMaterielParNumeroSerie($numeroSerie)
    {
        $classes = [
            \App\Models\PcPortable::class,
            \App\Models\MiniPc::class,
            \App\Models\Ecran::class,
            \App\Models\Imprimante::class,
            \App\Models\Peripherique::class,
        ];

        foreach ($classes as $classe) {
            $materiel = $classe::where('numero_serie', $numeroSerie)->first();
            if ($materiel) {
                return $materiel;
            }
        }

        return null;
    }
}