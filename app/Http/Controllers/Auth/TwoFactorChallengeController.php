<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    private const DUREE_SESSION_SECONDES = 300;

    private const MAX_TENTATIVES = 5;

    public function show(Request $request): View|RedirectResponse
    {
        $utilisateur = $this->utilisateurEnAttente($request);

        if (! $utilisateur) {
            return $this->redirigerVersConnexion($request);
        }

        return view('auth.two-factor-challenge');
    }

    public function verifierCode(
        Request $request,
        TwoFactorAuthService $twoFactorAuthService
    ): RedirectResponse {
        $donnees = $request->validate([
            'code_2fa' => ['required', 'digits:6'],
        ], [
            'code_2fa.required' => __('messages.double_authentification_code_obligatoire'),
            'code_2fa.digits' => __('messages.double_authentification_code_format'),
        ]);

        $utilisateur = $this->utilisateurEnAttente($request);

        if (! $utilisateur) {
            return $this->redirigerVersConnexion($request);
        }

        if ($reponse = $this->verifierLimite($request, $utilisateur)) {
            return $reponse;
        }

        if (! $twoFactorAuthService->verifierCode(
            $utilisateur->two_factor_secret,
            $donnees['code_2fa']
        )) {
            RateLimiter::hit($this->cleLimitation($request, $utilisateur), 60);

            return back()->withErrors([
                'code_2fa' => __('messages.double_authentification_code_invalide'),
            ])->withInput();
        }

        return $this->terminerConnexion($request, $utilisateur);
    }

    public function verifierCodeRecuperation(
        Request $request,
        TwoFactorAuthService $twoFactorAuthService
    ): RedirectResponse {
        $donnees = $request->validate([
            'code_recuperation' => ['required', 'regex:/^[A-Za-z0-9]{5}-[A-Za-z0-9]{5}$/'],
        ], [
            'code_recuperation.required' => __('messages.double_authentification_recuperation_obligatoire'),
            'code_recuperation.regex' => __('messages.double_authentification_recuperation_format'),
        ]);

        $utilisateur = $this->utilisateurEnAttente($request);

        if (! $utilisateur) {
            return $this->redirigerVersConnexion($request);
        }

        if ($reponse = $this->verifierLimite($request, $utilisateur, 'recuperation')) {
            return $reponse;
        }

        if (! $twoFactorAuthService->utiliserCodeRecuperation(
            $utilisateur,
            $donnees['code_recuperation']
        )) {
            RateLimiter::hit($this->cleLimitation($request, $utilisateur), 60);

            return back()
                ->withErrors([
                    'code_recuperation' => __('messages.double_authentification_recuperation_invalide'),
                ])
                ->withInput()
                ->with('two_factor_mode', 'recuperation');
        }

        return $this->terminerConnexion($request, $utilisateur);
    }

    public function annuler(Request $request): RedirectResponse
    {
        $this->effacerSession($request);

        return redirect()
            ->route('login')
            ->with('status', __('messages.double_authentification_connexion_annulee'));
    }

    private function utilisateurEnAttente(Request $request): ?User
    {
        $identifiant = $request->session()->get('auth.two_factor_user_id');
        $demarrage = $request->session()->get('auth.two_factor_started_at');

        if (! $identifiant || ! $demarrage) {
            return null;
        }

        if ((now()->timestamp - (int) $demarrage) > self::DUREE_SESSION_SECONDES) {
            return null;
        }

        $utilisateur = User::find($identifiant);

        if (! $utilisateur
            || $utilisateur->acces_bloque
            || ! $utilisateur->doubleAuthentificationActive()) {
            return null;
        }

        return $utilisateur;
    }

    private function terminerConnexion(Request $request, User $utilisateur): RedirectResponse
    {
        $remember = (bool) $request->session()->get('auth.two_factor_remember', false);

        RateLimiter::clear($this->cleLimitation($request, $utilisateur));
        $this->effacerSession($request);
        Auth::login($utilisateur, $remember);
        $request->session()->regenerate();

        $utilisateur->loadMissing('personnel');

        return redirect()->route(
            $utilisateur->personnel?->role === 'admin'
                ? 'dashboard'
                : 'mon-materiel.index'
        );
    }

    private function verifierLimite(
        Request $request,
        User $utilisateur,
        string $mode = 'application'
    ): ?RedirectResponse {
        $cle = $this->cleLimitation($request, $utilisateur);

        if (! RateLimiter::tooManyAttempts($cle, self::MAX_TENTATIVES)) {
            return null;
        }

        return back()
            ->withErrors([
                $mode === 'recuperation' ? 'code_recuperation' : 'code_2fa' => __(
                    'messages.double_authentification_trop_tentatives',
                    ['seconds' => RateLimiter::availableIn($cle)]
                ),
            ])
            ->with('two_factor_mode', $mode);
    }

    private function cleLimitation(Request $request, User $utilisateur): string
    {
        return 'double-authentification:'.$utilisateur->getKey().':'.$request->session()->getId();
    }

    private function redirigerVersConnexion(Request $request): RedirectResponse
    {
        $this->effacerSession($request);

        return redirect()
            ->route('login')
            ->withErrors([
                'email' => __('messages.double_authentification_session_expiree'),
            ]);
    }

    private function effacerSession(Request $request): void
    {
        $request->session()->forget([
            'auth.two_factor_user_id',
            'auth.two_factor_remember',
            'auth.two_factor_started_at',
        ]);
    }
}
