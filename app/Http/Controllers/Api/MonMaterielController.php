<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Affectation;
use App\Models\Emprunt;
use App\Services\MaterielService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonMaterielController extends Controller
{
    // Recoit et garde les objets ou les valeurs utilises par les autres methodes.
    public function __construct(private readonly MaterielService $materielService)
    {
    }

    // Renvoie les donnees MonMateriel autorisees au compte connecte au format JSON.
    public function index(Request $request): JsonResponse
    {
        $utilisateur = $request->user()->loadMissing(['personnel', 'etudiant']);

        if ($utilisateur->personnel) {
            $materiels = Affectation::query()
                ->with($this->materielService->relationsAffectation())
                ->where('personnel_id', $utilisateur->personnel->user_id)
                ->where('statut', 'active')
                ->latest()
                ->get()
                ->map(fn (Affectation $affectation) => $this->formaterAffectation($affectation));

            $typeUtilisateur = 'personnel';
        } else {
            $materiels = Emprunt::query()
                ->with('pcPortable.materiel')
                ->where('etudiant_id', $utilisateur->etudiant->user_id)
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

    // Expose les informations utiles d une affectation dans la reponse de l API mobile.
    private function formaterAffectation(Affectation $affectation): array
    {
        $materiel = $affectation->materiel;
        $materielSpecifique = $this->materielService->specifique($materiel);

        return [
            'origine' => 'affectation',
            'type' => $this->materielService->libelle($materiel?->type_materiel),
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

    // Expose les informations utiles d un emprunt dans la reponse de l API mobile.
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

    // Calcule le statut affiche selon la date de retour, sans modifier la base.
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
