<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Ces alias permettent de proteger les routes avant l execution des controleurs.
        $middleware->alias([
            'mdp.change' => \App\Http\Middleware\ForcerChangementMotDePasse::class,
            'role' => \App\Http\Middleware\VerifierRole::class,
            'compte.actif' => \App\Http\Middleware\VerifierCompteActif::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Adapte une expiration de jeton CSRF au format attendu : JSON ou retour a la connexion.
        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Votre session a expiré. Veuillez vous reconnecter.',
                ], 419);
            }

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Votre session a été renouvelée. Veuillez vous reconnecter.']);
        });
    })->create();
