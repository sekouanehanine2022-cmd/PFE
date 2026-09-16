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
                ->orderByDesc('created_at')
                ->get();

            $typeUtilisateur = 'personnel';
            $materiels = $this->formaterAffectations($lignesMateriel);
        } else {
            $lignesMateriel = Emprunt::with([
                    'materiel.pcPortable',
                    'materiel.miniPc',
                    'materiel.ecran',
                    'materiel.clavier',
                    'materiel.souris',
                    'materiel.casque',
                ])
                ->where('etudiant_id', $user->etudiant->id)
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
                'statut' => $affectation->statut,
            ];
        });
    }

    private function formaterEmprunts($emprunts)
    {
        return $emprunts->map(function ($emprunt) {
            $materiel = $emprunt->materiel;
            $materielSpecifique = $this->materielSpecifique($materiel);

            return [
                'origine' => 'Emprunt',
                'type' => $this->libelleTypeMateriel($materiel?->type_materiel),
                'nom' => $materiel->nom ?? '-',
                'marque' => $materiel->marque ?? '-',
                'numero_serie' => $materielSpecifique->numero_serie ?? '-',
                'etat' => $materiel->etat ?? '-',
                'emplacement' => $materiel->emplacement ?? '-',
                'date_debut' => $emprunt->date_debut,
                'date_fin' => $emprunt->date_fin_prevue,
                'statut' => $emprunt->statut,
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
}
