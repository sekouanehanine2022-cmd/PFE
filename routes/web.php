<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ----- Authentification -----
Auth::routes(['verify' => true]);

Route::middleware(['auth', 'mdp.change'])->group(function () {

    // ----- Routes communes -----
    Route::get('/', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/changer-mot-de-passe', [App\Http\Controllers\ChangementMotDePasseController::class, 'edit'])->name('mot-de-passe.edit');
    Route::patch('/changer-mot-de-passe', [App\Http\Controllers\ChangementMotDePasseController::class, 'update'])->name('mot-de-passe.update');

    Route::get('/mon-materiel', [App\Http\Controllers\MonMaterielController::class, 'index'])->name('mon-materiel.index');

    Route::get('/tickets', [App\Http\Controllers\TicketController::class, 'index'])->name('tickets.index');
    Route::middleware('role:personnel,etudiant')->group(function () {
        Route::post('/tickets', [App\Http\Controllers\TicketController::class, 'store'])->name('tickets.store');
    });

    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/tout-lire', [App\Http\Controllers\NotificationController::class, 'toutLire'])->name('notifications.tout-lire');
    Route::patch('/notifications/{notification}/lire', [App\Http\Controllers\NotificationController::class, 'lire'])->name('notifications.lire');

    Route::get('/parametres', [App\Http\Controllers\ParametresController::class, 'index'])->name('parametres.index');
    Route::patch('/parametres/mot-de-passe', [App\Http\Controllers\ParametresController::class, 'updateMotDePasse'])->name('parametres.mot-de-passe');

    // ----- Routes admin / technicien -----
    Route::middleware('role:admin')->group(function () {

        // Materiel
        Route::resource('pc-portables', App\Http\Controllers\PcPortableController::class)
            ->except(['create', 'show', 'edit']);
        Route::patch('/pc-portables/{pcPortable}/panne', [App\Http\Controllers\PcPortableController::class, 'signalerPanne'])->name('pc-portables.panne');
        Route::patch('/pc-portables/{pcPortable}/reparer', [App\Http\Controllers\PcPortableController::class, 'marquerRepare'])->name('pc-portables.reparer');

        Route::resource('mini-pc', App\Http\Controllers\MiniPcController::class)
            ->except(['create', 'show', 'edit']);
        Route::patch('/mini-pc/{miniPc}/panne', [App\Http\Controllers\MiniPcController::class, 'signalerPanne'])->name('mini-pc.panne');
        Route::patch('/mini-pc/{miniPc}/reparer', [App\Http\Controllers\MiniPcController::class, 'marquerRepare'])->name('mini-pc.reparer');

        Route::resource('ecrans', App\Http\Controllers\EcranController::class)
            ->except(['create', 'show', 'edit']);
        Route::patch('/ecrans/{ecran}/panne', [App\Http\Controllers\EcranController::class, 'signalerPanne'])->name('ecrans.panne');
        Route::patch('/ecrans/{ecran}/reparer', [App\Http\Controllers\EcranController::class, 'marquerRepare'])->name('ecrans.reparer');

        Route::resource('imprimantes', App\Http\Controllers\ImprimanteController::class)
            ->except(['create', 'show', 'edit']);
        Route::patch('/imprimantes/{imprimante}/panne', [App\Http\Controllers\ImprimanteController::class, 'signalerPanne'])->name('imprimantes.panne');
        Route::patch('/imprimantes/{imprimante}/reparer', [App\Http\Controllers\ImprimanteController::class, 'marquerRepare'])->name('imprimantes.reparer');

        // Connectique
        Route::resource('cables', App\Http\Controllers\CableController::class);
        Route::post('/cables/{cable}/incrementer', [App\Http\Controllers\CableController::class, 'incrementer'])->name('cables.incrementer');
        Route::post('/cables/{cable}/decrementer', [App\Http\Controllers\CableController::class, 'decrementer'])->name('cables.decrementer');
        Route::post('/cables/{cable}/ajouter-stock', [App\Http\Controllers\CableController::class, 'ajouterStock'])->name('cables.ajouter-stock');
        Route::post('/cables/{cable}/retirer-stock', [App\Http\Controllers\CableController::class, 'retirerStock'])->name('cables.retirer-stock');

        // Peripheriques
        Route::get('/claviers', [App\Http\Controllers\ClavierController::class, 'index'])->name('claviers.index');
        Route::post('/claviers', [App\Http\Controllers\ClavierController::class, 'store'])->name('claviers.store');
        Route::match(['put', 'patch'], '/claviers/{clavier}', [App\Http\Controllers\ClavierController::class, 'update'])->name('claviers.update');
        Route::delete('/claviers/{clavier}', [App\Http\Controllers\ClavierController::class, 'destroy'])->name('claviers.destroy');
        Route::patch('/claviers/{clavier}/panne', [App\Http\Controllers\ClavierController::class, 'signalerPanne'])->name('claviers.panne');
        Route::patch('/claviers/{clavier}/reparer', [App\Http\Controllers\ClavierController::class, 'marquerRepare'])->name('claviers.reparer');

        Route::get('/souris', [App\Http\Controllers\SourisController::class, 'index'])->name('souris.index');
        Route::post('/souris', [App\Http\Controllers\SourisController::class, 'store'])->name('souris.store');
        Route::match(['put', 'patch'], '/souris/{souris}', [App\Http\Controllers\SourisController::class, 'update'])->name('souris.update');
        Route::delete('/souris/{souris}', [App\Http\Controllers\SourisController::class, 'destroy'])->name('souris.destroy');
        Route::patch('/souris/{souris}/panne', [App\Http\Controllers\SourisController::class, 'signalerPanne'])->name('souris.panne');
        Route::patch('/souris/{souris}/reparer', [App\Http\Controllers\SourisController::class, 'marquerRepare'])->name('souris.reparer');

        Route::get('/casques', [App\Http\Controllers\CasqueController::class, 'index'])->name('casques.index');
        Route::post('/casques', [App\Http\Controllers\CasqueController::class, 'store'])->name('casques.store');
        Route::match(['put', 'patch'], '/casques/{casque}', [App\Http\Controllers\CasqueController::class, 'update'])->name('casques.update');
        Route::delete('/casques/{casque}', [App\Http\Controllers\CasqueController::class, 'destroy'])->name('casques.destroy');
        Route::patch('/casques/{casque}/panne', [App\Http\Controllers\CasqueController::class, 'signalerPanne'])->name('casques.panne');
        Route::patch('/casques/{casque}/reparer', [App\Http\Controllers\CasqueController::class, 'marquerRepare'])->name('casques.reparer');

        // Activite
        Route::get('/affectations', [App\Http\Controllers\AffectationController::class, 'index'])->name('affectations.index');
        Route::post('/affectations', [App\Http\Controllers\AffectationController::class, 'store'])->name('affectations.store');
        Route::post('/affectations/{affectation}/relancer', [App\Http\Controllers\AffectationController::class, 'relancer'])->name('affectations.relancer');
        Route::patch('/affectations/{affectation}/retour', [App\Http\Controllers\AffectationController::class, 'validerRetour'])->name('affectations.retour');
        Route::patch('/affectations/{affectation}/prolonger', [App\Http\Controllers\AffectationController::class, 'prolonger'])->name('affectations.prolonger');
        Route::delete('/affectations/{affectation}', [App\Http\Controllers\AffectationController::class, 'destroy'])->name('affectations.destroy');

        Route::get('/emprunts', [App\Http\Controllers\EmpruntController::class, 'index'])->name('emprunts.index');
        Route::post('/emprunts', [App\Http\Controllers\EmpruntController::class, 'store'])->name('emprunts.store');
        Route::post('/emprunts/{emprunt}/relancer', [App\Http\Controllers\EmpruntController::class, 'relancer'])->name('emprunts.relancer');
        Route::patch('/emprunts/{emprunt}/retour', [App\Http\Controllers\EmpruntController::class, 'validerRetour'])->name('emprunts.retour');
        Route::patch('/emprunts/{emprunt}/prolonger', [App\Http\Controllers\EmpruntController::class, 'prolonger'])->name('emprunts.prolonger');
        Route::delete('/emprunts/{emprunt}', [App\Http\Controllers\EmpruntController::class, 'destroy'])->name('emprunts.destroy');
        Route::get('/materiel-disponible/{type}', [App\Http\Controllers\EmpruntController::class, 'materielDisponible'])->name('materiel.disponible');

        Route::patch('/tickets/{ticket}/assigner', [App\Http\Controllers\TicketController::class, 'assigner'])->name('tickets.assigner');
        Route::patch('/tickets/{ticket}/resoudre', [App\Http\Controllers\TicketController::class, 'resoudre'])->name('tickets.resoudre');
        Route::patch('/tickets/{ticket}/refuser', [App\Http\Controllers\TicketController::class, 'refuser'])->name('tickets.refuser');
        Route::patch('/tickets/{ticket}/reponse', [App\Http\Controllers\TicketController::class, 'repondre'])->name('tickets.repondre');
        Route::post('/tickets/{ticket}/affectation', [App\Http\Controllers\AffectationController::class, 'storeDepuisTicket'])->name('tickets.affectation.store');
        Route::post('/tickets/{ticket}/emprunt', [App\Http\Controllers\EmpruntController::class, 'storeDepuisTicket'])->name('tickets.emprunt.store');

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
                    'personnels.type_contrat',
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
