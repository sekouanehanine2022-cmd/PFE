<?php

namespace App\Http\Controllers;

use App\Models\MiniPc;
use Illuminate\Http\Request;

class MiniPcController extends Controller
{
   public function index(Request $request)
{
    $recherche = $request->get('search', '');
    $etat      = $request->get('etat', '');

    $miniPcs = MiniPc::when($recherche, function($query) use ($recherche) {
            $query->where('nom', 'like', '%'.$recherche.'%')
                  ->orWhere('marque', 'like', '%'.$recherche.'%')
                  ->orWhere('numero_serie', 'like', '%'.$recherche.'%');
        })
        ->when($etat, function($query) use ($etat) {
            $query->where('etat', $etat);
        })
        ->with(['affectations.personnel.user', 'emprunts.etudiant.user'])
        ->get();

    $total       = MiniPc::count();
    $disponibles = MiniPc::where('etat', 'disponible')->count();
    $affectes    = MiniPc::where('etat', 'affecte')->count();
    $enPanne     = MiniPc::whereIn('etat', ['en_panne', 'maintenance'])->count();

    return view('materiel.mini-pc', compact(
        'miniPcs', 'total', 'disponibles', 'affectes', 'enPanne'
    ));
}

    public function store(Request $request)
{
    $request->validate([
        'reference'    => 'required|unique:mini_pcs',
        'nom'          => 'required',
        'marque'       => 'required',
        'numero_serie' => 'required|unique:mini_pcs',
        'cpu'          => 'required',
        'ram'          => 'required',
        'stockage'     => 'required',
        'os'           => 'required',
    ]);

    $miniPc = MiniPc::create($request->all());

    if ($request->etat === 'affecte' && $request->a_qui_id) {
        $personnel = \App\Models\Personnel::where('user_id', $request->a_qui_id)->first();
        if ($personnel) {
            \App\Models\Affectation::create([
                'personnel_id'  => $personnel->id,
                'materiel_type' => MiniPc::class,
                'materiel_id'   => $miniPc->id,
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
                'materiel_type'  => MiniPc::class,
                'materiel_id'    => $miniPc->id,
                'date_debut'     => now(),
                'date_fin_prevue'=> now()->addMonths(3),
                'statut'         => 'emprunte',
                'ticket_id'      => null,
            ]);
        }
    }

    return redirect()->route('mini-pc.index')
                     ->with('success', 'Mini PC ajouté avec succès !');
}
    public function update(Request $request, MiniPc $miniPc)
    {
        $request->validate([
            'nom'      => 'required',
            'marque'   => 'required',
            'cpu'      => 'required',
            'ram'      => 'required',
            'stockage' => 'required',
            'os'       => 'required',
        ]);

        $miniPc->update($request->all());

        return redirect()->route('mini-pc.index')
                         ->with('success', 'Mini PC modifié avec succès !');
    }

    public function destroy(MiniPc $miniPc)
    {
        $miniPc->delete();

        return redirect()->route('mini-pc.index')
                         ->with('success', 'Mini PC supprimé avec succès !');
    }
}