<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerifierCompteActif
{
    public function handle(Request $request, Closure $next): Response
    {
        $utilisateur = $request->user();

        if (! $utilisateur?->acces_bloque) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            $utilisateur->currentAccessToken()?->delete();

            return response()->json([
                'message' => __('messages.compte_acces_bloque'),
            ], Response::HTTP_FORBIDDEN);
        }

        Auth::guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => __('messages.compte_acces_bloque'),
        ]);
    }
}
