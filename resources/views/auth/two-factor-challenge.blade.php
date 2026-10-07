@extends('layouts.auth')

@section('title', 'Double authentification - EFEL')

@section('content')
<div class="login-wrapper">
    <div class="login-left">
        <h1 class="login-titre">
            Protegez votre<br>
            <span class="login-titre-bleu">connexion</span>
        </h1>

        <p class="login-desc">
            Confirmez votre identite avec l application d authentification associee a votre compte.
        </p>

        <div class="login-features">
            <div class="login-feature">
                <div class="login-feature-icon"><i class="bi bi-phone"></i></div>
                Code temporaire renouvele automatiquement
            </div>
            <div class="login-feature">
                <div class="login-feature-icon"><i class="bi bi-shield-check"></i></div>
                Protection supplementaire du compte
            </div>
        </div>
    </div>

    <div class="login-right">
        <div class="login-card two-factor-card">
            <div class="login-logo">
                <i class="bi bi-shield-lock"></i>
            </div>

            <h2 class="login-welcome">Verification de securite</h2>
            <p class="login-subtitle">Saisissez votre code pour terminer la connexion</p>

            @if ($errors->any())
                <div class="login-error">
                    <i class="bi bi-exclamation-triangle"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="two-factor-tabs" role="tablist" aria-label="Mode de verification">
                <button type="button"
                        class="two-factor-tab"
                        data-two-factor-tab="application"
                        aria-controls="two-factor-application">
                    Application
                </button>
                <button type="button"
                        class="two-factor-tab"
                        data-two-factor-tab="recuperation"
                        aria-controls="two-factor-recuperation">
                    Recuperation
                </button>
            </div>

            <div id="two-factor-application" data-two-factor-panel="application">
                <form method="POST" action="{{ route('double-authentification.verifier') }}">
                    @csrf
                    <div class="login-field">
                        <label class="login-label" for="code_2fa">CODE A 6 CHIFFRES</label>
                        <div class="login-input-wrap">
                            <i class="bi bi-key login-icon"></i>
                            <input type="text"
                                   name="code_2fa"
                                   id="code_2fa"
                                   class="login-input two-factor-code-input"
                                   value="{{ old('code_2fa') }}"
                                   inputmode="numeric"
                                   pattern="[0-9]{6}"
                                   maxlength="6"
                                   autocomplete="one-time-code"
                                   autofocus
                                   required>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="bi bi-check-circle"></i>
                        Verifier
                    </button>
                </form>
            </div>

            <div id="two-factor-recuperation" data-two-factor-panel="recuperation" hidden>
                <form method="POST" action="{{ route('double-authentification.recuperation') }}">
                    @csrf
                    <div class="login-field">
                        <label class="login-label" for="code_recuperation">CODE DE RECUPERATION</label>
                        <div class="login-input-wrap">
                            <i class="bi bi-key-fill login-icon"></i>
                            <input type="text"
                                   name="code_recuperation"
                                   id="code_recuperation"
                                   class="login-input two-factor-code-input"
                                   value="{{ old('code_recuperation') }}"
                                   placeholder="XXXXX-XXXXX"
                                   maxlength="11"
                                   autocomplete="off"
                                   required>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="bi bi-check-circle"></i>
                        Utiliser ce code
                    </button>
                </form>
            </div>

            <form method="POST" action="{{ route('double-authentification.annuler-connexion') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-auth-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Revenir a la connexion
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script>
        window.twoFactorMode = @json(session('two_factor_mode', $errors->has('code_recuperation') ? 'recuperation' : 'application'));
    </script>
    <script src="{{ asset('js/two-factor-challenge.js') }}"></script>
@endsection
