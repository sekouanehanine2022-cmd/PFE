<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Affectation;
use App\Models\Emprunt;
use App\Models\Materiel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonMaterielController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $utilisateur = $request->user()->loadMissing(['personnel', 'etudiant']);

        if ($utilisateur->personnel) {
            $materiels = Affectation::query()
                ->with([
                    'materiel.pcPortable',
                    'materiel.miniPc',
                    'materiel.ecran',
                    'materiel.clavier',
                    'materiel.souris',
                    'materiel.casque',
                ])
                ->where('personnel_id', $utilisateur->personnel->id)
                ->where('statut', 'active')
                ->latest()
                ->get()
                ->map(fn (Affectation $affectation) => $this->formaterAffectation($affectation));

            $typeUtilisateur = 'personnel';
        } else {
            $materiels = Emprunt::query()
                ->with('pcPortable.materiel')
                ->where('etudiant_id', $utilisateur->etudiant->id)
                ->where('statut', '!=', 'rendu')
                ->latest()
                ->get()
                ->map(fn (Emprunt $emprunt) => $this->formaterEmprunt($emprunt));

            $typeUtilisateur = 'etudiant';
        }

        return response()->json([
            'type_utilisateur' => $typeUtilisateur,
            'total' => $materiels->count(),
            'materiels' => $materiels->values(),
        ]);
    }

    private function formaterAffectation(Affectation $affectation): array
    {
        $materiel = $affectation->materiel;
        $materielSpecifique = $this->materielSpecifique($materiel);

        return [
            'origine' => 'affectation',
            'type' => $this->libelleTypeMateriel($materiel?->type_materiel),
            'nom' => $materiel?->nom,
            'marque' => $materiel?->marque,
            'numero_serie' => $materielSpecifique?->numero_serie,
            'etat' => $materiel?->etat,
            'emplacement' => $materiel?->emplacement,
            'date_debut' => $affectation->date_debut?->toDateString(),
            'date_fin' => $affectation->date_fin?->toDateString(),
            'statut' => $this->statutSelonEcheance($affectation->date_fin, 'active'),
        ];
    }

    private function formaterEmprunt(Emprunt $emprunt): array
    {
        $pcPortable = $emprunt->pcPortable;
        $materiel = $pcPortable?->materiel;

        return [
            'origine' => 'emprunt',
            'type' => 'PC portable',
            'nom' => $materiel?->nom,
            'marque' => $materiel?->marque,
            'numero_serie' => $pcPortable?->numero_serie,
            'etat' => $materiel?->etat,
            'emplacement' => $materiel?->emplacement,
            'date_debut' => $emprunt->date_debut?->toDateString(),
            'date_fin' => $emprunt->date_fin_prevue?->toDateString(),
            'statut' => $this->statutSelonEcheance($emprunt->date_fin_prevue, 'en_cours'),
        ];
    }

    private function materielSpecifique(?Materiel $materiel): mixed
    {
        return match ($materiel?->type_materiel) {
            'pc_portable' => $materiel->pcPortable,
            'mini_pc' => $materiel->miniPc,
            'ecran' => $materiel->ecran,
            'clavier' => $materiel->clavier,
            'souris' => $materiel->souris,
            'casque' => $materiel->casque,
            default => null,
        };
    }

    private function libelleTypeMateriel(?string $type): string
    {
        return [
            'pc_portable' => 'PC portable',
            'mini_pc' => 'Mini PC',
            'ecran' => 'Ecran',
            'clavier' => 'Clavier',
            'souris' => 'Souris',
            'casque' => 'Casque',
        ][$type] ?? 'Materiel';
    }

    private function statutSelonEcheance($dateFin, string $statutParDefaut): string
    {
        if (! $dateFin) {
            return $statutParDefaut;
        }

        $aujourdhui = now()->startOfDay();
        $echeance = $dateFin->copy()->startOfDay();

        if ($echeance->lt($aujourdhui)) {
            return 'en_retard';
        }

        if ($echeance->lte($aujourdhui->copy()->addDays(7))) {
            return 'echeance_proche';
        }

        return $statutParDefaut;
    }
}
