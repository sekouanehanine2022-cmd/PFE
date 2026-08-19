<?php

namespace App\Http\Controllers;

use App\Models\PcPortable;
use Illuminate\Http\Request;

class PcPortableController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->get('search', '');
        $etat      = $request->get('etat', '');

        $pcPortables = PcPortable::when($recherche, function($query) use ($recherche) {
                $query->where('nom', 'like', '%'.$recherche.'%')
                      ->orWhere('marque', 'like', '%'.$recherche.'%')
                      ->orWhere('numero_serie', 'like', '%'.$recherche.'%');
            })
            ->when($etat, function($query) use ($etat) {
                $query->where('etat', $etat);
            })
            ->with(['affectations.personnel.user', 'emprunts.etudiant.user'])
            ->get();

        $total       = PcPortable::count();
        $disponibles = PcPortable::where('etat', 'disponible')->count();
        $affectes    = PcPortable::where('etat', 'affecte')->count();
        $enPanne     = PcPortable::whereIn('etat', ['en_panne', 'maintenance'])->count();

        return view('materiel.pc-portables', compact(
            'pcPortables', 'total', 'disponibles', 'affectes', 'enPanne'
        ));
    }

    public function create()
    {
        return view('materiel.pc-portables-create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'reference'    => 'required|unique:pc_portables',
            'nom'          => 'required',
            'marque'       => 'required',
            'numero_serie' => 'required|unique:pc_portables',
            'cpu'          => 'required',
            'ram'          => 'required',
            'stockage'     => 'required',
            'os'           => 'required',
        ]);

        $pc = PcPortable::create($request->all());

        if ($request->etat === 'affecte' && $request->a_qui_id) {
            $personnel = \App\Models\Personnel::where('user_id', $request->a_qui_id)->first();
            if ($personnel) {
                \App\Models\Affectation::create([
                    'personnel_id'  => $personnel->id,
                    'materiel_type' => PcPortable::class,
                    'materiel_id'   => $pc->id,
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
                    'materiel_type'  => PcPortable::class,
                    'materiel_id'    => $pc->id,
                    'date_debut'     => now(),
                    'date_fin_prevue'=> now()->addMonths(3),
                    'statut'         => 'emprunte',
                    'ticket_id'      => null,
                ]);
            }
        }

        return redirect()->route('pc-portables.index')
                         ->with('success', 'PC Portable ajouté avec succès !');
    }

    public function update(Request $request, PcPortable $pcPortable)
    {
        $request->validate([
            'nom'     => 'required',
            'marque'  => 'required',
            'cpu'     => 'required',
            'ram'     => 'required',
            'stockage'=> 'required',
            'os'      => 'required',
        ]);

        $pcPortable->update($request->all());

        return redirect()->route('pc-portables.index')
                         ->with('success', 'PC Portable modifié avec succès !');
    }

    public function destroy(PcPortable $pcPortable)
    {
        $pcPortable->delete();

        return redirect()->route('pc-portables.index')
                         ->with('success', 'PC Portable supprimé avec succès !');
    }
}