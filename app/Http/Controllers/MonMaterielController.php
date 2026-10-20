<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Models\Emprunt;
use App\Services\MaterielService;

class MonMaterielController extends Controller
{
    // Recoit et garde les objets ou les valeurs utilises par les autres methodes.
    public function __construct(private readonly MaterielService $materielService)
    {
    }

    // Recupere la liste et les compteurs des MonMateriel pour afficher la page.
    public function index()
    {
        $user = auth()->user()->loadMissing(['personnel', 'etudiant']);

        if ($user->personnel) {
            $requete = Affectation::with($this->materielService->relationsAffectation())
                ->where('personnel_id', $user->personnel->user_id)
                ->orderByDesc('created_at');

            $typeUtilisateur = 'personnel';
            $materielsActifs = $this->formaterAffectations(
                (clone $requete)->where('statut', 'active')->get()
            );
            $historique = $this->formaterAffectations(
                (clone $requete)->where('statut', 'rendu')->get()
            );
        } else {
            $requete = Emprunt::with(['pcPortable.materiel'])
                ->where('etudiant_id', $user->etudiant->user_id)
                ->orderByDesc('created_at');

            $typeUtilisateur = 'etudiant';
            $materielsActifs = $this->formaterEmprunts(
                (clone $requete)->where('statut', '!=', 'rendu')->get()
            );
            $historique = $this->formaterEmprunts(
                (clone $requete)->where('statut', 'rendu')->get()
            );
        }

        return view('mon-materiel', compact('materielsActifs', 'historique', 'typeUtilisateur'));
    }

    // Prepare les informations des affectations pour les cartes et l historique web.
    private function formaterAffectations($affectations)
    {
        return $affectations->map(function ($affectation) {
            $materiel = $affectation->materiel;
            $materielSpecifique = $this->materielService->specifique($materiel);

            return [
                'origine' => 'Affectation',
                'type' => $this->materielService->libelle($materiel?->type_materiel),
                'nom' => $materiel->nom ?? '-',
                'marque' => $materiel->marque ?? '-',
                'numero_serie' => $materielSpecifique->numero_serie ?? '-',
                'etat' => $materiel->etat ?? '-',
                'emplacement' => $materiel->emplacement ?? '-',
                'date_debut' => $affectation->date_debut,
                'date_fin' => $affectation->date_fin,
                'date_retour' => $affectation->date_retour,
                'statut' => $affectation->statut === 'rendu'
                    ? 'rendu'
                    : $this->statutSelonEcheance($affectation->date_fin, 'active'),
            ];
        });
    }

    // Prepare les informations des emprunts pour les cartes et l historique web.
    private function formaterEmprunts($emprunts)
    {
        return $emprunts->map(function ($emprunt) {
            $pcPortable = $emprunt->pcPortable;
            $materiel = $pcPortable?->materiel;

            return [
                'origine' => 'Emprunt',
                'type' => 'PC portable',
                'nom' => $materiel->nom ?? '-',
                'marque' => $materiel->marque ?? '-',
                'numero_serie' => $pcPortable?->numero_serie ?? '-',
                'etat' => $materiel->etat ?? '-',
                'emplacement' => $materiel->emplacement ?? '-',
                'date_debut' => $emprunt->date_debut,
                'date_fin' => $emprunt->date_fin_prevue,
                'date_retour' => $emprunt->date_retour,
                'statut' => $emprunt->statut === 'rendu'
                    ? 'rendu'
                    : $this->statutSelonEcheance($emprunt->date_fin_prevue, 'en_cours'),
            ];
        });
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
