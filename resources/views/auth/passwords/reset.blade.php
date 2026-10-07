@extends('layouts.auth')

@section('title', 'Reinitialiser le mot de passe - IEG')

@section('content')
<div class="login-wrapper">
    <div class="login-left">
        <h1 class="login-titre">
            IEG <br>
            <span class="login-titre-bleu">Parc Info</span>
        </h1>
        <p class="login-desc">
            Choisissez un nouveau mot de passe pour securiser votre compte.
        </p>
        <div class="login-features">
            <div class="login-feature">
                <div class="login-feature-icon"><i class="bi bi-shield-lock"></i></div>
                8 caracteres minimum
            </div>
            <div class="login-feature">
                <div class="login-feature-icon"><i class="bi bi-fonts"></i></div>
                Majuscule et minuscule
            </div>
            <div class="login-feature">
                <div class="login-feature-icon"><i class="bi bi-123"></i></div>
                Chiffre et caractere special
            </div>
        </div>
    </div>

    <div class="login-right">
        <div class="login-card verification-card">
            <div class="login-logo"><i class="bi bi-lock"></i></div>
            <h2 class="login-welcome">Nouveau mot de passe</h2>
            <p class="login-subtitle">Definissez le nouveau mot de passe de votre compte.</p>

            @if ($errors->any())
                <div class="login-error" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>{{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="login-field">
                    <label class="login-label" for="email">ADRESSE E-MAIL</label>
                    <div class="login-input-wrap">
                        <i class="bi bi-envelope login-icon"></i>
                        <input id="email"
                               type="email"
                               name="email"
                               class="login-input"
                               value="{{ $email ?? old('email') }}"
                               autocomplete="email"
                               required>
                    </div>
                </div>

                <div class="login-field">
                    <label class="login-label" for="password">NOUVEAU MOT DE PASSE</label>
                    <div class="login-input-wrap">
                        <i class="bi bi-lock login-icon"></i>
                        <input id="password"
                               type="password"
                               name="password"
                               class="login-input"
                               autocomplete="new-password"
                               required>
                        <button type="button" class="password-eye-button" data-password-target="password" aria-label="Afficher le mot de passe">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="login-field">
                    <label class="login-label" for="password_confirmation">CONFIRMER LE MOT DE PASSE</label>
                    <div class="login-input-wrap">
                        <i class="bi bi-lock-fill login-icon"></i>
                        <input id="password_confirmation"
                               type="password"
                               name="password_confirmation"
                               class="login-input"
                               autocomplete="new-password"
                               required>
                        <button type="button" class="password-eye-button" data-password-target="password_confirmation" aria-label="Afficher la confirmation">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="bi bi-check-circle"></i> Reinitialiser le mot de passe
                </button>
            </form>

            <a href="{{ route('login') }}" class="btn-auth-secondary text-decoration-none">
                <i class="bi bi-arrow-left"></i> Retour a la connexion
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.querySelectorAll('[data-password-target]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordTarget);
            const icon = button.querySelector('i');
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            icon.classList.toggle('bi-eye', visible);
            icon.classList.toggle('bi-eye-slash', !visible);
        });
    });
</script>
@endsection
