<?php

namespace App\Services;

use App\Models\Materiel;

class MaterielService
{
    // Recupere la fiche du PC, de l'ecran ou d'un autre type a partir du materiel.
    public function specifique(?Materiel $materiel): mixed
    {
        return match ($materiel?->type_materiel) {
            'pc_portable' => $materiel->pcPortable,
            'mini_pc' => $materiel->miniPc,
            'ecran' => $materiel->ecran,
            'imprimante' => $materiel->imprimante,
            'clavier' => $materiel->clavier,
            'souris' => $materiel->souris,
            'casque' => $materiel->casque,
            default => null,
        };
    }

    // Donne le nom a afficher pour un type, par exemple PC portable pour pc_portable.
    public function libelle(?string $type): string
    {
        return [
            'pc_portable' => 'PC portable',
            'mini_pc' => 'Mini PC',
            'ecran' => 'Ecran',
            'imprimante' => 'Imprimante',
            'clavier' => 'Clavier',
            'souris' => 'Souris',
            'casque' => 'Casque',
        ][$type] ?? 'Materiel';
    }

    // Prepare le texte contenant le nom du materiel et son numero de serie.
    public function libelleAvecNumero(?Materiel $materiel): string
    {
        if (! $materiel) {
            return 'Materiel';
        }

        $numeroSerie = $this->specifique($materiel)?->numero_serie;

        return $numeroSerie
            ? $materiel->nom.' ('.$numeroSerie.')'
            : $materiel->nom;
    }

    // Donne la liste des liens a suivre pour recuperer les informations des PC, ecrans, claviers, etc.
    public function relationsAffectation(): array
    {
        return [
            'materiel.pcPortable',
            'materiel.miniPc',
            'materiel.ecran',
            'materiel.imprimante',
            'materiel.clavier',
            'materiel.souris',
            'materiel.casque',
        ];
    }
}
