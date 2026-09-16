<?php

namespace App\Http\Controllers;

use App\Models\Clavier;
use App\Models\Materiel;
use App\Services\AffectationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClavierController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->get('search', '');
        $etat      = $request->get('etat', '');

        $claviers = Clavier::with(['materiel', 'affectations.personnel.user', 'emprunts.etudiant.user'])
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

        $total       = Clavier::count();
        $disponibles = Clavier::whereHas('materiel', fn ($query) => $query->where('etat', 'disponible'))->count();
        $affectes    = Clavier::whereHas('materiel', fn ($query) => $query->where('etat', 'affecte'))->count();
        $enPanne     = Clavier::whereHas('materiel', fn ($query) => $query->where('etat', 'en_panne'))->count();

        return view('peripheriques.claviers', compact(
            'claviers', 'total', 'disponibles', 'affectes', 'enPanne'
        ));
    }

    public function store(Request $request, AffectationService $affectationService)
    {
        $request->validate($this->reglesValidation($request, true));

        $personnel = null;
        $etudiant  = null;

        if ($request->etat === 'affecte') {
            if (! $request->filled('a_qui_id')) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.collaborateur_obligatoire_affectation', ['type' => 'clavier'])])
                    ->withInput();
            }

            $personnel = \App\Models\Personnel::where('user_id', $request->a_qui_id)->first();

            if (! $personnel) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.collaborateur_introuvable')])
                    ->withInput();
            }

            if ($affectationService->existeAffectationActivePourType($personnel->id, Clavier::class)) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.collaborateur_deja_materiel_affecte', ['type' => 'clavier'])])
                    ->withInput();
            }
        } elseif ($request->etat === 'emprunte') {
            if (! $request->filled('a_qui_id')) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.etudiant_obligatoire_emprunt', ['type' => 'clavier'])])
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

            $donnees = $this->donneesClavier($request, true);
            $donnees['materiel_id'] = $materiel->id;

            $clavier = Clavier::create($donnees);

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

        return redirect()->route('claviers.index')
                         ->with('success', __('messages.materiel_ajoute', ['type' => 'Clavier']));
    }

    public function update(Request $request, Clavier $clavier)
    {
        $request->validate($this->reglesValidation($request, false));

        DB::transaction(function () use ($request, $clavier) {
            $clavier->materiel()->update($this->donneesMateriel($request, true));
            $clavier->update($this->donneesClavier($request, false));
        });

        return redirect()->route('claviers.index')
                         ->with('success', __('messages.materiel_modifie', ['type' => 'Clavier']));
    }

    public function destroy(Clavier $clavier)
    {
        DB::transaction(function () use ($clavier) {
            if ($clavier->materiel) {
                $clavier->materiel->delete();
            } else {
                $clavier->delete();
            }
        });

        return redirect()->route('claviers.index')
                         ->with('success', __('messages.materiel_supprime', ['type' => 'Clavier']));
    }

    public function signalerPanne(Clavier $clavier)
    {
        $clavier->materiel()->update(['etat' => 'en_panne']);

        return back()->with('success', __('messages.materiel_signale_panne', ['type' => 'Clavier']));
    }

    public function marquerRepare(Clavier $clavier)
    {
        $clavier->materiel()->update(['etat' => 'disponible']);

        return back()->with('success', __('messages.materiel_marque_disponible', ['type' => 'Clavier']));
    }

    private function reglesValidation(Request $request, bool $creation): array
    {
        $regles = [
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
        ];

        if ($creation) {
            $regles['numero_serie'] = ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:claviers,numero_serie'];
        }

        return $regles;
    }

    private function donneesMateriel(Request $request, bool $avecEtat): array
    {
        $donnees = [
            'type_materiel' => 'clavier',
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

    private function donneesClavier(Request $request, bool $creation): array
    {
        $donnees = [
            'connexion'       => $request->connexion,
            'disposition'     => $request->disposition,
            'retro_eclairage' => $request->retro_eclairage ?? false,
        ];

        if ($creation) {
            $donnees['numero_serie'] = $request->numero_serie;
        }

        if ($request->connexion === 'autre') {
            $donnees['connexion'] = $request->connexion_autre;
        }

        if ($request->disposition === 'autre') {
            $donnees['disposition'] = $request->disposition_autre;
        }

        return $donnees;
    }
}
