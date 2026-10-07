<?php

namespace Database\Seeders;

use App\Models\Ecran;
use App\Models\Materiel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EcranSeeder extends Seeder
{
    public function run(): void
    {
        $modeles = [
            ['nom' => 'P2422H', 'marque' => 'Dell', 'taille' => '24', 'resolution' => '1920x1080', 'dalle' => 'IPS', 'taux' => '60'],
            ['nom' => 'ThinkVision T24i-30', 'marque' => 'Lenovo', 'taille' => '24', 'resolution' => '1920x1080', 'dalle' => 'IPS', 'taux' => '60'],
            ['nom' => 'E24 G5', 'marque' => 'HP', 'taille' => '24', 'resolution' => '1920x1080', 'dalle' => 'IPS', 'taux' => '75'],
            ['nom' => '27UP650', 'marque' => 'LG', 'taille' => '27', 'resolution' => '3840x2160', 'dalle' => 'IPS', 'taux' => '60'],
            ['nom' => 'S27A600', 'marque' => 'Samsung', 'taille' => '27', 'resolution' => '2560x1440', 'dalle' => 'IPS', 'taux' => '75'],
        ];

        DB::transaction(function () use ($modeles): void {
            for ($numero = 1; $numero <= 100; $numero++) {
                $numeroSerie = sprintf('ECRAN-DEMO-%03d', $numero);
                $modele = $modeles[($numero - 1) % count($modeles)];

                $donneesMateriel = [
                    'type_materiel' => 'ecran',
                    'nom' => $modele['nom'],
                    'marque' => $modele['marque'],
                    'etat' => $numero % 10 === 0 ? 'en_panne' : 'disponible',
                    'emplacement' => $numero % 10 === 0 ? 'Atelier informatique' : 'Stock informatique',
                    'date_achat' => now()->subMonths(($numero % 24) + 1)->toDateString(),
                ];

                $donneesEcran = [
                    'taille' => $modele['taille'],
                    'resolution' => $modele['resolution'],
                    'dalle' => $modele['dalle'],
                    'taux_rafraichissement' => $modele['taux'],
                ];

                $ecran = Ecran::where('numero_serie', $numeroSerie)->first();

                if ($ecran) {
                    $ecran->materiel()->update($donneesMateriel);
                    $ecran->update($donneesEcran);

                    continue;
                }

                $materiel = Materiel::create($donneesMateriel);

                Ecran::create([
                    'numero_serie' => $numeroSerie,
                    'materiel_id' => $materiel->id,
                    ...$donneesEcran,
                ]);
            }
        });
    }
}
