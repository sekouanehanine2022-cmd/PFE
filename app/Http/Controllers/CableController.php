<?php

namespace App\Http\Controllers;

use App\Models\Cable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'reference'        => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/', 'unique:cables,reference'],
            'type_cable'       => ['required', Rule::in(['HDMI', 'VGA', 'DisplayPort', 'USB-A', 'USB-C', 'RJ45', 'Alimentation', 'autre'])],
            'type_cable_autre' => ['nullable', 'required_if:type_cable,autre', 'string', 'max:30', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'longueur'         => ['required', 'string', 'max:10', 'regex:/^[0-9]+([.,][0-9]{1,2})?\s?(m|cm)$/i'],
            'quantite'         => ['required', 'integer', 'min:0', 'max:9999'],
            'seuil_alerte'     => ['nullable', 'integer', 'min:0', 'max:9999'],
            'couleur'          => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'emplacement'      => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
        ]);

        $data = $this->donneesCable($request);
        $data['quantite_disponible'] = $request->quantite;

        Cable::create($data);

        return redirect()->route('cables.index')
                         ->with('success', __('messages.materiel_ajoute', ['type' => 'Cable']));
    }

    public function update(Request $request, Cable $cable)
    {
        $request->validate([
            'type_cable'       => ['required', Rule::in(['HDMI', 'VGA', 'DisplayPort', 'USB-A', 'USB-C', 'RJ45', 'Alimentation', 'autre'])],
            'type_cable_autre' => ['nullable', 'required_if:type_cable,autre', 'string', 'max:30', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'longueur'         => ['required', 'string', 'max:10', 'regex:/^[0-9]+([.,][0-9]{1,2})?\s?(m|cm)$/i'],
            'seuil_alerte'     => ['nullable', 'integer', 'min:0', 'max:9999'],
            'couleur'          => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'emplacement'      => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
        ]);

        // Référence et Quantité ne sont volontairement pas envoyées par le
        // formulaire en mode édition (champs désactivés côté vue) : la
        // référence est un identifiant, et la quantité passe par les
        // mécanismes dédiés (+/- du tableau, Ajouter/Retirer stock du popup).
        $cable->update($this->donneesCable($request, false));

        return redirect()->route('cables.index')
                         ->with('success', __('messages.materiel_modifie', ['type' => 'Cable']));
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
        $donnees = $request->validate([
            'quantite' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $quantite = $donnees['quantite'];

        $cable->increment('quantite', $quantite);
        $cable->increment('quantite_disponible', $quantite);

        return redirect()->route('cables.index')->with('success', __('messages.stock_ajoute', ['quantite' => $quantite]));
    }

    public function retirerStock(Request $request, Cable $cable)
    {
        $donnees = $request->validate([
            'quantite' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $quantite = $donnees['quantite'];
        $quantite = min($quantite, $cable->quantite_disponible);

        if ($quantite > 0) {
            $cable->decrement('quantite', $quantite);
            $cable->decrement('quantite_disponible', $quantite);
        }

        return redirect()->route('cables.index')->with('success', __('messages.stock_retire', ['quantite' => $quantite]));
    }

    private function donneesCable(Request $request, bool $creation = true): array
    {
        $champs = [
            'type_cable',
            'longueur',
            'seuil_alerte',
            'couleur',
            'emplacement',
        ];

        if ($creation) {
            array_unshift($champs, 'reference', 'quantite');
        }

        $donnees = $request->only($champs);

        if ($request->type_cable === 'autre') {
            $donnees['type_cable'] = $request->type_cable_autre;
        }

        return $donnees;
    }

    public function destroy(Cable $cable)
    {
        $cable->delete();
        return redirect()->route('cables.index')
                         ->with('success', __('messages.materiel_supprime', ['type' => 'Cable']));
    }
}
