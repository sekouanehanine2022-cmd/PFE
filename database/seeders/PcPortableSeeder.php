<?php

namespace Database\Seeders;

use App\Models\Materiel;
use App\Models\PcPortable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PcPortableSeeder extends Seeder
{
    public function run(): void
    {
        $modeles = [
            ['nom' => 'Latitude 5440', 'marque' => 'Dell', 'cpu' => 'Intel Core i5-1335U', 'ecran' => '14'],
            ['nom' => 'ThinkPad L15', 'marque' => 'Lenovo', 'cpu' => 'Intel Core i5-1235U', 'ecran' => '15.6'],
            ['nom' => 'EliteBook 840 G9', 'marque' => 'HP', 'cpu' => 'Intel Core i5-1245U', 'ecran' => '14'],
            ['nom' => 'ProBook 450 G10', 'marque' => 'HP', 'cpu' => 'Intel Core i5-1335U', 'ecran' => '15.6'],
            ['nom' => 'Vostro 3520', 'marque' => 'Dell', 'cpu' => 'Intel Core i5-1235U', 'ecran' => '15.6'],
        ];

        $rams = ['8 Go DDR4', '16 Go DDR4', '16 Go DDR5', '32 Go DDR5'];
        $stockages = ['256 Go SSD', '512 Go SSD', '1 To SSD'];
        $systemes = ['Windows 11', 'Windows 11', 'Windows 11', 'Windows 10'];

        DB::transaction(function () use ($modeles, $rams, $stockages, $systemes): void {
            for ($numero = 1; $numero <= 100; $numero++) {
                $numeroSerie = sprintf('PC-DEMO-%03d', $numero);
                $modele = $modeles[($numero - 1) % count($modeles)];

                $donneesMateriel = [
                    'type_materiel' => 'pc_portable',
                    'nom' => $modele['nom'],
                    'marque' => $modele['marque'],
                    'etat' => $numero % 10 === 0 ? 'en_panne' : 'disponible',
                    'emplacement' => $numero % 10 === 0 ? 'Atelier informatique' : 'Stock informatique',
                    'date_achat' => now()->subMonths(($numero % 24) + 1)->toDateString(),
                ];

                $donneesPc = [
                    'adresse_mac' => sprintf('02:00:00:00:%02X:%02X', intdiv($numero, 256), $numero % 256),
                    'cpu' => $modele['cpu'],
                    'ram' => $rams[($numero - 1) % count($rams)],
                    'stockage' => $stockages[($numero - 1) % count($stockages)],
                    'os' => $systemes[($numero - 1) % count($systemes)],
                    'ecran' => $modele['ecran'],
                ];

                $pcPortable = PcPortable::where('numero_serie', $numeroSerie)->first();

                if ($pcPortable) {
                    $pcPortable->materiel()->update($donneesMateriel);
                    $pcPortable->update($donneesPc);

                    continue;
                }

                $materiel = Materiel::create($donneesMateriel);

                PcPortable::create([
                    'numero_serie' => $numeroSerie,
                    'materiel_id' => $materiel->id,
                    ...$donneesPc,
                ]);
            }
        });
    }
}
