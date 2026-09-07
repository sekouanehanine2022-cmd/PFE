<?php

namespace App\Http\Controllers;

use App\Models\PcPortable;
use App\Services\AffectationService;
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
        $enPanne     = PcPortable::where('etat', 'en_panne')->count();

        return view('materiel.pc-portables', compact(
            'pcPortables', 'total', 'disponibles', 'affectes', 'enPanne'
        ));
    }

    public function create()
    {
        return view('materiel.pc-portables-create');
    }

    public function store(Request $request, AffectationService $affectationService)
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

        $personnel = null;
        $etudiant  = null;

        if ($request->etat === 'affecte') {
            if (! $request->filled('a_qui_id')) {
                return back()
                    ->withErrors(['a_qui_id' => "Veuillez sélectionner un collaborateur pour un PC affecté."])
                    ->withInput();
            }

            $personnel = \App\Models\Personnel::where('user_id', $request->a_qui_id)->first();

            if (! $personnel) {
                return back()
                    ->withErrors(['a_qui_id' => "Le collaborateur sélectionné est introuvable. Veuillez le choisir dans la liste de suggestions."])
                    ->withInput();
            }

            if ($affectationService->existeAffectationActivePourType($personnel->id, PcPortable::class)) {
                return back()
                    ->withErrors(['a_qui_id' => "Ce collaborateur a deja un materiel de type PC portable affecte."])
                    ->withInput();
            }
        } elseif ($request->etat === 'emprunte') {
            if (! $request->filled('a_qui_id')) {
                return back()
                    ->withErrors(['a_qui_id' => "Veuillez sélectionner un étudiant pour un PC emprunté."])
                    ->withInput();
            }

            $etudiant = \App\Models\Etudiant::where('user_id', $request->a_qui_id)->first();

            if (! $etudiant) {
                return back()
                    ->withErrors(['a_qui_id' => "L'étudiant sélectionné est introuvable. Veuillez le choisir dans la liste de suggestions."])
                    ->withInput();
            }
        }

        $pc = PcPortable::create($request->all());

        if ($personnel) {
            \App\Models\Affectation::create([
                'personnel_id'  => $personnel->id,
                'materiel_type' => PcPortable::class,
                'materiel_id'   => $pc->id,
                'date_debut'    => now(),
                'statut'        => 'active',
                'ticket_id'     => null,
            ]);
        } elseif ($etudiant) {
            \App\Models\Emprunt::create([
                'etudiant_id'    => $etudiant->id,
                'materiel_type'  => PcPortable::class,
                'materiel_id'    => $pc->id,
                'date_debut'     => now(),
                'date_fin_prevue'=> now()->addMonths(3),
                'statut'         => 'en_cours',
                'ticket_id'      => null,
            ]);
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

    public function signalerPanne(PcPortable $pcPortable)
    {
        $pcPortable->update(['etat' => 'en_panne']);

        return back()->with('success', 'PC Portable signalé en panne.');
    }

    public function marquerRepare(PcPortable $pcPortable)
    {
        $pcPortable->update(['etat' => 'disponible']);

        return back()->with('success', 'PC Portable marqué comme disponible.');
    }
}
