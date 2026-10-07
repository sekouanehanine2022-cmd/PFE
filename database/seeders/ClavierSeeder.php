<?php

namespace Database\Seeders;

use App\Models\Clavier;
use App\Models\Materiel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClavierSeeder extends Seeder
{
    public function run(): void
    {
        $modeles = [
            ['nom' => 'K120', 'marque' => 'Logitech', 'connexion' => 'filaire', 'retro_eclairage' => false],
            ['nom' => 'KB216', 'marque' => 'Dell', 'connexion' => 'filaire', 'retro_eclairage' => false],
            ['nom' => '230 Wireless', 'marque' => 'HP', 'connexion' => 'sans_fil', 'retro_eclairage' => false],
            ['nom' => 'MX Keys', 'marque' => 'Logitech', 'connexion' => 'bluetooth', 'retro_eclairage' => true],
            ['nom' => 'Essential Wired', 'marque' => 'Lenovo', 'connexion' => 'filaire', 'retro_eclairage' => false],
        ];

        DB::transaction(function () use ($modeles): void {
            for ($numero = 1; $numero <= 100; $numero++) {
                $numeroSerie = sprintf('CLAV-DEMO-%03d', $numero);
                $modele = $modeles[($numero - 1) % count($modeles)];

                $donneesMateriel = [
                    'type_materiel' => 'clavier',
                    'nom' => $modele['nom'],
                    'marque' => $modele['marque'],
                    'etat' => $numero % 10 === 0 ? 'en_panne' : 'disponible',
                    'emplacement' => $numero % 10 === 0 ? 'Atelier informatique' : 'Stock informatique',
                    'date_achat' => now()->subMonths(($numero % 24) + 1)->toDateString(),
                ];

                $donneesClavier = [
                    'connexion' => $modele['connexion'],
                    'disposition' => 'AZERTY',
                    'retro_eclairage' => $modele['retro_eclairage'],
                ];

                $clavier = Clavier::where('numero_serie', $numeroSerie)->first();

                if ($clavier) {
                    $clavier->materiel()->update($donneesMateriel);
                    $clavier->update($donneesClavier);

                    continue;
                }

                $materiel = Materiel::create($donneesMateriel);

                Clavier::create([
                    'numero_serie' => $numeroSerie,
                    'materiel_id' => $materiel->id,
                    ...$donneesClavier,
                ]);
            }
        });
    }
}
