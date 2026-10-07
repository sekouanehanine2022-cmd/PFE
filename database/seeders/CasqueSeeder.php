<?php

namespace Database\Seeders;

use App\Models\Casque;
use App\Models\Materiel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CasqueSeeder extends Seeder
{
    public function run(): void
    {
        $modeles = [
            ['nom' => 'H390', 'marque' => 'Logitech', 'connexion' => 'filaire', 'micro' => true, 'reduction_bruit' => false],
            ['nom' => 'Evolve2 40', 'marque' => 'Jabra', 'connexion' => 'filaire', 'micro' => true, 'reduction_bruit' => true],
            ['nom' => 'Blackwire 3325', 'marque' => 'Poly', 'connexion' => 'filaire', 'micro' => true, 'reduction_bruit' => false],
            ['nom' => 'WH-1000XM5', 'marque' => 'Sony', 'connexion' => 'bluetooth', 'micro' => true, 'reduction_bruit' => true],
            ['nom' => 'Zone Vibe 100', 'marque' => 'Logitech', 'connexion' => 'bluetooth', 'micro' => true, 'reduction_bruit' => true],
        ];

        DB::transaction(function () use ($modeles): void {
            for ($numero = 1; $numero <= 100; $numero++) {
                $numeroSerie = sprintf('CASQ-DEMO-%03d', $numero);
                $modele = $modeles[($numero - 1) % count($modeles)];

                $donneesMateriel = [
                    'type_materiel' => 'casque',
                    'nom' => $modele['nom'],
                    'marque' => $modele['marque'],
                    'etat' => $numero % 10 === 0 ? 'en_panne' : 'disponible',
                    'emplacement' => $numero % 10 === 0 ? 'Atelier informatique' : 'Stock informatique',
                    'date_achat' => now()->subMonths(($numero % 24) + 1)->toDateString(),
                ];

                $donneesCasque = [
                    'connexion' => $modele['connexion'],
                    'micro' => $modele['micro'],
                    'reduction_bruit' => $modele['reduction_bruit'],
                ];

                $casque = Casque::where('numero_serie', $numeroSerie)->first();

                if ($casque) {
                    $casque->materiel()->update($donneesMateriel);
                    $casque->update($donneesCasque);

                    continue;
                }

                $materiel = Materiel::create($donneesMateriel);

                Casque::create([
                    'numero_serie' => $numeroSerie,
                    'materiel_id' => $materiel->id,
                    ...$donneesCasque,
                ]);
            }
        });
    }
}
