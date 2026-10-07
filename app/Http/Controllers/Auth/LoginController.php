<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Redirection après connexion
     */
    /**
     * Nombre de tentatives autorisees avant blocage
     */
    protected $maxAttempts = 3;

    /**
     * Constructeur
     */
    public function __construct()
    {
        // Syntaxe compatible Laravel 12
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    public function showLoginForm()
    {
        return response()
            ->view('auth.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    protected function authenticated(Request $request, $user)
    {
        if ($user->acces_bloque) {
            $this->guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                $this->username() => [__('messages.compte_acces_bloque')],
            ]);
        }

        if ($user->doubleAuthentificationActive()) {
            $remember = $request->boolean('remember');

            $this->guard()->logout();
            $request->session()->put([
                'auth.two_factor_user_id' => $user->getKey(),
                'auth.two_factor_remember' => $remember,
                'auth.two_factor_started_at' => now()->timestamp,
            ]);

            return redirect()->route('double-authentification.challenge');
        }

        $user->loadMissing('personnel');

        if ($user->personnel?->role === 'admin') {
            return redirect()->route('dashboard');
        }

        return redirect()->route('mon-materiel.index');
    }

    protected function sendLockoutResponse(Request $request)
    {
        $seconds = $this->limiter()->availableIn(
            $this->throttleKey($request)
        );

        $request->session()->flash('login_lockout_seconds', $seconds);

        throw ValidationException::withMessages([
            $this->username() => [__('messages.connexion_trop_tentatives', ['seconds' => $seconds])],
        ])->status(Response::HTTP_TOO_MANY_REQUESTS);
    }
}
