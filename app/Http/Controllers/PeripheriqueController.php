<?php

namespace App\Http\Controllers;

use App\Models\Peripherique;
use Illuminate\Http\Request;

class PeripheriqueController extends Controller
{
    public function index(Request $request, $sousType)
{
    $recherche = $request->get('search', '');
    $etat      = $request->get('etat', '');

    $peripheriques = Peripherique::where('sous_type', $sousType)
        ->when($recherche, function($query) use ($recherche) {
            $query->where('nom', 'like', '%'.$recherche.'%')
                  ->orWhere('marque', 'like', '%'.$recherche.'%')
                  ->orWhere('numero_serie', 'like', '%'.$recherche.'%');
        })
        ->when($etat, function($query) use ($etat) {
            $query->where('etat', $etat);
        })
        ->with(['affectations.personnel.user', 'emprunts.etudiant.user'])
        ->get();

    $total       = Peripherique::where('sous_type', $sousType)->count();
    $disponibles = Peripherique::where('sous_type', $sousType)->where('etat', 'disponible')->count();
    $affectes    = Peripherique::where('sous_type', $sousType)->where('etat', 'affecte')->count();
    $enPanne     = Peripherique::where('sous_type', $sousType)->whereIn('etat', ['en_panne', 'maintenance'])->count();

    $vue = match($sousType) {
        'clavier' => 'peripheriques.claviers',
        'souris'  => 'peripheriques.souris',
        'casque'  => 'peripheriques.casques',
        default   => 'peripheriques.claviers'
    };

    return view($vue, compact(
        'peripheriques', 'total', 'disponibles', 'affectes', 'enPanne', 'sousType'
    ));
}

public function store(Request $request)
{
    $request->validate([
        'reference'    => 'required|unique:peripheriques',
        'nom'          => 'required',
        'marque'       => 'required',
        'numero_serie' => 'required|unique:peripheriques',
        'sous_type'    => 'required',
        'connexion'    => 'required',
    ]);

    $peripherique = Peripherique::create($request->all());

    if ($request->etat === 'affecte' && $request->a_qui_id) {
        $personnel = \App\Models\Personnel::where('user_id', $request->a_qui_id)->first();
        if ($personnel) {
            \App\Models\Affectation::create([
                'personnel_id'  => $personnel->id,
                'materiel_type' => Peripherique::class,
                'materiel_id'   => $peripherique->id,
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
                'materiel_type'  => Peripherique::class,
                'materiel_id'    => $peripherique->id,
                'date_debut'     => now(),
                'date_fin_prevue'=> now()->addMonths(3),
                'statut'         => 'emprunte',
                'ticket_id'      => null,
            ]);
        }
    }

    $route = match($request->sous_type) {
        'clavier' => 'claviers.index',
        'souris'  => 'souris.index',
        'casque'  => 'casques.index',
        default   => 'claviers.index'
    };

    return redirect()->route($route)
                     ->with('success', ucfirst($request->sous_type) . ' ajouté avec succès !');
}
    public function destroy(Peripherique $peripherique)
    {
        $route = match($peripherique->sous_type) {
            'clavier' => 'claviers.index',
            'souris'  => 'souris.index',
            'casque'  => 'casques.index',
            default   => 'claviers.index'
        };

        $peripherique->delete();

        return redirect()->route($route)
                         ->with('success', 'Périphérique supprimé avec succès !');
    }
}