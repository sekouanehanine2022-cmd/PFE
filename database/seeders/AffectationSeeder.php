<?php

namespace Database\Seeders;

use App\Models\Affectation;
use App\Models\Casque;
use App\Models\Clavier;
use App\Models\Ecran;
use App\Models\Materiel;
use App\Models\MiniPc;
use App\Models\PcPortable;
use App\Models\Personnel;
use App\Models\Souris;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AffectationSeeder extends Seeder
{
    public function run(): void
    {
        $materiels = [
            'pc_portable' => $this->materielsDisponibles(PcPortable::class, 'PC-DEMO-%'),
            'mini_pc' => $this->materielsDisponibles(MiniPc::class, 'MINI-DEMO-%'),
            'ecran' => $this->materielsDisponibles(Ecran::class, 'ECRAN-DEMO-%'),
            'clavier' => $this->materielsDisponibles(Clavier::class, 'CLAV-DEMO-%'),
            'souris' => $this->materielsDisponibles(Souris::class, 'SOUR-DEMO-%'),
            'casque' => $this->materielsDisponibles(Casque::class, 'CASQ-DEMO-%'),
        ];

        DB::transaction(function () use (&$materiels): void {
            Personnel::query()
                ->orderBy('id')
                ->get()
                ->each(function (Personnel $personnel, int $index) use (&$materiels): void {
                    $typeOrdinateur = $index % 2 === 0 ? 'pc_portable' : 'mini_pc';
                    $typesAffectes = [$typeOrdinateur, 'ecran', 'clavier', 'souris', 'casque'];
                    $dateDebut = now()->subMonths(($index % 6) + 1)->startOfDay();
                    $dateFin = $this->dateFin($personnel, $index);

                    foreach ($typesAffectes as $typeMateriel) {
                        if ($this->possedeDejaCeType($personnel, $typeMateriel)) {
                            continue;
                        }

                        /** @var Materiel|null $materiel */
                        $materiel = $materiels[$typeMateriel]->shift();

                        if (! $materiel) {
                            continue;
                        }

                        Affectation::create([
                            'personnel_id' => $personnel->id,
                            'ticket_id' => null,
                            'materiel_id' => $materiel->id,
                            'date_debut' => $dateDebut,
                            'date_fin' => $dateFin,
                            'date_retour' => null,
                            'statut' => 'active',
                            'notes' => 'Affectation de demonstration',
                        ]);

                        $materiel->update(['etat' => 'affecte']);
                    }
                });
        });
    }

    private function materielsDisponibles(string $modele, string $prefixeNumeroSerie): Collection
    {
        return $modele::query()
            ->with('materiel')
            ->where('numero_serie', 'like', $prefixeNumeroSerie)
            ->whereHas('materiel', fn ($query) => $query->where('etat', 'disponible'))
            ->orderBy('numero_serie')
            ->get()
            ->pluck('materiel')
            ->filter()
            ->values();
    }

    private function possedeDejaCeType(Personnel $personnel, string $typeMateriel): bool
    {
        return Affectation::query()
            ->where('personnel_id', $personnel->id)
            ->where('statut', 'active')
            ->whereHas('materiel', fn ($query) => $query->where('type_materiel', $typeMateriel))
            ->exists();
    }

    private function dateFin(Personnel $personnel, int $index): ?string
    {
        if ($personnel->type_contrat === 'cdi') {
            return null;
        }

        if ($index % 10 === 0) {
            return now()->subDays(3)->toDateString();
        }

        if ($index % 7 === 0) {
            return now()->addDays(5)->toDateString();
        }

        return now()->addMonths(6)->toDateString();
    }
}
