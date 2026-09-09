<?php

namespace App\Http\Controllers;

use App\Models\Peripherique;
use App\Services\AffectationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'reference'         => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/', 'unique:peripheriques,reference'],
            'nom'               => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9 ._+-]+$/'],
            'marque'            => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9 ._+-]+$/'],
            'numero_serie'      => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9 ._-]+$/', 'unique:peripheriques,numero_serie'],
            'sous_type'         => ['required', Rule::in(['clavier', 'souris', 'casque'])],
            'connexion'         => ['required', Rule::in(['bluetooth', 'filaire', 'sans_fil', 'autre'])],
            'connexion_autre'   => ['nullable', 'required_if:connexion,autre', 'string', 'max:30', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'disposition'       => ['nullable', Rule::in(['AZERTY', 'QWERTY', 'QWERTZ', 'autre'])],
            'disposition_autre' => ['nullable', 'required_if:disposition,autre', 'string', 'max:30', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'retro_eclairage'   => ['nullable', 'boolean'],
            'etat'              => ['nullable', Rule::in(['disponible', 'affecte', 'emprunte', 'en_panne'])],
            'emplacement'       => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'date_achat'        => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $personnel = null;
        $etudiant  = null;

        if ($request->etat === 'affecte') {
            if (! $request->filled('a_qui_id')) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.collaborateur_obligatoire_affectation', ['type' => 'peripherique'])])
                    ->withInput();
            }

            $personnel = \App\Models\Personnel::where('user_id', $request->a_qui_id)->first();

            if (! $personnel) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.collaborateur_introuvable')])
                    ->withInput();
            }

            if ($affectationService->existeAffectationActivePourType($personnel->id, Peripherique::class, $request->sous_type)) {
                $libelleType = $affectationService->libelleType(Peripherique::class, $request->sous_type);

                return back()
                    ->withErrors(['a_qui_id' => __('messages.collaborateur_deja_materiel_affecte', ['type' => $libelleType])])
                    ->withInput();
            }
        } elseif ($request->etat === 'emprunte') {
            if (! $request->filled('a_qui_id')) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.etudiant_obligatoire_emprunt', ['type' => 'peripherique'])])
                    ->withInput();
            }

            $etudiant = \App\Models\Etudiant::where('user_id', $request->a_qui_id)->first();

            if (! $etudiant) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.etudiant_introuvable')])
                    ->withInput();
            }
        }

        $peripherique = Peripherique::create($this->donneesPeripherique($request));

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
                         ->with('success', __('messages.materiel_ajoute', ['type' => ucfirst($request->sous_type)]));
    }

    public function update(Request $request, Peripherique $peripherique)
    {
        $request->validate([
            'nom'               => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9 ._+-]+$/'],
            'marque'            => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9 ._+-]+$/'],
            'connexion'         => ['required', Rule::in(['bluetooth', 'filaire', 'sans_fil', 'autre'])],
            'connexion_autre'   => ['nullable', 'required_if:connexion,autre', 'string', 'max:30', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'disposition'       => ['nullable', Rule::in(['AZERTY', 'QWERTY', 'QWERTZ', 'autre'])],
            'disposition_autre' => ['nullable', 'required_if:disposition,autre', 'string', 'max:30', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'retro_eclairage'   => ['nullable', 'boolean'],
            'etat'              => ['nullable', Rule::in(['disponible', 'affecte', 'emprunte', 'en_panne'])],
            'emplacement'       => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'date_achat'        => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $peripherique->update($this->donneesPeripherique($request, false, $peripherique->sous_type));

        $route = match($peripherique->sous_type) {
            'clavier' => 'claviers.index',
            'souris'  => 'souris.index',
            'casque'  => 'casques.index',
            default   => 'claviers.index'
        };

        return redirect()->route($route)
                         ->with('success', __('messages.materiel_modifie', ['type' => ucfirst($peripherique->sous_type)]));
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
                         ->with('success', __('messages.materiel_supprime', ['type' => 'Peripherique']));
    }

    public function signalerPanne(Peripherique $peripherique)
    {
        $peripherique->update(['etat' => 'en_panne']);

        return back()->with('success', __('messages.materiel_signale_panne', ['type' => 'Peripherique']));
    }

    private function donneesPeripherique(Request $request, bool $creation = true, ?string $sousTypeActuel = null): array
    {
        $champs = [
            'nom',
            'marque',
            'connexion',
            'disposition',
            'retro_eclairage',
            'etat',
            'emplacement',
            'date_achat',
        ];

        if ($creation) {
            array_unshift($champs, 'reference', 'numero_serie', 'sous_type');
        }

        $donnees = $request->only($champs);

        if ($request->connexion === 'autre') {
            $donnees['connexion'] = $request->connexion_autre;
        }

        if ($request->disposition === 'autre') {
            $donnees['disposition'] = $request->disposition_autre;
        }

        $sousType = $donnees['sous_type'] ?? $request->input('sous_type') ?? $sousTypeActuel;

        if ($sousType !== 'clavier') {
            $donnees['disposition'] = null;
        }

        return $donnees;
    }

    public function marquerRepare(Peripherique $peripherique)
    {
        $peripherique->update(['etat' => 'disponible']);

        return back()->with('success', __('messages.materiel_marque_disponible', ['type' => 'Peripherique']));
    }
}
