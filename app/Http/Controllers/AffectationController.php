<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Models\Casque;
use App\Models\Clavier;
use App\Models\Ecran;
use App\Models\MiniPc;
use App\Models\PcPortable;
use App\Models\Souris;
use App\Services\AffectationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AffectationController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->get('search', '');
        $statut = $request->get('statut', '');
        $aujourdhui = now()->toDateString();
        $dansSeptJours = now()->addDays(7)->toDateString();

        $affectations = Affectation::with([
                'personnel.user',
                'materiel.pcPortable',
                'materiel.miniPc',
                'materiel.ecran',
                'materiel.clavier',
                'materiel.souris',
                'materiel.casque',
            ])
            ->when($recherche, function ($query) use ($recherche) {
                $query->whereHas('personnel.user', function ($q) use ($recherche) {
                    $q->where('name', 'like', '%' . $recherche . '%');
                });
            })
            ->when($statut, function ($query) use ($statut, $aujourdhui, $dansSeptJours) {
                match ($statut) {
                    'active' => $query->where('statut', 'active'),
                    'en_cours' => $query->where('statut', 'active')
                        ->where(function ($q) use ($dansSeptJours) {
                            $q->whereNull('date_fin')
                                ->orWhereDate('date_fin', '>', $dansSeptJours);
                        }),
                    'echeance_proche' => $query->where('statut', 'active')
                        ->whereBetween('date_fin', [$aujourdhui, $dansSeptJours]),
                    'en_retard' => $query->where('statut', 'active')
                        ->whereDate('date_fin', '<', $aujourdhui),
                    'rendu' => $query->where('statut', 'rendu'),
                    default => null,
                };
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $total = Affectation::count();
        $actives = Affectation::where('statut', 'active')->count();
        $enCours = Affectation::where('statut', 'active')
            ->where(function ($query) use ($dansSeptJours) {
                $query->whereNull('date_fin')
                    ->orWhereDate('date_fin', '>', $dansSeptJours);
            })
            ->count();
        $echeanceProche = Affectation::where('statut', 'active')
            ->whereBetween('date_fin', [$aujourdhui, $dansSeptJours])
            ->count();
        $enRetard = Affectation::where('statut', 'active')
            ->whereDate('date_fin', '<', $aujourdhui)
            ->count();
        $rendus = Affectation::where('statut', 'rendu')->count();

        return view('activite.affectations', compact(
            'affectations',
            'total',
            'actives',
            'enCours',
            'echeanceProche',
            'enRetard',
            'rendus'
        ));
    }

    public function store(Request $request, AffectationService $affectationService)
    {
        $request->validate([
            'personnel_id' => 'required|exists:personnels,id',
            'materiel_type' => 'required|string',
            'materiel_numero_serie' => 'required|string|max:100',
            'date_debut' => 'required|date',
        ]);

        $classeMateriel = $this->classeMateriel($request->materiel_type);

        if (! $classeMateriel) {
            return back()->withErrors(['materiel_type' => __('messages.materiel_type_invalide')]);
        }

        if ($affectationService->existeAffectationActivePourType($request->personnel_id, $classeMateriel)) {
            $libelleType = $affectationService->libelleType($classeMateriel);

            return back()
                ->withErrors(['personnel_id' => __('messages.collaborateur_deja_type_affecte', ['type' => $libelleType])])
                ->withInput();
        }

        $materiel = $classeMateriel::where('numero_serie', $request->materiel_numero_serie)
            ->with('materiel')
            ->first();

        if (! $materiel || ! $materiel->materiel_id) {
            return back()
                ->withErrors(['materiel_numero_serie' => __('messages.materiel_numero_serie_introuvable')])
                ->withInput();
        }

        DB::transaction(function () use ($request, $materiel) {
            Affectation::create([
                'personnel_id' => $request->personnel_id,
                'materiel_id' => $materiel->materiel_id,
                'date_debut' => $request->date_debut,
                'date_fin' => null,
                'statut' => 'active',
            ]);

            $this->mettreAJourEtatMateriel($materiel, 'affecte');
        });

        return redirect()->route('affectations.index')->with('success', __('messages.affectation_creee'));
    }

    public function update(Request $request, Affectation $affectation, AffectationService $affectationService)
    {
        $request->validate([
            'personnel_id' => 'required|exists:personnels,id',
            'date_debut' => 'required|date',
        ]);

        $affectation->loadMissing('materiel');

        if (
            $affectation->statut === 'active'
            && $affectation->materiel
            && $affectationService->existeAffectationActivePourType(
                $request->personnel_id,
                $affectation->materiel->type_materiel,
                null,
                $affectation->id
            )
        ) {
            $libelleType = $affectationService->libelleType($affectation->materiel->type_materiel);

            return back()
                ->withErrors(['personnel_id' => __('messages.collaborateur_deja_type_affecte', ['type' => $libelleType])])
                ->withInput();
        }

        $affectation->update([
            'personnel_id' => $request->personnel_id,
            'date_debut' => $request->date_debut,
        ]);

        return redirect()->route('affectations.index')->with('success', __('messages.affectation_modifiee'));
    }

    public function validerRetour(Affectation $affectation)
    {
        if ($affectation->statut === 'rendu') {
            return redirect()
                ->route('affectations.index')
                ->with('info', __('messages.affectation_deja_rendue'));
        }

        DB::transaction(function () use ($affectation) {
            $affectation->update([
                'statut' => 'rendu',
                'date_fin' => now(),
            ]);

            $materiel = $affectation->materiel;

            if ($materiel) {
                $this->mettreAJourEtatMateriel($materiel, 'disponible');
            }
        });

        return redirect()->route('affectations.index')->with('success', __('messages.affectation_retour_valide'));
    }

    public function destroy(Affectation $affectation)
    {
        DB::transaction(function () use ($affectation) {
            if ($affectation->statut === 'active') {
                $materiel = $affectation->materiel;

                if ($materiel) {
                    $this->mettreAJourEtatMateriel($materiel, 'disponible');
                }
            }

            $affectation->delete();
        });

        return redirect()->route('affectations.index')->with('success', __('messages.affectation_supprimee'));
    }

    private function classeMateriel(string $type): ?string
    {
        $typesMateriel = [
            'pc-portable' => PcPortable::class,
            'mini-pc' => MiniPc::class,
            'ecran' => Ecran::class,
            'clavier' => Clavier::class,
            'souris' => Souris::class,
            'casque' => Casque::class,
        ];

        return $typesMateriel[$type] ?? null;
    }

    private function mettreAJourEtatMateriel(object $materiel, string $etat): void
    {
        if (method_exists($materiel, 'materiel') && $materiel->materiel) {
            $materiel->materiel()->update(['etat' => $etat]);

            return;
        }

        $materiel->etat = $etat;
        $materiel->save();
    }
}
