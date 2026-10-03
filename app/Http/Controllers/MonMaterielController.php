<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Models\Emprunt;

class MonMaterielController extends Controller
{
    public function index()
    {
        $user = auth()->user()->loadMissing(['personnel', 'etudiant']);

        if ($user->personnel) {
            $lignesMateriel = Affectation::with([
                    'materiel.pcPortable',
                    'materiel.miniPc',
                    'materiel.ecran',
                    'materiel.clavier',
                    'materiel.souris',
                    'materiel.casque',
                ])
                ->where('personnel_id', $user->personnel->id)
                ->where('statut', 'active')
                ->orderByDesc('created_at')
                ->get();

            $typeUtilisateur = 'personnel';
            $materiels = $this->formaterAffectations($lignesMateriel);
        } else {
            $lignesMateriel = Emprunt::with([
                    'pcPortable.materiel',
                ])
                ->where('etudiant_id', $user->etudiant->id)
                ->where('statut', '!=', 'rendu')
                ->orderByDesc('created_at')
                ->get();

            $typeUtilisateur = 'etudiant';
            $materiels = $this->formaterEmprunts($lignesMateriel);
        }

        return view('mon-materiel', compact('materiels', 'typeUtilisateur'));
    }

    private function formaterAffectations($affectations)
    {
        return $affectations->map(function ($affectation) {
            $materiel = $affectation->materiel;
            $materielSpecifique = $this->materielSpecifique($materiel);

            return [
                'origine' => 'Affectation',
                'type' => $this->libelleTypeMateriel($materiel?->type_materiel),
                'nom' => $materiel->nom ?? '-',
                'marque' => $materiel->marque ?? '-',
                'numero_serie' => $materielSpecifique->numero_serie ?? '-',
                'etat' => $materiel->etat ?? '-',
                'emplacement' => $materiel->emplacement ?? '-',
                'date_debut' => $affectation->date_debut,
                'date_fin' => $affectation->date_fin,
                'statut' => $this->statutSelonEcheance($affectation->date_fin, 'active'),
            ];
        });
    }

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
                'statut' => $this->statutSelonEcheance($emprunt->date_fin_prevue, 'en_cours'),
            ];
        });
    }

    private function materielSpecifique($materiel)
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
