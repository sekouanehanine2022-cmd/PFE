@extends('layouts.auth')

@section('title', 'Mot de passe oublie - IEG')

@section('content')
<div class="login-wrapper">
    <div class="login-left">
        <h1 class="login-titre">
            IEG <br>
            <span class="login-titre-bleu">Parc Info</span>
        </h1>
        <p class="login-desc">
            Retrouvez l'acces a votre espace en recevant un lien personnel de reinitialisation.
        </p>
        <div class="login-features">
            <div class="login-feature">
                <div class="login-feature-icon"><i class="bi bi-envelope-check"></i></div>
                Lien personnel et securise
            </div>
            <div class="login-feature">
                <div class="login-feature-icon"><i class="bi bi-clock"></i></div>
                Lien a duree limitee
            </div>
            <div class="login-feature">
                <div class="login-feature-icon"><i class="bi bi-shield-check"></i></div>
                Protection de votre compte
            </div>
        </div>
    </div>

    <div class="login-right">
        <div class="login-card verification-card">
            <div class="login-logo"><i class="bi bi-key"></i></div>
            <h2 class="login-welcome">Mot de passe oublie</h2>
            <p class="login-subtitle">
                Saisissez l'adresse e-mail associee a votre compte.
            </p>

            @if (session('status'))
                <div class="login-success" role="alert">
                    <i class="bi bi-check-circle"></i>{{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="login-error" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>{{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="login-field">
                    <label class="login-label" for="email">ADRESSE E-MAIL</label>
                    <div class="login-input-wrap">
                        <i class="bi bi-envelope login-icon"></i>
                        <input id="email"
                               type="email"
                               name="email"
                               class="login-input"
                               value="{{ old('email') }}"
                               placeholder="votre.nom@efeledu.com"
                               autocomplete="email"
                               required
                               autofocus>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="bi bi-send"></i> Envoyer le lien
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
    window.addEventListener('storage', (event) => {
        if (event.key === 'passwordResetCompleted' && event.newValue) {
            window.location.replace(@json(route('login')));
        }
    });
</script>
@endsection
