<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\Ecran;
use App\Models\MiniPc;
use App\Models\PcPortable;
use App\Models\Peripherique;

class AffectationService
{
    public function existeAffectationActivePourType(
        int $personnelId,
        string $materielType,
        ?string $sousType = null,
        ?int $affectationIgnoreeId = null
    ): bool {
        return Affectation::query()
            ->where('personnel_id', $personnelId)
            ->where('statut', 'active')
            ->where('materiel_type', $materielType)
            ->when($affectationIgnoreeId, function ($query) use ($affectationIgnoreeId) {
                $query->where('id', '!=', $affectationIgnoreeId);
            })
            ->when($materielType === Peripherique::class && $sousType, function ($query) use ($sousType) {
                $query->whereHasMorph('materiel', [Peripherique::class], function ($materielQuery) use ($sousType) {
                    $materielQuery->where('sous_type', $sousType);
                });
            })
            ->exists();
    }

    public function typeDepuisSlug(string $slug): ?string
    {
        return [
            'pc-portable' => PcPortable::class,
            'mini-pc' => MiniPc::class,
            'ecran' => Ecran::class,
            'clavier' => Peripherique::class,
            'souris' => Peripherique::class,
            'casque' => Peripherique::class,
        ][$slug] ?? null;
    }

    public function sousTypeDepuisSlug(string $slug): ?string
    {
        return in_array($slug, ['clavier', 'souris', 'casque'], true) ? $slug : null;
    }

    public function libelleType(string $materielType, ?string $sousType = null): string
    {
        if ($materielType === Peripherique::class && $sousType) {
            return [
                'clavier' => 'clavier',
                'souris' => 'souris',
                'casque' => 'casque',
            ][$sousType] ?? 'peripherique';
        }

        return [
            PcPortable::class => 'PC portable',
            MiniPc::class => 'Mini PC',
            Ecran::class => 'ecran',
        ][$materielType] ?? 'materiel';
    }
}
