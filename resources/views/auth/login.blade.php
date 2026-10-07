{{-- resources/views/auth/login.blade.php --}}

@extends('layouts.auth')

@section('title', 'Connexion - EFEL')

@section('content')

<div class="login-wrapper">

    {{-- CÔTÉ GAUCHE --}}
    <div class="login-left">

        <h1 class="login-titre">
            IEG <br>
            <span class="login-titre-bleu">Parc Info</span>
        </h1>

        <p class="login-desc">
            Gérez votre parc informatique avec simplicité et efficacité.
            Une solution moderne pour votre établissement.
        </p>

        <div class="login-features">
            <div class="login-feature">
                <div class="login-feature-icon">
                    <i class="bi bi-pc-display"></i>
                </div>
                Gestion complète du matériel
            </div>
            <div class="login-feature">
                <div class="login-feature-icon">
                    <i class="bi bi-activity"></i>
                </div>
                Suivi en temps réel
            </div>
            <div class="login-feature">
                <div class="login-feature-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                Sécurisé et fiable
            </div>
        </div>

    </div>

    {{-- CÔTÉ DROIT --}}
    <div class="login-right">
        <div class="login-card">

            {{-- Logo --}}
            <div class="login-logo">
                <i class="bi bi-pc-display"></i>
            </div>

            <h2 class="login-welcome">Bienvenue</h2>
            <p class="login-subtitle">Connectez-vous pour accéder à votre espace</p>

            {{-- Erreurs --}}
            @if (session('status'))
                <div class="login-success" role="alert">
                    <i class="bi bi-check-circle"></i>{{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="login-error" id="login-error-message">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- Email --}}
                <div class="login-field">
                    <label class="login-label">ADRESSE E-MAIL</label>
                    <div class="login-input-wrap">
                        <i class="bi bi-envelope login-icon"></i>
                        <input type="email"
                               name="email"
                               class="login-input"
                               placeholder="votre.nom@efel.fr"
                               value="{{ old('email') }}"
                               required
                               autofocus>
                    </div>
                </div>

                {{-- Mot de passe --}}
                <div class="login-field">
                    <label class="login-label">MOT DE PASSE</label>
                    <div class="login-input-wrap">
                        <i class="bi bi-lock login-icon"></i>
                        <input type="password"
                               name="password"
                               id="password"
                               class="login-input"
                               placeholder="••••••••"
                               required>
                        <i class="bi bi-eye login-icon-right" onclick="togglePassword()"></i>
                    </div>
                </div>

                {{-- Options --}}
                <div class="login-options">
                    <div class="d-flex align-items-center gap-2">
                        <input type="checkbox" name="remember" class="form-check-input" id="remember">
                        <label class="login-remember" for="remember">Se souvenir de moi</label>
                    </div>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="login-forgot">
                            Mot de passe oublié ?
                        </a>
                    @endif
                </div>

                {{-- Bouton --}}
                <button type="submit" class="btn-login" id="btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> Se connecter
                </button>

            </form>

            {{-- Footer --}}
            <div class="login-footer">
                © 2026 IEG School ·
                <a href="#">Confidentialité</a> ·
                <a href="#">Mentions légales</a>
            </div>

        </div>
    </div>

</div>

@endsection

@section('scripts')
    @if (session('status') === __('passwords.reset'))
        <script>
            localStorage.setItem('passwordResetCompleted', Date.now().toString());
        </script>
    @endif
    <script>
        window.loginLockoutSeconds = {{ session('login_lockout_seconds', 0) }};
    </script>
    <script src="{{ asset('js/login.js') }}"></script>
@endsection
