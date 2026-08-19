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
        ->with(['affectations.personnel.user', 'emprunts.etudiant.user'])
        ->get();

    $total       = Imprimante::count();
    $disponibles = Imprimante::where('etat', 'disponible')->count();
    $affectes    = Imprimante::where('etat', 'affecte')->count();
    $enPanne     = Imprimante::whereIn('etat', ['en_panne', 'maintenance'])->count();

    return view('materiel.imprimantes', compact(
        'imprimantes', 'total', 'disponibles', 'affectes', 'enPanne'
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

    $imprimante = Imprimante::create($request->all());

    if ($request->etat === 'affecte' && $request->a_qui_id) {
        $personnel = \App\Models\Personnel::where('user_id', $request->a_qui_id)->first();
        if ($personnel) {
            \App\Models\Affectation::create([
                'personnel_id'  => $personnel->id,
                'materiel_type' => Imprimante::class,
                'materiel_id'   => $imprimante->id,
                'date_debut'    => now(),
                'statut'        => 'active',
                'ticket_id'     => null,
            ]);
        }
    } elseif ($request->etat === 'emprunte' && $request->a_qui_id) {
        $etudiant = \App\Models\Etudiant::where('user_id', $request->a_qui_id)->first();
        if ($etudiant) {
            \App\Models\Emprunt::create([
                'etudiant_id'    => $etudiant->id,
                'materiel_type'  => Imprimante::class,
                'materiel_id'    => $imprimante->id,
                'date_debut'     => now(),
                'date_fin_prevue'=> now()->addMonths(3),
                'statut'         => 'emprunte',
                'ticket_id'      => null,
            ]);
        }
    }

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
}