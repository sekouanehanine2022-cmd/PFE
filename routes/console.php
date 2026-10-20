<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Verifie chaque matin les retards pour prevenir les administrateurs sans intervention manuelle.
Schedule::command('notifications:verifier-echeances')
    ->dailyAt('08:00')
    ->withoutOverlapping();

// Les rappels anticipes concernent les destinataires du materiel, a J-14, J-7 et J-1.
Schedule::command('relances:envoyer')
    ->timezone('Europe/Paris')
    ->dailyAt('08:00')
    ->withoutOverlapping();
