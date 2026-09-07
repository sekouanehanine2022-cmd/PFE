<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ----- Authentification -----
Auth::routes();

Route::middleware(['auth', 'mdp.change'])->group(function () {

    // ----- Routes communes -----
    Route::get('/', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/changer-mot-de-passe', [App\Http\Controllers\ChangementMotDePasseController::class, 'edit'])->name('mot-de-passe.edit');
    Route::patch('/changer-mot-de-passe', [App\Http\Controllers\ChangementMotDePasseController::class, 'update'])->name('mot-de-passe.update');

    Route::get('/notifications', function () {
        return view('systeme.notifications');
    })->name('notifications.index');

    Route::get('/mon-materiel', [App\Http\Controllers\MonMaterielController::class, 'index'])->name('mon-materiel.index');

    Route::get('/parametres', [App\Http\Controllers\ParametresController::class, 'index'])->name('parametres.index');
    Route::patch('/parametres/mot-de-passe', [App\Http\Controllers\ParametresController::class, 'updateMotDePasse'])->name('parametres.mot-de-passe');

    // ----- Routes admin / technicien -----
    Route::middleware('role:admin')->group(function () {

        // Materiel
        Route::resource('pc-portables', App\Http\Controllers\PcPortableController::class);
        Route::patch('/pc-portables/{pcPortable}/panne', [App\Http\Controllers\PcPortableController::class, 'signalerPanne'])->name('pc-portables.panne');
        Route::patch('/pc-portables/{pcPortable}/reparer', [App\Http\Controllers\PcPortableController::class, 'marquerRepare'])->name('pc-portables.reparer');

        Route::resource('mini-pc', App\Http\Controllers\MiniPcController::class);
        Route::patch('/mini-pc/{miniPc}/panne', [App\Http\Controllers\MiniPcController::class, 'signalerPanne'])->name('mini-pc.panne');
        Route::patch('/mini-pc/{miniPc}/reparer', [App\Http\Controllers\MiniPcController::class, 'marquerRepare'])->name('mini-pc.reparer');

        Route::resource('ecrans', App\Http\Controllers\EcranController::class);
        Route::patch('/ecrans/{ecran}/panne', [App\Http\Controllers\EcranController::class, 'signalerPanne'])->name('ecrans.panne');
        Route::patch('/ecrans/{ecran}/reparer', [App\Http\Controllers\EcranController::class, 'marquerRepare'])->name('ecrans.reparer');

        Route::resource('imprimantes', App\Http\Controllers\ImprimanteController::class);
        Route::patch('/imprimantes/{imprimante}/panne', [App\Http\Controllers\ImprimanteController::class, 'signalerPanne'])->name('imprimantes.panne');
        Route::patch('/imprimantes/{imprimante}/reparer', [App\Http\Controllers\ImprimanteController::class, 'marquerRepare'])->name('imprimantes.reparer');

        // Connectique
        Route::resource('cables', App\Http\Controllers\CableController::class);
        Route::post('/cables/{cable}/incrementer', [App\Http\Controllers\CableController::class, 'incrementer'])->name('cables.incrementer');
        Route::post('/cables/{cable}/decrementer', [App\Http\Controllers\CableController::class, 'decrementer'])->name('cables.decrementer');
        Route::post('/cables/{cable}/ajouter-stock', [App\Http\Controllers\CableController::class, 'ajouterStock'])->name('cables.ajouter-stock');
        Route::post('/cables/{cable}/retirer-stock', [App\Http\Controllers\CableController::class, 'retirerStock'])->name('cables.retirer-stock');

        // Peripheriques
        Route::get('/claviers', [App\Http\Controllers\PeripheriqueController::class, 'index'])->defaults('sousType', 'clavier')->name('claviers.index');
        Route::get('/souris', [App\Http\Controllers\PeripheriqueController::class, 'index'])->defaults('sousType', 'souris')->name('souris.index');
        Route::get('/casques', [App\Http\Controllers\PeripheriqueController::class, 'index'])->defaults('sousType', 'casque')->name('casques.index');

        Route::post('/peripheriques', [App\Http\Controllers\PeripheriqueController::class, 'store'])->name('peripheriques.store');
        Route::match(['put', 'patch'], '/peripheriques/{peripherique}', [App\Http\Controllers\PeripheriqueController::class, 'update'])->name('peripheriques.update');
        Route::delete('/peripheriques/{peripherique}', [App\Http\Controllers\PeripheriqueController::class, 'destroy'])->name('peripheriques.destroy');
        Route::patch('/peripheriques/{peripherique}/panne', [App\Http\Controllers\PeripheriqueController::class, 'signalerPanne'])->name('peripheriques.panne');
        Route::patch('/peripheriques/{peripherique}/reparer', [App\Http\Controllers\PeripheriqueController::class, 'marquerRepare'])->name('peripheriques.reparer');

        // Activite
        Route::get('/affectations', [App\Http\Controllers\AffectationController::class, 'index'])->name('affectations.index');
        Route::post('/affectations', [App\Http\Controllers\AffectationController::class, 'store'])->name('affectations.store');
        Route::patch('/affectations/{affectation}', [App\Http\Controllers\AffectationController::class, 'update'])->name('affectations.update');
        Route::patch('/affectations/{affectation}/cloturer', [App\Http\Controllers\AffectationController::class, 'cloturer'])->name('affectations.cloturer');

        Route::get('/emprunts', [App\Http\Controllers\EmpruntController::class, 'index'])->name('emprunts.index');
        Route::post('/emprunts', [App\Http\Controllers\EmpruntController::class, 'store'])->name('emprunts.store');
        Route::get('/materiel-disponible/{type}', [App\Http\Controllers\EmpruntController::class, 'materielDisponible'])->name('materiel.disponible');

        Route::get('/tickets', [App\Http\Controllers\TicketController::class, 'index'])->name('tickets.index');
        Route::post('/tickets', [App\Http\Controllers\TicketController::class, 'store'])->name('tickets.store');

        // Recherche
        Route::get('/search/personnels', function (Request $request) {
            $query = trim($request->get('q', ''));

            $personnels = \App\Models\User::join('personnels', 'users.id', '=', 'personnels.user_id')
                ->where(function ($users) use ($query) {
                    $users->where('name', 'like', $query . '%')
                        ->orWhere('name', 'like', '% ' . $query . '%');
                })
                ->limit(10)
                ->get([
                    'users.id',
                    'users.name',
                    'users.email',
                    'personnels.id as personnel_id',
                ]);

            return response()->json($personnels);
        })->name('search.personnels');

        Route::get('/search/etudiants', function (Request $request) {
            $query = trim($request->get('q', ''));

            $etudiants = \App\Models\User::join('etudiants', 'users.id', '=', 'etudiants.user_id')
                ->where(function ($users) use ($query) {
                    $users->where('name', 'like', $query . '%')
                        ->orWhere('name', 'like', '% ' . $query . '%');
                })
                ->limit(10)
                ->get([
                    'users.id',
                    'users.name',
                    'users.email',
                    'etudiants.id as etudiant_id',
                ]);

            return response()->json($etudiants);
        })->name('search.etudiants');
    });
});
