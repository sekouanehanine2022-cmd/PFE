<?php

namespace App\Http\Controllers;

use App\Models\Imprimante;
use Illuminate\Http\Request;

class ImprimanteController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->get('search', '');
        $etat      = $request->get('etat', '');

        $imprimantes = Imprimante::when($recherche, function($query) use ($recherche) {
                $query->where('nom', 'like', '%'.$recherche.'%')
                      ->orWhere('marque', 'like', '%'.$recherche.'%')
                      ->orWhere('numero_serie', 'like', '%'.$recherche.'%');
            })
            ->when($etat, function($query) use ($etat) {
                $query->where('etat', $etat);
            })
            ->get();

        $total       = Imprimante::count();
        $disponibles = Imprimante::where('etat', 'disponible')->count();
        $enPanne     = Imprimante::where('etat', 'en_panne')->count();

        return view('materiel.imprimantes', compact(
            'imprimantes', 'total', 'disponibles', 'enPanne'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'reference'       => 'required|unique:imprimantes',
            'nom'             => 'required',
            'marque'          => 'required',
            'numero_serie'    => 'required|unique:imprimantes',
            'type_impression' => 'required',
            'connexion'       => 'required',
        ]);

        Imprimante::create($request->all());

        return redirect()->route('imprimantes.index')
                         ->with('success', 'Imprimante ajoutée avec succès !');
    }

    public function update(Request $request, Imprimante $imprimante)
    {
        $request->validate([
            'nom'             => 'required',
            'marque'          => 'required',
            'type_impression' => 'required',
            'connexion'       => 'required',
        ]);

        $imprimante->update($request->all());

        return redirect()->route('imprimantes.index')
                         ->with('success', 'Imprimante modifiée avec succès !');
    }

    public function destroy(Imprimante $imprimante)
    {
        $imprimante->delete();

        return redirect()->route('imprimantes.index')
                         ->with('success', 'Imprimante supprimée avec succès !');
    }

    public function signalerPanne(Imprimante $imprimante)
    {
        $imprimante->update(['etat' => 'en_panne']);

        return back()->with('success', 'Imprimante signalée en panne.');
    }

    public function marquerRepare(Imprimante $imprimante)
    {
        $imprimante->update(['etat' => 'disponible']);

        return back()->with('success', 'Imprimante marquée comme disponible.');
    }
}