<?php

use App\Http\Controllers\Api\AuthentificationController;
use App\Http\Controllers\Api\ChangementMotDePasseController;
use App\Http\Controllers\Api\MesTicketsController;
use App\Http\Controllers\Api\MonMaterielController;
use App\Http\Controllers\Api\MotDePasseOublieController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\VerificationEmailController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthentificationController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('api.login');
Route::post('/login/double-authentification', [AuthentificationController::class, 'verifierDoubleAuthentification'])
    ->middleware('throttle:5,1')
    ->name('api.login.double-authentification');

Route::post('/mot-de-passe/code', [MotDePasseOublieController::class, 'envoyerCode'])
    ->middleware('throttle:3,1')
    ->name('api.mot-de-passe.code');
Route::post('/mot-de-passe/reinitialiser', [MotDePasseOublieController::class, 'reinitialiser'])
    ->middleware('throttle:5,1')
    ->name('api.mot-de-passe.reinitialiser');

Route::get('/email/verify/{id}/{hash}', [VerificationEmailController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.mobile.verify');

Route::middleware(['auth:sanctum', 'compte.actif'])->group(function () {
    Route::get('/me', [AuthentificationController::class, 'me'])
        ->name('api.me');
    Route::post('/logout', [AuthentificationController::class, 'logout'])
        ->name('api.logout');
    Route::patch('/changer-mot-de-passe', [ChangementMotDePasseController::class, 'update'])
        ->middleware('role:personnel,etudiant')
        ->name('api.mot-de-passe.update');

    Route::middleware(['mdp.change', 'role:personnel,etudiant'])->group(function () {
        Route::get('/email/verification-status', [VerificationEmailController::class, 'status'])
            ->name('api.verification.status');
        Route::post('/email/verification-notification', [VerificationEmailController::class, 'resend'])
            ->middleware('throttle:6,1')
            ->name('api.verification.resend');
    });

    Route::middleware(['mdp.change', 'verified'])->group(function () {
        Route::get('/mon-materiel', [MonMaterielController::class, 'index'])
            ->middleware('role:personnel,etudiant')
            ->name('api.mon-materiel');

        Route::get('/mes-tickets', [MesTicketsController::class, 'index'])
            ->middleware('role:personnel,etudiant')
            ->name('api.mes-tickets');
        Route::get('/mes-tickets/options', [MesTicketsController::class, 'options'])
            ->middleware('role:personnel,etudiant')
            ->name('api.mes-tickets.options');
        Route::post('/mes-tickets', [MesTicketsController::class, 'store'])
            ->middleware('role:personnel,etudiant')
            ->name('api.mes-tickets.store');

        Route::get('/notifications', [NotificationController::class, 'index'])
            ->middleware('role:personnel,etudiant')
            ->name('api.notifications');
        Route::patch('/notifications/tout-lire', [NotificationController::class, 'toutLire'])
            ->middleware('role:personnel,etudiant')
            ->name('api.notifications.tout-lire');
        Route::patch('/notifications/{notification}/lire', [NotificationController::class, 'lire'])
            ->middleware('role:personnel,etudiant')
            ->name('api.notifications.lire');
    });
});
