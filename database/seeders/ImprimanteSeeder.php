<?php

namespace Database\Seeders;

use App\Models\Imprimante;
use App\Models\Materiel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImprimanteSeeder extends Seeder
{
    public function run(): void
    {
        $imprimantes = [
            [
                'numero_serie' => 'IMPR-DEMO-001',
                'nom' => 'LaserJet Pro M404dn',
                'marque' => 'HP',
                'type_impression' => 'Laser',
                'couleur' => false,
                'connexion' => 'Ethernet',
                'vitesse' => '38 ppm',
                'etat' => 'disponible',
                'emplacement' => 'Administration',
            ],
            [
                'numero_serie' => 'IMPR-DEMO-002',
                'nom' => 'i-SENSYS MF445dw',
                'marque' => 'Canon',
                'type_impression' => 'Laser',
                'couleur' => false,
                'connexion' => 'Wi-Fi',
                'vitesse' => '38 ppm',
                'etat' => 'disponible',
                'emplacement' => 'Accueil',
            ],
            [
                'numero_serie' => 'IMPR-DEMO-003',
                'nom' => 'WorkForce Pro WF-C5890',
                'marque' => 'Epson',
                'type_impression' => "Jet d'encre",
                'couleur' => true,
                'connexion' => 'Wi-Fi',
                'vitesse' => '25 ppm',
                'etat' => 'disponible',
                'emplacement' => 'Service pedagogique',
            ],
            [
                'numero_serie' => 'IMPR-DEMO-004',
                'nom' => 'HL-L3270CDW',
                'marque' => 'Brother',
                'type_impression' => 'Laser',
                'couleur' => true,
                'connexion' => 'Wi-Fi',
                'vitesse' => '24 ppm',
                'etat' => 'en_panne',
                'emplacement' => 'Atelier informatique',
            ],
        ];

        DB::transaction(function () use ($imprimantes): void {
            foreach ($imprimantes as $index => $donnees) {
                $donneesMateriel = [
                    'type_materiel' => 'imprimante',
                    'nom' => $donnees['nom'],
                    'marque' => $donnees['marque'],
                    'etat' => $donnees['etat'],
                    'emplacement' => $donnees['emplacement'],
                    'date_achat' => now()->subMonths(($index + 1) * 4)->toDateString(),
                ];

                $donneesImprimante = [
                    'type_impression' => $donnees['type_impression'],
                    'couleur' => $donnees['couleur'],
                    'connexion' => $donnees['connexion'],
                    'vitesse' => $donnees['vitesse'],
                ];

                $imprimante = Imprimante::where('numero_serie', $donnees['numero_serie'])->first();

                if ($imprimante) {
                    $imprimante->materiel()->update($donneesMateriel);
                    $imprimante->update($donneesImprimante);

                    continue;
                }

                $materiel = Materiel::create($donneesMateriel);

                Imprimante::create([
                    'numero_serie' => $donnees['numero_serie'],
                    'materiel_id' => $materiel->id,
                    ...$donneesImprimante,
                ]);
            }
        });
    }
}
