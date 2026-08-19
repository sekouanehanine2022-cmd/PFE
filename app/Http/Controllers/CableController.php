<?php

namespace App\Http\Controllers;

use App\Models\Cable;
use Illuminate\Http\Request;

class CableController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->get('search', '');

        $cables = Cable::when($recherche, function($query) use ($recherche) {
                $query->where('type_cable', 'like', '%'.$recherche.'%')
                      ->orWhere('reference', 'like', '%'.$recherche.'%');
            })
            ->get();

        $totalStock    = $cables->sum('quantite');
        $disponibles   = $cables->sum('quantite_disponible');
        $enUtilisation = $totalStock - $disponibles;
        $stockBas      = $cables->filter(function($cable) {
            return $cable->quantite_disponible <= $cable->seuil_alerte;
        })->count();

        return view('connectique.cables', compact(
            'cables', 'totalStock', 'disponibles', 'enUtilisation', 'stockBas'
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

    public function incrementer(Cable $cable)
    {
        $cable->increment('quantite_disponible');
        $cable->increment('quantite');
        return redirect()->route('cables.index');
    }

    public function decrementer(Cable $cable)
{
    if ($cable->quantite_disponible > 0) {
        $cable->decrement('quantite_disponible');
        $cable->decrement('quantite'); // ← ajoute cette ligne
    }
    return redirect()->route('cables.index');
}

    public function destroy(Cable $cable)
    {
        $cable->delete();
        return redirect()->route('cables.index')
                         ->with('success', 'Câble supprimé avec succès !');
    }
}