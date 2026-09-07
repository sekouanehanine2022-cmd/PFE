{{-- resources/views/auth/changer-mot-de-passe.blade.php --}}

@extends('layouts.auth')

@section('title', 'Changer le mot de passe - EFEL')

@section('content')

<div class="login-wrapper">

    <div class="login-left">
        <div class="login-badge">PREMIERE CONNEXION</div>

        <h1 class="login-titre">
            Securisez <br>
            <span class="login-titre-bleu">votre compte</span>
        </h1>

        <p class="login-desc">
            Pour continuer, vous devez remplacer le mot de passe par defaut
            par un mot de passe personnel.
        </p>

        <div class="login-features">
            <div class="login-feature">
                <div class="login-feature-icon">
                    <i class="bi bi-shield-lock"></i>
                </div>
                Protection de votre espace
            </div>
            <div class="login-feature">
                <div class="login-feature-icon">
                    <i class="bi bi-key"></i>
                </div>
                Mot de passe personnel obligatoire
            </div>
            <div class="login-feature">
                <div class="login-feature-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                Acces debloque apres validation
            </div>
        </div>
    </div>

    <div class="login-right">
        <div class="login-card">
            <div class="login-logo">
                <i class="bi bi-shield-lock"></i>
            </div>

            <h2 class="login-welcome">Changer le mot de passe</h2>
            <p class="login-subtitle">Choisissez un nouveau mot de passe pour acceder a l'application</p>

            @if (session('avertissement'))
                <div class="login-error">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    {{ session('avertissement') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="login-error">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('mot-de-passe.update') }}">
                @csrf
                @method('PATCH')

                <div class="login-field">
                    <label class="login-label">MOT DE PASSE ACTUEL</label>
                    <div class="login-input-wrap">
                        <i class="bi bi-lock login-icon"></i>
                        <input type="password"
                               name="mot_de_passe_actuel"
                               class="login-input"
                               placeholder="Mot de passe actuel"
                               autocomplete="current-password"
                               required
                               autofocus>
                    </div>
                </div>

                <div class="login-field">
                    <label class="login-label">NOUVEAU MOT DE PASSE</label>
                    <div class="login-input-wrap">
                        <i class="bi bi-key login-icon"></i>
                        <input type="password"
                               name="nouveau_mot_de_passe"
                               class="login-input"
                               placeholder="8 caracteres minimum"
                               autocomplete="new-password"
                               required>
                    </div>
                </div>

                <div class="login-field">
                    <label class="login-label">CONFIRMATION</label>
                    <div class="login-input-wrap">
                        <i class="bi bi-check2-circle login-icon"></i>
                        <input type="password"
                               name="nouveau_mot_de_passe_confirmation"
                               class="login-input"
                               placeholder="Confirmer le nouveau mot de passe"
                               autocomplete="new-password"
                               required>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="bi bi-check-circle"></i> Valider et continuer
                </button>
            </form>
        </div>
    </div>

</div>

@endsection

@section('scripts')
    <script>
        window.addEventListener('pageshow', function (event) {
            var navigation = performance.getEntriesByType('navigation')[0];
            var retourHistorique = event.persisted || (navigation && navigation.type === 'back_forward');

            if (retourHistorique) {
                window.location.reload();
            }
        });
    </script>
@endsection
