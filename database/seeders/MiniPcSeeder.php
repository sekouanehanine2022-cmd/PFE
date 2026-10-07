<?php

namespace Database\Seeders;

use App\Models\Materiel;
use App\Models\MiniPc;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MiniPcSeeder extends Seeder
{
    public function run(): void
    {
        $modeles = [
            ['nom' => 'OptiPlex 7010 Micro', 'marque' => 'Dell', 'cpu' => 'Intel Core i5-13500T'],
            ['nom' => 'ThinkCentre M70q Gen 3', 'marque' => 'Lenovo', 'cpu' => 'Intel Core i5-12500T'],
            ['nom' => 'Pro Mini 400 G9', 'marque' => 'HP', 'cpu' => 'Intel Core i5-13500T'],
            ['nom' => 'NUC 12 Pro', 'marque' => 'Intel', 'cpu' => 'Intel Core i5-1240P'],
            ['nom' => 'ExpertCenter PN64', 'marque' => 'Asus', 'cpu' => 'Intel Core i5-12500H'],
        ];

        $rams = ['8 Go DDR4', '16 Go DDR4', '16 Go DDR5', '32 Go DDR5'];
        $stockages = ['256 Go SSD', '512 Go SSD', '1 To SSD'];
        $systemes = ['Windows 11', 'Windows 11', 'Windows 11', 'Windows 10'];

        DB::transaction(function () use ($modeles, $rams, $stockages, $systemes): void {
            for ($numero = 1; $numero <= 100; $numero++) {
                $numeroSerie = sprintf('MINI-DEMO-%03d', $numero);
                $modele = $modeles[($numero - 1) % count($modeles)];

                $donneesMateriel = [
                    'type_materiel' => 'mini_pc',
                    'nom' => $modele['nom'],
                    'marque' => $modele['marque'],
                    'etat' => $numero % 10 === 0 ? 'en_panne' : 'disponible',
                    'emplacement' => $numero % 10 === 0 ? 'Atelier informatique' : 'Stock informatique',
                    'date_achat' => now()->subMonths(($numero % 24) + 1)->toDateString(),
                ];

                $donneesMiniPc = [
                    'adresse_mac' => sprintf('06:00:00:00:%02X:%02X', intdiv($numero, 256), $numero % 256),
                    'cpu' => $modele['cpu'],
                    'ram' => $rams[($numero - 1) % count($rams)],
                    'stockage' => $stockages[($numero - 1) % count($stockages)],
                    'os' => $systemes[($numero - 1) % count($systemes)],
                ];

                $miniPc = MiniPc::where('numero_serie', $numeroSerie)->first();

                if ($miniPc) {
                    $miniPc->materiel()->update($donneesMateriel);
                    $miniPc->update($donneesMiniPc);

                    continue;
                }

                $materiel = Materiel::create($donneesMateriel);

                MiniPc::create([
                    'numero_serie' => $numeroSerie,
                    'materiel_id' => $materiel->id,
                    ...$donneesMiniPc,
                ]);
            }
        });
    }
}
