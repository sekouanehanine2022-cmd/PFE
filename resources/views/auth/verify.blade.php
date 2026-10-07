@extends('layouts.auth')

@section('title', 'Verification de l\'adresse e-mail - EFEL')

@section('content')
<div class="login-wrapper">
    <div class="login-left">
        <div class="login-badge">VERIFICATION DE SECURITE</div>

        <h1 class="login-titre">
            Validez votre <br>
            <span class="login-titre-bleu">adresse e-mail</span>
        </h1>

        <p class="login-desc">
            Cette verification confirme que vous avez acces a l'adresse
            professionnelle associee a votre compte.
        </p>

        <div class="login-features">
            <div class="login-feature">
                <div class="login-feature-icon">
                    <i class="bi bi-envelope-check"></i>
                </div>
                Lien de verification personnel
            </div>
            <div class="login-feature">
                <div class="login-feature-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                Protection de votre compte
            </div>
            <div class="login-feature">
                <div class="login-feature-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                Acces apres validation
            </div>
        </div>
    </div>

    <div class="login-right">
        <div class="login-card verification-card"
             id="verification-card"
             data-status-url="{{ route('verification.status') }}"
             data-redirect-url="{{ route('dashboard') }}">
            <div id="verification-en-attente">
                <div class="login-logo">
                    <i class="bi bi-envelope-check"></i>
                </div>

                <h2 class="login-welcome">Verifiez votre boite mail</h2>
                <p class="login-subtitle verification-subtitle">
                    Un lien de verification a ete envoye a<br>
                    <strong>{{ auth()->user()->email }}</strong>
                </p>

                @if (session('resent'))
                    <div class="login-success" role="alert">
                        <i class="bi bi-check-circle"></i>
                        Un nouveau lien de verification vient d'etre envoye.
                    </div>
                @endif

                @if ($errors->any())
                    <div class="login-error" role="alert">
                        <i class="bi bi-exclamation-triangle"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="verification-message">
                    <i class="bi bi-info-circle"></i>
                    <p>
                        Consultez votre boite de reception et cliquez sur le lien
                        contenu dans l'e-mail pour acceder a l'application.
                    </p>
                </div>

                <form method="POST" action="{{ route('verification.resend') }}">
                    @csrf
                    <button type="submit" class="btn-login">
                        <i class="bi bi-arrow-clockwise"></i>
                        Renvoyer le lien de verification
                    </button>
                </form>

                <div class="verification-separator"><span>Adresse incorrecte ?</span></div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-auth-secondary">
                        <i class="bi bi-box-arrow-left"></i>
                        Se deconnecter
                    </button>
                </form>
            </div>

            <div class="verification-success-state d-none" id="verification-reussie" role="status" aria-live="polite">
                <div class="verification-success-icon">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h2>Adresse e-mail verifiee</h2>
                <p>Votre adresse a ete verifiee avec succes.</p>
                <small>Ouverture de votre espace...</small>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/verification-email.js') }}"></script>
@endsection
