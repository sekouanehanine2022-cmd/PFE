<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

// ----- Authentification -----
Auth::routes();

// ----- Dashboard (protégé par auth) -----
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');

    // ----- Matériel -----
// Page PC Portables
    Route::resource('pc-portables', App\Http\Controllers\PcPortableController::class);
    Route::resource('mini-pc', App\Http\Controllers\MiniPcController::class);

    // Page PC ecran
    Route::resource('ecrans', App\Http\Controllers\EcranController::class);

    // Page imprimantes
    Route::resource('imprimantes', App\Http\Controllers\ImprimanteController::class);

    // ----- Connectique -----
    Route::resource('cables', App\Http\Controllers\CableController::class);
    Route::post('/cables/{cable}/incrementer', [App\Http\Controllers\CableController::class, 'incrementer'])->name('cables.incrementer');
    Route::post('/cables/{cable}/decrementer', [App\Http\Controllers\CableController::class, 'decrementer'])->name('cables.decrementer');

    // ----- Périphériques -----
    Route::get('/claviers', [App\Http\Controllers\PeripheriqueController::class, 'index'])
    ->defaults('sousType', 'clavier')
    ->name('claviers.index');

    Route::get('/souris', [App\Http\Controllers\PeripheriqueController::class, 'index'])
    ->defaults('sousType', 'souris')
    ->name('souris.index');

    Route::get('/casques', [App\Http\Controllers\PeripheriqueController::class, 'index'])
    ->defaults('sousType', 'casque')
    ->name('casques.index');

    Route::post('/peripheriques', [App\Http\Controllers\PeripheriqueController::class, 'store'])
    ->name('peripheriques.store');

    Route::delete('/peripheriques/{peripherique}', [App\Http\Controllers\PeripheriqueController::class, 'destroy'])
    ->name('peripheriques.destroy');
    // ----- Activité -----
    Route::get('/affectations', [App\Http\Controllers\AffectationController::class, 'index'])->name('affectations.index');

    Route::get('/emprunts', function () {
        return view('activite.emprunts');
    })->name('emprunts.index');

    Route::get('/tickets', function () {
        return view('activite.tickets');
    })->name('tickets.index');

    // ----- Système -----
    Route::get('/notifications', function () {
        return view('systeme.notifications');
    })->name('notifications.index');

    Route::get('/parametres', function () {
        return view('systeme.parametres');
    })->name('parametres.index');

   // Recherche collaborateurs
Route::get('/search/personnels', function(Request $request) {
    $query = trim($request->get('q', ''));
    $personnels = \App\Models\User::where(function ($users) use ($query) {
                    $users->where('name', 'like', $query.'%')
                          ->orWhere('name', 'like', '% '.$query.'%');
                  })
                  ->whereIn('id', \App\Models\Personnel::pluck('user_id'))
                  ->limit(10)
                  ->get(['id', 'name']);
    return response()->json($personnels);
})->name('search.personnels');

// Recherche étudiants
Route::get('/search/etudiants', function(Request $request) {
    $query = trim($request->get('q', ''));
    $etudiants = \App\Models\User::where(function ($users) use ($query) {
                    $users->where('name', 'like', $query.'%')
                          ->orWhere('name', 'like', '% '.$query.'%');
                  })
                  ->whereIn('id', \App\Models\Etudiant::pluck('user_id'))
                  ->limit(10)
                  ->get(['id', 'name']);
    return response()->json($etudiants);
})->name('search.etudiants');

});
