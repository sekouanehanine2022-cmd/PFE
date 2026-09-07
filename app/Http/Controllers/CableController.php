<?php

namespace App\Http\Controllers;

use App\Models\Cable;
use Illuminate\Http\Request;

class CableController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->get('search', '');
        $statut    = $request->get('statut', '');

        // Statistiques globales (indépendantes de la recherche/du filtre, comme sur les autres pages matériel)
        $tousLesCables = Cable::all();
        $totalStock    = $tousLesCables->sum('quantite');
        $disponibles   = $tousLesCables->sum('quantite_disponible');
        $enUtilisation = $totalStock - $disponibles;
        $stockBas      = $tousLesCables->filter(function ($cable) {
            return $cable->quantite_disponible > 0 && $cable->quantite_disponible <= $cable->seuil_alerte;
        })->count();
        $enRupture     = $tousLesCables->filter(function ($cable) {
            return $cable->quantite_disponible == 0;
        })->count();

        // Câbles affichés dans le tableau : recherche + filtre par état de stock
        $cables = Cable::when($recherche, function($query) use ($recherche) {
                $query->where('type_cable', 'like', '%'.$recherche.'%')
                      ->orWhere('reference', 'like', '%'.$recherche.'%');
            })
            ->get()
            ->filter(function ($cable) use ($statut) {
                if ($statut === 'stock_ok') {
                    return $cable->quantite_disponible > $cable->seuil_alerte;
                }
                if ($statut === 'stock_bas') {
                    return $cable->quantite_disponible > 0 && $cable->quantite_disponible <= $cable->seuil_alerte;
                }
                if ($statut === 'rupture') {
                    return $cable->quantite_disponible == 0;
                }
                return true; // pas de filtre : tous les câbles
            });

        return view('connectique.cables', compact(
            'cables', 'totalStock', 'disponibles', 'enUtilisation', 'stockBas', 'enRupture'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'reference'  => 'required|unique:cables',
            'type_cable' => 'required',
            'longueur'   => 'required',
            'quantite'   => 'required|integer|min:0',
        ]);

        $data = $request->all();
        $data['quantite_disponible'] = $request->quantite;

        Cable::create($data);

        return redirect()->route('cables.index')
                         ->with('success', 'Câble ajouté avec succès !');
    }

    public function update(Request $request, Cable $cable)
    {
        $request->validate([
            'type_cable' => 'required',
            'longueur'   => 'required',
        ]);

        // Référence et Quantité ne sont volontairement pas envoyées par le
        // formulaire en mode édition (champs désactivés côté vue) : la
        // référence est un identifiant, et la quantité passe par les
        // mécanismes dédiés (+/- du tableau, Ajouter/Retirer stock du popup).
        $cable->update($request->except(['reference', 'quantite']));

        return redirect()->route('cables.index')
                         ->with('success', 'Câble modifié avec succès !');
    }

    // ---- Boutons +/- du TABLEAU : usage/retour, le stock total ne change pas ----

    public function incrementer(Cable $cable)
    {
        if ($cable->quantite_disponible < $cable->quantite) {
            $cable->increment('quantite_disponible');
        }

        return redirect()->route('cables.index');
    }

    public function decrementer(Cable $cable)
    {
        if ($cable->quantite_disponible > 0) {
            $cable->decrement('quantite_disponible');
        }

        return redirect()->route('cables.index');
    }

    // ---- Boutons du POPUP DÉTAIL : vrai mouvement de stock (achat / mise au rebut) ----

    public function ajouterStock(Request $request, Cable $cable)
    {
        $quantite = max(1, (int) $request->input('quantite', 1));

        $cable->increment('quantite', $quantite);
        $cable->increment('quantite_disponible', $quantite);

        return redirect()->route('cables.index')->with('success', $quantite.' câble(s) ajouté(s) au stock.');
    }

    public function retirerStock(Request $request, Cable $cable)
    {
        $quantite = max(1, (int) $request->input('quantite', 1));
        $quantite = min($quantite, $cable->quantite_disponible);

        if ($quantite > 0) {
            $cable->decrement('quantite', $quantite);
            $cable->decrement('quantite_disponible', $quantite);
        }

        return redirect()->route('cables.index')->with('success', $quantite.' câble(s) retiré(s) du stock.');
    }

    public function destroy(Cable $cable)
    {
        $cable->delete();
        return redirect()->route('cables.index')
                         ->with('success', 'Câble supprimé avec succès !');
    }
}