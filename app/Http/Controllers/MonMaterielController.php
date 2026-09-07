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
            $lignesMateriel = Affectation::with('materiel')
                ->where('personnel_id', $user->personnel->id)
                ->orderByDesc('created_at')
                ->get();

            $typeUtilisateur = 'personnel';
            $materiels = $this->formaterAffectations($lignesMateriel);
        } else {
            $lignesMateriel = Emprunt::with('materiel')
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
            return [
                'origine' => 'Affectation',
                'type' => class_basename($affectation->materiel_type),
                'nom' => $affectation->materiel->nom ?? '-',
                'reference' => $affectation->materiel->reference ?? '-',
                'marque' => $affectation->materiel->marque ?? '-',
                'numero_serie' => $affectation->materiel->numero_serie ?? '-',
                'etat' => $affectation->materiel->etat ?? '-',
                'emplacement' => $affectation->materiel->emplacement ?? '-',
                'date_debut' => $affectation->date_debut,
                'date_fin' => $affectation->date_fin,
                'statut' => $affectation->statut,
            ];
        });
    }

    private function formaterEmprunts($emprunts)
    {
        return $emprunts->map(function ($emprunt) {
            return [
                'origine' => 'Emprunt',
                'type' => class_basename($emprunt->materiel_type),
                'nom' => $emprunt->materiel->nom ?? '-',
                'reference' => $emprunt->materiel->reference ?? '-',
                'marque' => $emprunt->materiel->marque ?? '-',
                'numero_serie' => $emprunt->materiel->numero_serie ?? '-',
                'etat' => $emprunt->materiel->etat ?? '-',
                'emplacement' => $emprunt->materiel->emplacement ?? '-',
                'date_debut' => $emprunt->date_debut,
                'date_fin' => $emprunt->date_fin_prevue,
                'statut' => $emprunt->statut,
            ];
        });
    }
}
