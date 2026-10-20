<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportCsvService
{
    // Produit un telechargement CSV en UTF-8 avec un separateur adapte a Excel.
    public function telecharger(string $nomFichier, array $entetes, iterable $lignes): StreamedResponse
    {
        $nom = $nomFichier.'-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($entetes, $lignes) {
            $sortie = fopen('php://output', 'wb');

            // Le marqueur UTF-8 permet notamment a Excel de reconnaitre correctement les accents.
            echo "\xEF\xBB\xBF";
            fputcsv($sortie, $entetes, ';', '"', '');

            foreach ($lignes as $ligne) {
                fputcsv($sortie, $ligne, ';', '"', '');
            }

            fclose($sortie);
        }, $nom, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
