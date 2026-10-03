<?php

namespace App\Http\Controllers;

use App\Models\Casque;
use App\Models\Materiel;
use App\Services\AffectationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CasqueController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->get('search', '');
        $etat      = $request->get('etat', '');

        $casques = Casque::with(['materiel', 'affectations.personnel.user'])
            ->when($recherche, function ($query) use ($recherche) {
                $query->where(function ($query) use ($recherche) {
                    $query->where('numero_serie', 'like', '%'.$recherche.'%')
                          ->orWhereHas('materiel', function ($query) use ($recherche) {
                              $query->where('nom', 'like', '%'.$recherche.'%')
                                    ->orWhere('marque', 'like', '%'.$recherche.'%');
                          });
                });
            })
            ->when($etat, function ($query) use ($etat) {
                $query->whereHas('materiel', function ($query) use ($etat) {
                    $query->where('etat', $etat);
                });
            })
            ->get();

        $total       = Casque::count();
        $disponibles = Casque::whereHas('materiel', fn ($query) => $query->where('etat', 'disponible'))->count();
        $affectes    = Casque::whereHas('materiel', fn ($query) => $query->where('etat', 'affecte'))->count();
        $enPanne     = Casque::whereHas('materiel', fn ($query) => $query->where('etat', 'en_panne'))->count();

        return view('peripheriques.casques', compact(
            'casques', 'total', 'disponibles', 'affectes', 'enPanne'
        ));
    }

    public function store(Request $request, AffectationService $affectationService)
    {
        $request->validate($this->reglesValidation(true));

        $personnel = null;
        $dateFinPrevue = null;

        if ($request->etat === 'affecte') {
            if (! $request->filled('a_qui_id')) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.collaborateur_obligatoire_affectation', ['type' => 'casque'])])
                    ->withInput();
            }

            $personnel = \App\Models\Personnel::where('user_id', $request->a_qui_id)->first();

            if (! $personnel) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.collaborateur_introuvable')])
                    ->withInput();
            }

            $dateFinPrevue = $affectationService->validerDateFinCreationMateriel($request, $personnel);

            if ($affectationService->existeAffectationActivePourType($personnel->id, Casque::class)) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.collaborateur_deja_materiel_affecte', ['type' => 'casque'])])
                    ->withInput();
            }
        }

        DB::transaction(function () use ($request, $personnel, $dateFinPrevue) {
            $materiel = Materiel::create($this->donneesMateriel($request, true));

            $donnees = $this->donneesCasque($request, true);
            $donnees['materiel_id'] = $materiel->id;

            $casque = Casque::create($donnees);

            if ($personnel) {
                \App\Models\Affectation::create([
                    'personnel_id'  => $personnel->id,
                    'materiel_id'   => $materiel->id,
                    'date_debut'    => now(),
                    'date_fin'      => $dateFinPrevue,
                    'statut'        => 'active',
                    'ticket_id'     => null,
                ]);
            }
        });

        return redirect()->route('casques.index')
                         ->with('success', __('messages.materiel_ajoute', ['type' => 'Casque']));
    }

    public function update(Request $request, Casque $casque)
    {
        $request->validate($this->reglesValidation(false));

        DB::transaction(function () use ($request, $casque) {
            $casque->materiel()->update($this->donneesMateriel($request, true));
            $casque->update($this->donneesCasque($request, false));
        });

        return redirect()->route('casques.index')
                         ->with('success', __('messages.materiel_modifie', ['type' => 'Casque']));
    }

    public function destroy(Casque $casque, AffectationService $affectationService)
    {
        if ($casque->materiel) {
            $affectationService->verifierSuppressionAutorisee($casque->materiel, 'Casque');
        }

        DB::transaction(function () use ($casque) {
            if ($casque->materiel) {
                $casque->materiel->delete();
            } else {
                $casque->delete();
            }
        });

        return redirect()->route('casques.index')
                         ->with('success', __('messages.materiel_supprime', ['type' => 'Casque']));
    }

    public function signalerPanne(Casque $casque, AffectationService $affectationService)
    {
        $affectationService->verifierMiseEnPanneAutorisee($casque->materiel, 'Casque');
        $casque->materiel()->update(['etat' => 'en_panne']);

        return back()->with('success', __('messages.materiel_signale_panne', ['type' => 'Casque']));
    }

    public function marquerRepare(Casque $casque)
    {
        $casque->materiel()->update(['etat' => 'disponible']);

        return back()->with('success', __('messages.materiel_marque_disponible', ['type' => 'Casque']));
    }

    private function reglesValidation(bool $creation): array
    {
        $regles = [
            'nom'             => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9 ._+-]+$/'],
            'marque'          => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9 ._+-]+$/'],
            'connexion'       => ['required', Rule::in(['bluetooth', 'filaire', 'sans_fil', 'autre'])],
            'connexion_autre' => ['nullable', 'required_if:connexion,autre', 'string', 'max:30', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'micro'           => ['nullable', 'boolean'],
            'reduction_bruit' => ['nullable', 'boolean'],
            'etat'            => ['nullable', Rule::in(['disponible', 'affecte', 'en_panne'])],
            'emplacement'     => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'date_achat'      => ['nullable', 'date', 'before_or_equal:today'],
        ];

        if ($creation) {
            $regles['numero_serie'] = ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:casques,numero_serie'];
        }

        return $regles;
    }

    private function donneesMateriel(Request $request, bool $avecEtat): array
    {
        $donnees = [
            'type_materiel' => 'casque',
            'nom'           => $request->nom,
            'marque'        => $request->marque,
            'emplacement'   => $request->emplacement,
            'date_achat'    => $request->date_achat,
        ];

        if ($avecEtat) {
            $donnees['etat'] = $request->etat ?: 'disponible';
        }

        return $donnees;
    }

    private function donneesCasque(Request $request, bool $creation): array
    {
        $donnees = [
            'connexion'       => $request->connexion,
            'micro'           => $request->boolean('micro'),
            'reduction_bruit' => $request->boolean('reduction_bruit'),
        ];

        if ($creation) {
            $donnees['numero_serie'] = $request->numero_serie;
        }

        if ($request->connexion === 'autre') {
            $donnees['connexion'] = $request->connexion_autre;
        }

        return $donnees;
    }
}
