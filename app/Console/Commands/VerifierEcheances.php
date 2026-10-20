<?php

namespace App\Console\Commands;

use App\Models\Affectation;
use App\Models\Emprunt;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class VerifierEcheances extends Command
{
    protected $signature = 'notifications:verifier-echeances';

    protected $description = 'Notifie les administrateurs des affectations et emprunts en retard non rendus';

    // Cherche les materiels non rendus dont la date de fin est passee et previent les administrateurs.
    public function handle(NotificationService $notificationService): int
    {
        $aujourdhui = today()->toDateString();

        $affectations = Affectation::query()
            ->where('statut', 'active')
            ->whereNull('date_retour')
            ->whereDate('date_fin', '<', $aujourdhui)
            ->get();

        foreach ($affectations as $affectation) {
            $notificationService->notifierAdminsRetardAffectation($affectation);
        }

        $emprunts = Emprunt::query()
            ->where('statut', '!=', 'rendu')
            ->whereNull('date_retour')
            ->whereHas('pcPortable')
            ->whereDate('date_fin_prevue', '<', $aujourdhui)
            ->get();

        foreach ($emprunts as $emprunt) {
            $notificationService->notifierAdminsRetardEmprunt($emprunt);
        }

        $this->info(sprintf(
            '%d affectation(s) et %d emprunt(s) en retard ont ete verifies.',
            $affectations->count(),
            $emprunts->count()
        ));

        return self::SUCCESS;
    }
}
