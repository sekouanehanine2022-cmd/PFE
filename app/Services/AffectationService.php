<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\Casque;
use App\Models\Clavier;
use App\Models\Ecran;
use App\Models\MiniPc;
use App\Models\PcPortable;
use App\Models\Souris;

class AffectationService
{
    public function existeAffectationActivePourType(
        int $personnelId,
        string $materielType,
        ?string $sousType = null,
        ?int $affectationIgnoreeId = null
    ): bool {
        $typeMateriel = $this->typeMaterielCentral($materielType);

        if (! $typeMateriel) {
            return false;
        }

        return Affectation::query()
            ->where('personnel_id', $personnelId)
            ->where('statut', 'active')
            ->whereHas('materiel', function ($query) use ($typeMateriel) {
                $query->where('type_materiel', $typeMateriel);
            })
            ->when($affectationIgnoreeId, function ($query) use ($affectationIgnoreeId) {
                $query->where('id', '!=', $affectationIgnoreeId);
            })
            ->exists();
    }

    public function typeDepuisSlug(string $slug): ?string
    {
        return [
            'pc-portable' => PcPortable::class,
            'mini-pc' => MiniPc::class,
            'ecran' => Ecran::class,
            'clavier' => Clavier::class,
            'souris' => Souris::class,
            'casque' => Casque::class,
        ][$slug] ?? null;
    }

    public function sousTypeDepuisSlug(string $slug): ?string
    {
        return null;
    }

    public function libelleType(string $materielType, ?string $sousType = null): string
    {
        return [
            PcPortable::class => 'PC portable',
            MiniPc::class => 'Mini PC',
            Ecran::class => 'ecran',
            Clavier::class => 'clavier',
            Souris::class => 'souris',
            Casque::class => 'casque',
            'pc_portable' => 'PC portable',
            'mini_pc' => 'Mini PC',
            'ecran' => 'ecran',
            'clavier' => 'clavier',
            'souris' => 'souris',
            'casque' => 'casque',
        ][$materielType] ?? 'materiel';
    }

    private function typeMaterielCentral(string $materielType): ?string
    {
        return [
            PcPortable::class => 'pc_portable',
            MiniPc::class => 'mini_pc',
            Ecran::class => 'ecran',
            Clavier::class => 'clavier',
            Souris::class => 'souris',
            Casque::class => 'casque',
            'pc-portable' => 'pc_portable',
            'mini-pc' => 'mini_pc',
            'ecran' => 'ecran',
            'clavier' => 'clavier',
            'souris' => 'souris',
            'casque' => 'casque',
            'pc_portable' => 'pc_portable',
            'mini_pc' => 'mini_pc',
        ][$materielType] ?? null;
    }
}
