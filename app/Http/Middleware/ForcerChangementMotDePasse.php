<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcerChangementMotDePasse
{
    public function handle(Request $request, Closure $next): Response
    {
        $utilisateur = $request->user();

        // On ne bloque que les utilisateurs connectés qui n'ont pas encore
        // changé leur mot de passe par défaut. On laisse toujours passer
        // la page Paramètres elle-même (et la déconnexion), sinon
        // l'utilisateur serait bloqué sans aucun moyen de s'en sortir.
        if ($utilisateur
            && ! $utilisateur->mot_de_passe_change
            && ! $request->routeIs('mot-de-passe.*')
            && ! $request->routeIs('api.mot-de-passe.*')
            && ! $request->routeIs('logout')) {

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('messages.mot_de_passe_changement_requis'),
                ], Response::HTTP_PRECONDITION_REQUIRED);
            }

            return redirect()->route('mot-de-passe.edit')
                ->with('avertissement', 'Vous devez changer votre mot de passe avant de continuer.');
        }

        return $next($request);
    }
}
