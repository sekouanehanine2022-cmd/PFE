<?php

namespace App\Http\Controllers;

use App\Models\Ecran;
use App\Services\AffectationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $enPanne     = Ecran::where('etat', 'en_panne')->count();

        return view('materiel.ecrans', compact(
            'ecrans', 'total', 'disponibles', 'affectes', 'enPanne'
        ));
    }

    public function store(Request $request, AffectationService $affectationService)
    {
        $request->validate([
            'reference'    => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/', 'unique:ecrans,reference'],
            'nom'          => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9 ._-]+$/'],
            'marque'       => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9 ._-]+$/'],
            'numero_serie' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:ecrans,numero_serie'],
            'taille'       => ['required', 'string', Rule::in(['19', '22', '24', '27', '32', 'autre'])],
            'taille_autre' => ['nullable', 'required_if:taille,autre', 'string', 'max:5', 'regex:/^[0-9]{2}([.,][0-9])?$/'],
            'resolution'   => ['required', 'string', Rule::in(['1366x768', '1920x1080', '2560x1440', '3840x2160', 'autre'])],
            'resolution_autre' => ['nullable', 'required_if:resolution,autre', 'string', 'max:20', 'regex:/^[0-9]{3,4}x[0-9]{3,4}$/'],
            'dalle'        => ['nullable', 'string', Rule::in(['IPS', 'TN', 'VA', 'OLED', 'autre'])],
            'dalle_autre'  => ['nullable', 'required_if:dalle,autre', 'string', 'max:30', 'regex:/^[A-Za-z0-9 ._-]+$/'],
            'taux_rafraichissement' => ['nullable', 'string', 'max:3', 'regex:/^[0-9]{2,3}$/'],
            'etat'         => ['nullable', Rule::in(['disponible', 'affecte', 'emprunte', 'en_panne'])],
            'emplacement'  => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9 ._()\/-]+$/'],
            'date_achat'   => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $personnel = null;
        $etudiant  = null;

        if ($request->etat === 'affecte') {
            if (! $request->filled('a_qui_id')) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.collaborateur_obligatoire_affectation', ['type' => 'ecran'])])
                    ->withInput();
            }

            $personnel = \App\Models\Personnel::where('user_id', $request->a_qui_id)->first();

            if (! $personnel) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.collaborateur_introuvable')])
                    ->withInput();
            }

            if ($affectationService->existeAffectationActivePourType($personnel->id, Ecran::class)) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.collaborateur_deja_materiel_affecte', ['type' => 'ecran'])])
                    ->withInput();
            }
        } elseif ($request->etat === 'emprunte') {
            if (! $request->filled('a_qui_id')) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.etudiant_obligatoire_emprunt', ['type' => 'ecran'])])
                    ->withInput();
            }

            $etudiant = \App\Models\Etudiant::where('user_id', $request->a_qui_id)->first();

            if (! $etudiant) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.etudiant_introuvable')])
                    ->withInput();
            }
        }

        $ecran = Ecran::create($this->donneesEcran($request));

        if ($personnel) {
            \App\Models\Affectation::create([
                'personnel_id'  => $personnel->id,
                'materiel_type' => Ecran::class,
                'materiel_id'   => $ecran->id,
                'date_debut'    => now(),
                'statut'        => 'active',
                'ticket_id'     => null,
            ]);
        } elseif ($etudiant) {
            \App\Models\Emprunt::create([
                'etudiant_id'    => $etudiant->id,
                'materiel_type'  => Ecran::class,
                'materiel_id'    => $ecran->id,
                'date_debut'     => now(),
                'date_fin_prevue'=> now()->addMonths(3),
                'statut'         => 'en_cours',
                'ticket_id'      => null,
            ]);
        }

        return redirect()->route('ecrans.index')
                         ->with('success', __('messages.materiel_ajoute', ['type' => 'Ecran']));
    }

    public function update(Request $request, Ecran $ecran)
    {
        $request->validate([
            'nom'          => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9 ._-]+$/'],
            'marque'       => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9 ._-]+$/'],
            'taille'       => ['required', 'string', Rule::in(['19', '22', '24', '27', '32', 'autre'])],
            'taille_autre' => ['nullable', 'required_if:taille,autre', 'string', 'max:5', 'regex:/^[0-9]{2}([.,][0-9])?$/'],
            'resolution'   => ['required', 'string', Rule::in(['1366x768', '1920x1080', '2560x1440', '3840x2160', 'autre'])],
            'resolution_autre' => ['nullable', 'required_if:resolution,autre', 'string', 'max:20', 'regex:/^[0-9]{3,4}x[0-9]{3,4}$/'],
            'dalle'        => ['nullable', 'string', Rule::in(['IPS', 'TN', 'VA', 'OLED', 'autre'])],
            'dalle_autre'  => ['nullable', 'required_if:dalle,autre', 'string', 'max:30', 'regex:/^[A-Za-z0-9 ._-]+$/'],
            'taux_rafraichissement' => ['nullable', 'string', 'max:3', 'regex:/^[0-9]{2,3}$/'],
            'emplacement'  => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9 ._()\/-]+$/'],
            'date_achat'   => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $ecran->update($this->donneesEcran($request));

        return redirect()->route('ecrans.index')
                         ->with('success', __('messages.materiel_modifie', ['type' => 'Ecran']));
    }

    public function destroy(Ecran $ecran)
    {
        $ecran->delete();

        return redirect()->route('ecrans.index')
                         ->with('success', __('messages.materiel_supprime', ['type' => 'Ecran']));
    }

    public function signalerPanne(Ecran $ecran)
    {
        $ecran->update(['etat' => 'en_panne']);

        return back()->with('success', __('messages.materiel_signale_panne', ['type' => 'Ecran']));
    }

    private function donneesEcran(Request $request): array
    {
        $donnees = $request->except(['taille_autre', 'resolution_autre', 'dalle_autre']);

        if ($request->taille === 'autre') {
            $donnees['taille'] = $request->taille_autre;
        }

        if ($request->resolution === 'autre') {
            $donnees['resolution'] = $request->resolution_autre;
        }

        if ($request->dalle === 'autre') {
            $donnees['dalle'] = $request->dalle_autre;
        }

        $donnees['taille'] = $this->normaliserTaille($donnees['taille'] ?? null);
        $donnees['taux_rafraichissement'] = $this->normaliserRafraichissement($request->taux_rafraichissement);

        return $donnees;
    }

    private function normaliserTaille(?string $taille): ?string
    {
        if (! $taille) {
            return null;
        }

        return str_replace(',', '.', $taille) . ' pouces';
    }

    private function normaliserRafraichissement(?string $taux): ?string
    {
        if (! $taux) {
            return null;
        }

        return $taux . ' Hz';
    }

    public function marquerRepare(Ecran $ecran)
    {
        $ecran->update(['etat' => 'disponible']);

        return back()->with('success', __('messages.materiel_marque_disponible', ['type' => 'Ecran']));
    }
}
