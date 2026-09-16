<?php

namespace App\Http\Controllers;

use App\Models\Materiel;
use App\Models\Souris;
use App\Services\AffectationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SourisController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->get('search', '');
        $etat      = $request->get('etat', '');

        $souris = Souris::with(['materiel', 'affectations.personnel.user', 'emprunts.etudiant.user'])
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

        $total       = Souris::count();
        $disponibles = Souris::whereHas('materiel', fn ($query) => $query->where('etat', 'disponible'))->count();
        $affectes    = Souris::whereHas('materiel', fn ($query) => $query->where('etat', 'affecte'))->count();
        $enPanne     = Souris::whereHas('materiel', fn ($query) => $query->where('etat', 'en_panne'))->count();

        return view('peripheriques.souris', compact(
            'souris', 'total', 'disponibles', 'affectes', 'enPanne'
        ));
    }

    public function store(Request $request, AffectationService $affectationService)
    {
        $request->validate($this->reglesValidation(true));

        $personnel = null;
        $etudiant  = null;

        if ($request->etat === 'affecte') {
            if (! $request->filled('a_qui_id')) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.collaborateur_obligatoire_affectation', ['type' => 'souris'])])
                    ->withInput();
            }

            $personnel = \App\Models\Personnel::where('user_id', $request->a_qui_id)->first();

            if (! $personnel) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.collaborateur_introuvable')])
                    ->withInput();
            }

            if ($affectationService->existeAffectationActivePourType($personnel->id, Souris::class)) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.collaborateur_deja_materiel_affecte', ['type' => 'souris'])])
                    ->withInput();
            }
        } elseif ($request->etat === 'emprunte') {
            if (! $request->filled('a_qui_id')) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.etudiant_obligatoire_emprunt', ['type' => 'souris'])])
                    ->withInput();
            }

            $etudiant = \App\Models\Etudiant::where('user_id', $request->a_qui_id)->first();

            if (! $etudiant) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.etudiant_introuvable')])
                    ->withInput();
            }
        }

        DB::transaction(function () use ($request, $personnel, $etudiant) {
            $materiel = Materiel::create($this->donneesMateriel($request, true));

            $donnees = $this->donneesSouris($request, true);
            $donnees['materiel_id'] = $materiel->id;

            $souris = Souris::create($donnees);

            if ($personnel) {
                \App\Models\Affectation::create([
                    'personnel_id'  => $personnel->id,
                    'materiel_id'   => $materiel->id,
                    'date_debut'    => now(),
                    'statut'        => 'active',
                    'ticket_id'     => null,
                ]);
            } elseif ($etudiant) {
                \App\Models\Emprunt::create([
                    'etudiant_id'    => $etudiant->id,
                    'materiel_id'    => $materiel->id,
                    'date_debut'     => now(),
                    'date_fin_prevue'=> now()->addMonths(3),
                    'statut'         => 'en_cours',
                    'ticket_id'      => null,
                ]);
            }
        });

        return redirect()->route('souris.index')
                         ->with('success', __('messages.materiel_ajoute', ['type' => 'Souris']));
    }

    public function update(Request $request, Souris $souris)
    {
        $request->validate($this->reglesValidation(false));

        DB::transaction(function () use ($request, $souris) {
            $souris->materiel()->update($this->donneesMateriel($request, true));
            $souris->update($this->donneesSouris($request, false));
        });

        return redirect()->route('souris.index')
                         ->with('success', __('messages.materiel_modifie', ['type' => 'Souris']));
    }

    public function destroy(Souris $souris)
    {
        DB::transaction(function () use ($souris) {
            if ($souris->materiel) {
                $souris->materiel->delete();
            } else {
                $souris->delete();
            }
        });

        return redirect()->route('souris.index')
                         ->with('success', __('messages.materiel_supprime', ['type' => 'Souris']));
    }

    public function signalerPanne(Souris $souris)
    {
        $souris->materiel()->update(['etat' => 'en_panne']);

        return back()->with('success', __('messages.materiel_signale_panne', ['type' => 'Souris']));
    }

    public function marquerRepare(Souris $souris)
    {
        $souris->materiel()->update(['etat' => 'disponible']);

        return back()->with('success', __('messages.materiel_marque_disponible', ['type' => 'Souris']));
    }

    private function reglesValidation(bool $creation): array
    {
        $regles = [
            'nom'             => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9 ._+-]+$/'],
            'marque'          => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9 ._+-]+$/'],
            'connexion'       => ['required', Rule::in(['bluetooth', 'filaire', 'sans_fil', 'autre'])],
            'connexion_autre' => ['nullable', 'required_if:connexion,autre', 'string', 'max:30', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'dpi'             => ['nullable', 'integer', 'min:100', 'max:30000'],
            'nombre_boutons'  => ['nullable', 'integer', 'min:1', 'max:20'],
            'etat'            => ['nullable', Rule::in(['disponible', 'affecte', 'emprunte', 'en_panne'])],
            'emplacement'     => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'date_achat'      => ['nullable', 'date', 'before_or_equal:today'],
        ];

        if ($creation) {
            $regles['numero_serie'] = ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:souris,numero_serie'];
        }

        return $regles;
    }

    private function donneesMateriel(Request $request, bool $avecEtat): array
    {
        $donnees = [
            'type_materiel' => 'souris',
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

    private function donneesSouris(Request $request, bool $creation): array
    {
        $donnees = [
            'connexion'      => $request->connexion,
            'dpi'            => $request->dpi,
            'nombre_boutons' => $request->nombre_boutons,
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
