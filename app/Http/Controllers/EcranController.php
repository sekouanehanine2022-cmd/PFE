<?php

namespace App\Http\Controllers;

use App\Models\Ecran;
use Illuminate\Http\Request;

class EcranController extends Controller
{
    public function index(Request $request)
{
    $recherche = $request->get('search', '');
    $etat      = $request->get('etat', '');

    $ecrans = Ecran::when($recherche, function($query) use ($recherche) {
            $query->where('nom', 'like', '%'.$recherche.'%')
                  ->orWhere('marque', 'like', '%'.$recherche.'%')
                  ->orWhere('numero_serie', 'like', '%'.$recherche.'%');
        })
        ->when($etat, function($query) use ($etat) {
            $query->where('etat', $etat);
        })
        ->with(['affectations.personnel.user', 'emprunts.etudiant.user'])
        ->get();

    $total       = Ecran::count();
    $disponibles = Ecran::where('etat', 'disponible')->count();
    $affectes    = Ecran::where('etat', 'affecte')->count();
    $enPanne     = Ecran::whereIn('etat', ['en_panne', 'maintenance'])->count();

    return view('materiel.ecrans', compact(
        'ecrans', 'total', 'disponibles', 'affectes', 'enPanne'
    ));
}

public function store(Request $request)
{
    $request->validate([
        'reference'    => 'required|unique:ecrans',
        'nom'          => 'required',
        'marque'       => 'required',
        'numero_serie' => 'required|unique:ecrans',
        'taille'       => 'required',
        'resolution'   => 'required',
    ]);

    $ecran = Ecran::create($request->all());

    if ($request->etat === 'affecte' && $request->a_qui_id) {
        $personnel = \App\Models\Personnel::where('user_id', $request->a_qui_id)->first();
        if ($personnel) {
            \App\Models\Affectation::create([
                'personnel_id'  => $personnel->id,
                'materiel_type' => Ecran::class,
                'materiel_id'   => $ecran->id,
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
                'materiel_type'  => Ecran::class,
                'materiel_id'    => $ecran->id,
                'date_debut'     => now(),
                'date_fin_prevue'=> now()->addMonths(3),
                'statut'         => 'emprunte',
                'ticket_id'      => null,
            ]);
        }
    }

    return redirect()->route('ecrans.index')
                     ->with('success', 'Écran ajouté avec succès !');
}
    public function update(Request $request, Ecran $ecran)
    {
        $request->validate([
            'nom'        => 'required',
            'marque'     => 'required',
            'taille'     => 'required',
            'resolution' => 'required',
        ]);

        $ecran->update($request->all());

        return redirect()->route('ecrans.index')
                         ->with('success', 'Écran modifié avec succès !');
    }

    public function destroy(Ecran $ecran)
    {
        $ecran->delete();

        return redirect()->route('ecrans.index')
                         ->with('success', 'Écran supprimé avec succès !');
    }
}