<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Services\AffectationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AffectationController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->get('search', '');
        $statut = $request->get('statut', '');

        $affectations = Affectation::with([
                'personnel.user',
                'materiel',
            ])
            ->when($recherche, function ($query) use ($recherche) {
                $query->whereHas('personnel.user', function ($q) use ($recherche) {
                    $q->where('name', 'like', '%' . $recherche . '%');
                });
            })
            ->when($statut, function ($query) use ($statut) {
                $query->where('statut', $statut);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $total = Affectation::count();
        $actives = Affectation::where('statut', 'active')->count();
        $cloturees = Affectation::where('statut', 'cloturee')->count();

        return view('activite.affectations', compact(
            'affectations',
            'total',
            'actives',
            'cloturees'
        ));
    }

    public function store(Request $request, AffectationService $affectationService)
    {
        $request->validate([
            'personnel_id' => 'required|exists:personnels,id',
            'materiel_type' => 'required|string',
            'materiel_id' => 'required|integer',
            'date_debut' => 'required|date',
        ]);

        $classeMateriel = $this->classeMateriel($request->materiel_type);

        if (! $classeMateriel) {
            return back()->withErrors(['materiel_type' => __('messages.materiel_type_invalide')]);
        }

        $sousType = $affectationService->sousTypeDepuisSlug($request->materiel_type);

        if ($affectationService->existeAffectationActivePourType($request->personnel_id, $classeMateriel, $sousType)) {
            $libelleType = $affectationService->libelleType($classeMateriel, $sousType);

            return back()
                ->withErrors(['personnel_id' => __('messages.collaborateur_deja_type_affecte', ['type' => $libelleType])])
                ->withInput();
        }

        Affectation::create([
            'personnel_id' => $request->personnel_id,
            'materiel_type' => $classeMateriel,
            'materiel_id' => $request->materiel_id,
            'date_debut' => $request->date_debut,
            'date_fin' => null,
            'statut' => 'active',
        ]);

        $materiel = $classeMateriel::find($request->materiel_id);

        if ($materiel) {
            $materiel->etat = 'affecte';
            $materiel->save();
        }

        return redirect()->route('affectations.index')->with('success', __('messages.affectation_creee'));
    }

    public function update(Request $request, Affectation $affectation)
    {
        $request->validate([
            'personnel_id' => 'required|exists:personnels,id',
            'date_debut' => 'required|date',
        ]);

        $affectation->update([
            'personnel_id' => $request->personnel_id,
            'date_debut' => $request->date_debut,
        ]);

        return redirect()->route('affectations.index')->with('success', __('messages.affectation_modifiee'));
    }

    public function cloturer(Affectation $affectation)
    {
        if ($affectation->statut === 'cloturee') {
            return redirect()
                ->route('affectations.index')
                ->with('info', __('messages.affectation_deja_cloturee'));
        }

        DB::transaction(function () use ($affectation) {
            $affectation->update([
                'statut' => 'cloturee',
                'date_fin' => now(),
            ]);

            $materiel = $affectation->materiel;

            if ($materiel) {
                $materiel->etat = 'disponible';
                $materiel->save();
            }
        });

        return redirect()->route('affectations.index')->with('success', __('messages.affectation_cloturee'));
    }

    private function classeMateriel(string $type): ?string
    {
        $typesMateriel = [
            'pc-portable' => \App\Models\PcPortable::class,
            'mini-pc' => \App\Models\MiniPc::class,
            'ecran' => \App\Models\Ecran::class,
            'clavier' => \App\Models\Peripherique::class,
            'souris' => \App\Models\Peripherique::class,
            'casque' => \App\Models\Peripherique::class,
        ];

        return $typesMateriel[$type] ?? null;
    }
}
