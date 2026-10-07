<?php

namespace Database\Seeders;

use App\Models\Materiel;
use App\Models\Souris;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SourisSeeder extends Seeder
{
    public function run(): void
    {
        $modeles = [
            ['nom' => 'B100', 'marque' => 'Logitech', 'connexion' => 'filaire', 'dpi' => 800, 'nombre_boutons' => 3],
            ['nom' => 'MS116', 'marque' => 'Dell', 'connexion' => 'filaire', 'dpi' => 1000, 'nombre_boutons' => 3],
            ['nom' => 'Z3700', 'marque' => 'HP', 'connexion' => 'sans_fil', 'dpi' => 1200, 'nombre_boutons' => 3],
            ['nom' => 'M185', 'marque' => 'Logitech', 'connexion' => 'sans_fil', 'dpi' => 1000, 'nombre_boutons' => 3],
            ['nom' => 'MX Master 3S', 'marque' => 'Logitech', 'connexion' => 'bluetooth', 'dpi' => 8000, 'nombre_boutons' => 7],
        ];

        DB::transaction(function () use ($modeles): void {
            for ($numero = 1; $numero <= 100; $numero++) {
                $numeroSerie = sprintf('SOUR-DEMO-%03d', $numero);
                $modele = $modeles[($numero - 1) % count($modeles)];

                $donneesMateriel = [
                    'type_materiel' => 'souris',
                    'nom' => $modele['nom'],
                    'marque' => $modele['marque'],
                    'etat' => $numero % 10 === 0 ? 'en_panne' : 'disponible',
                    'emplacement' => $numero % 10 === 0 ? 'Atelier informatique' : 'Stock informatique',
                    'date_achat' => now()->subMonths(($numero % 24) + 1)->toDateString(),
                ];

                $donneesSouris = [
                    'connexion' => $modele['connexion'],
                    'dpi' => $modele['dpi'],
                    'nombre_boutons' => $modele['nombre_boutons'],
                ];

                $souris = Souris::where('numero_serie', $numeroSerie)->first();

                if ($souris) {
                    $souris->materiel()->update($donneesMateriel);
                    $souris->update($donneesSouris);

                    continue;
                }

                $materiel = Materiel::create($donneesMateriel);

                Souris::create([
                    'numero_serie' => $numeroSerie,
                    'materiel_id' => $materiel->id,
                    ...$donneesSouris,
                ]);
            }
        });
    }
}
