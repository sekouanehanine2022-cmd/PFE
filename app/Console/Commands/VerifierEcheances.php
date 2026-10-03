<?php

namespace App\Console\Commands;

use App\Models\Affectation;
use App\Models\Emprunt;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class VerifierEcheances extends Command
{
    protected $signature = 'notifications:verifier-echeances';

    protected $description = 'Cree les notifications des affectations et emprunts arrivant a echeance dans 7 jours';

    public function handle(NotificationService $notificationService): int
    {
        $dateEcheance = today()->addDays(7)->toDateString();

        $affectations = Affectation::query()
            ->where('statut', 'active')
            ->whereDate('date_fin', $dateEcheance)
            ->get();

        foreach ($affectations as $affectation) {
            $notificationService->notifierAdminsEcheanceAffectation($affectation);
        }

        $emprunts = Emprunt::query()
            ->where('statut', 'en_cours')
            ->whereDate('date_fin_prevue', $dateEcheance)
            ->get();

        foreach ($emprunts as $emprunt) {
            $notificationService->notifierAdminsEcheanceEmprunt($emprunt);
        }

        $this->info(sprintf(
            '%d affectation(s) et %d emprunt(s) arrivant a echeance dans 7 jours ont ete verifies.',
            $affectations->count(),
            $emprunts->count()
        ));

        return self::SUCCESS;
    }
}
