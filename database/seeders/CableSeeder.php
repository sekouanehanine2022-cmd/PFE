<?php

namespace Database\Seeders;

use App\Models\Cable;
use Illuminate\Database\Seeder;

class CableSeeder extends Seeder
{
    public function run(): void
    {
        $cables = [
            [
                'reference' => 'CB-DEMO-HDMI',
                'type_cable' => 'HDMI',
                'longueur' => '2m',
                'quantite' => 30,
                'quantite_disponible' => 22,
                'seuil_alerte' => 5,
                'couleur' => 'Noir',
                'etat' => 'bon_etat',
                'emplacement' => 'Stock informatique',
            ],
            [
                'reference' => 'CB-DEMO-VGA',
                'type_cable' => 'VGA',
                'longueur' => '1.5m',
                'quantite' => 12,
                'quantite_disponible' => 8,
                'seuil_alerte' => 3,
                'couleur' => 'Bleu',
                'etat' => 'bon_etat',
                'emplacement' => 'Stock informatique',
            ],
            [
                'reference' => 'CB-DEMO-DP',
                'type_cable' => 'DisplayPort',
                'longueur' => '2m',
                'quantite' => 15,
                'quantite_disponible' => 10,
                'seuil_alerte' => 4,
                'couleur' => 'Noir',
                'etat' => 'bon_etat',
                'emplacement' => 'Stock informatique',
            ],
            [
                'reference' => 'CB-DEMO-USBA',
                'type_cable' => 'USB-A',
                'longueur' => '1m',
                'quantite' => 25,
                'quantite_disponible' => 18,
                'seuil_alerte' => 5,
                'couleur' => 'Noir',
                'etat' => 'bon_etat',
                'emplacement' => 'Stock informatique',
            ],
            [
                'reference' => 'CB-DEMO-USBC',
                'type_cable' => 'USB-C',
                'longueur' => '1m',
                'quantite' => 20,
                'quantite_disponible' => 4,
                'seuil_alerte' => 5,
                'couleur' => 'Blanc',
                'etat' => 'bon_etat',
                'emplacement' => 'Stock informatique',
            ],
            [
                'reference' => 'CB-DEMO-RJ45',
                'type_cable' => 'RJ45',
                'longueur' => '5m',
                'quantite' => 50,
                'quantite_disponible' => 0,
                'seuil_alerte' => 10,
                'couleur' => 'Gris',
                'etat' => 'bon_etat',
                'emplacement' => 'Stock informatique',
            ],
        ];

        foreach ($cables as $donnees) {
            Cable::updateOrCreate(
                ['reference' => $donnees['reference']],
                $donnees
            );
        }
    }
}
