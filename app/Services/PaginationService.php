<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PaginationService
{
    // Repartit les resultats sur plusieurs pages et ouvre la bonne page depuis une notification.
    public function paginerActivite(Builder $query, Request $request, string $parametre): LengthAwarePaginator
    {
        $page = max(1, $request->integer('page', 1));
        $taille = 15;

        // Cherche sur quelle page se trouve l'element indique par la notification.
        if (! $request->has('page') && $request->integer($parametre) > 0) {
            $selection = (clone $query)->whereKey($request->integer($parametre))->first();
            if ($selection) {
                $avant = (clone $query)->where(function ($query) use ($selection) {
                    $query->where('created_at', '>', $selection->getRawOriginal('created_at'))
                        ->orWhere(function ($query) use ($selection) {
                            $query->where('created_at', $selection->getRawOriginal('created_at'))
                                ->where('id', '>', $selection->getKey());
                        });
                })->count();
                $page = intdiv($avant, $taille) + 1;
            }
        }

        return $query->orderByDesc('created_at')->orderByDesc('id')
            ->paginate($taille, ['*'], 'page', $page)->withQueryString();
    }
}
