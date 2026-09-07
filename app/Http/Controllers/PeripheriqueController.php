<?php

namespace App\Http\Controllers;

use App\Models\Peripherique;
use App\Services\AffectationService;
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
        $enPanne     = Peripherique::where('sous_type', $sousType)->where('etat', 'en_panne')->count();

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

    public function store(Request $request, AffectationService $affectationService)
    {
        $request->validate([
            'reference'    => 'required|unique:peripheriques',
            'nom'          => 'required',
            'marque'       => 'required',
            'numero_serie' => 'required|unique:peripheriques',
            'sous_type'    => 'required',
            'connexion'    => 'required',
        ]);

        $personnel = null;
        $etudiant  = null;

        if ($request->etat === 'affecte') {
            if (! $request->filled('a_qui_id')) {
                return back()
                    ->withErrors(['a_qui_id' => "Veuillez sélectionner un collaborateur pour un périphérique affecté."])
                    ->withInput();
            }

            $personnel = \App\Models\Personnel::where('user_id', $request->a_qui_id)->first();

            if (! $personnel) {
                return back()
                    ->withErrors(['a_qui_id' => "Le collaborateur sélectionné est introuvable. Veuillez le choisir dans la liste de suggestions."])
                    ->withInput();
            }

            if ($affectationService->existeAffectationActivePourType($personnel->id, Peripherique::class, $request->sous_type)) {
                $libelleType = $affectationService->libelleType(Peripherique::class, $request->sous_type);

                return back()
                    ->withErrors(['a_qui_id' => 'Ce collaborateur a deja un materiel de type ' . $libelleType . ' affecte.'])
                    ->withInput();
            }
        } elseif ($request->etat === 'emprunte') {
            if (! $request->filled('a_qui_id')) {
                return back()
                    ->withErrors(['a_qui_id' => "Veuillez sélectionner un étudiant pour un périphérique emprunté."])
                    ->withInput();
            }

            $etudiant = \App\Models\Etudiant::where('user_id', $request->a_qui_id)->first();

            if (! $etudiant) {
                return back()
                    ->withErrors(['a_qui_id' => "L'étudiant sélectionné est introuvable. Veuillez le choisir dans la liste de suggestions."])
                    ->withInput();
            }
        }

        $peripherique = Peripherique::create($request->all());

        if ($personnel) {
            \App\Models\Affectation::create([
                'personnel_id'  => $personnel->id,
                'materiel_type' => Peripherique::class,
                'materiel_id'   => $peripherique->id,
                'date_debut'    => now(),
                'statut'        => 'active',
                'ticket_id'     => null,
            ]);
        } elseif ($etudiant) {
            \App\Models\Emprunt::create([
                'etudiant_id'    => $etudiant->id,
                'materiel_type'  => Peripherique::class,
                'materiel_id'    => $peripherique->id,
                'date_debut'     => now(),
                'date_fin_prevue'=> now()->addMonths(3),
                'statut'         => 'en_cours',
                'ticket_id'      => null,
            ]);
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

    public function update(Request $request, Peripherique $peripherique)
    {
        $request->validate([
            'nom'       => 'required',
            'marque'    => 'required',
            'connexion' => 'required',
        ]);

        $peripherique->update($request->except('sous_type'));

        $route = match($peripherique->sous_type) {
            'clavier' => 'claviers.index',
            'souris'  => 'souris.index',
            'casque'  => 'casques.index',
            default   => 'claviers.index'
        };

        return redirect()->route($route)
                         ->with('success', ucfirst($peripherique->sous_type) . ' modifié avec succès !');
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

    public function signalerPanne(Peripherique $peripherique)
    {
        $peripherique->update(['etat' => 'en_panne']);

        return back()->with('success', 'Périphérique signalé en panne.');
    }

    public function marquerRepare(Peripherique $peripherique)
    {
        $peripherique->update(['etat' => 'disponible']);

        return back()->with('success', 'Périphérique marqué comme disponible.');
    }
}
