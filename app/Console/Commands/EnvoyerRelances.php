<?php

namespace App\Console\Commands;

use App\Services\RelanceService;
use Illuminate\Console\Command;

class EnvoyerRelances extends Command
{
    protected $signature = 'relances:envoyer';
    protected $description = 'Cree les rappels et envoie les emails de retour a J-14, J-7 et J-1';

    // Appelle le service de relance et affiche le bilan dans le terminal.
    public function handle(RelanceService $service): int
    {
        $resultat = $service->envoyer();
        $this->info(sprintf('%d notification(s) creee(s), %d email(s) envoye(s), %d erreur(s).', ...array_values($resultat)));

        return $resultat['erreurs'] ? self::FAILURE : self::SUCCESS;
    }
}
