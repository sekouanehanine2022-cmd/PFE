{{-- resources/views/systeme/parametres.blade.php --}}

@extends('layouts.app')

@section('title', 'Parametres')

@section('styles')
    <link href="{{ asset('css/parametres.css') }}" rel="stylesheet">
@endsection

@section('scripts')
    <script>
        window.parametresModal2fa = @json(session('modal_2fa'));
    </script>
    <script src="{{ asset('js/parametres.js') }}"></script>
@endsection

@section('content')

    <div class="mb-4">
        <div class="d-flex align-items-center gap-3 mt-2">
            <h2 class="fw-bold mb-0">Parametres</h2>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2">
            <i class="bi bi-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <div class="row g-4">

        <div class="col-12 col-xl-7">
            <div class="param-card">
                <div class="param-card-titre">
                    <i class="bi bi-person"></i>
                    <span>Mon profil</span>
                </div>

                <div class="profile-list mt-3">
                    <div class="profile-row">
                        <div class="profile-label">
                            <i class="bi bi-person-badge"></i>
                            <span>Nom</span>
                        </div>
                        <strong>{{ $profil['nom'] }}</strong>
                    </div>

                    <div class="profile-row">
                        <div class="profile-label">
                            <i class="bi bi-envelope"></i>
                            <span>Email</span>
                        </div>
                        <strong>{{ $profil['email'] }}</strong>
                    </div>

                    <div class="profile-row">
                        <div class="profile-label">
                            <i class="bi bi-briefcase"></i>
                            <span>Service</span>
                        </div>
                        <strong>{{ $profil['service'] }}</strong>
                    </div>

                    <div class="profile-row">
                        <div class="profile-label">
                            <i class="bi bi-person-workspace"></i>
                            <span>Poste</span>
                        </div>
                        <strong>{{ $profil['poste'] }}</strong>
                    </div>

                    <div class="profile-row">
                        <div class="profile-label">
                            <i class="bi bi-shield-check"></i>
                            <span>Role</span>
                        </div>
                        <strong>{{ ucfirst($profil['role']) }}</strong>
                    </div>
                </div>

                <div class="profile-note mt-3">
                    <i class="bi bi-info-circle"></i>
                    <span>Ces informations sont gerees par l'administration. Contactez le support IT pour toute correction.</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="param-card mb-4">
                <div class="param-card-titre">
                    <i class="bi bi-shield-lock"></i>
                    <span>Securite</span>
                </div>

                <form method="POST" action="{{ route('parametres.mot-de-passe') }}" class="mt-3">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label class="param-label" for="mot_de_passe_actuel">Mot de passe actuel</label>
                        <div class="input-group">
                            <input type="password"
                                   name="mot_de_passe_actuel"
                                   id="mot_de_passe_actuel"
                                   class="form-control @error('mot_de_passe_actuel') is-invalid @enderror"
                                   autocomplete="current-password"
                                   required>
                            <button type="button" class="btn btn-outline-secondary password-toggle" data-target="mot_de_passe_actuel">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="param-label" for="nouveau_mot_de_passe">Nouveau mot de passe</label>
                        <div class="input-group">
                            <input type="password"
                                   name="nouveau_mot_de_passe"
                                   id="nouveau_mot_de_passe"
                                   class="form-control @error('nouveau_mot_de_passe') is-invalid @enderror"
                                   autocomplete="new-password"
                                   required>
                            <button type="button" class="btn btn-outline-secondary password-toggle" data-target="nouveau_mot_de_passe">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">
                            8 caracteres minimum, avec au moins une majuscule, une minuscule, un chiffre et un caractere special. Ne doit pas contenir votre nom ou prenom.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="param-label" for="nouveau_mot_de_passe_confirmation">Confirmer le nouveau mot de passe</label>
                        <div class="input-group">
                            <input type="password"
                                   name="nouveau_mot_de_passe_confirmation"
                                   id="nouveau_mot_de_passe_confirmation"
                                   class="form-control"
                                   autocomplete="new-password"
                                   required>
                            <button type="button" class="btn btn-outline-secondary password-toggle" data-target="nouveau_mot_de_passe_confirmation">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-shield-check"></i>
                        Mettre a jour le mot de passe
                    </button>
                </form>
            </div>

            <div class="param-card">
                <div class="param-card-titre">
                    <i class="bi bi-phone"></i>
                    <span>Double authentification</span>
                </div>

                @if (session('codes_recuperation_2fa'))
                    <div class="codes-recuperation mt-3">
                        <div class="d-flex align-items-start gap-2 mb-3">
                            <i class="bi bi-key text-success"></i>
                            <div>
                                <strong>Codes de recuperation</strong>
                                <p class="mb-0 text-muted small">
                                    Conservez ces codes dans un endroit sur. Ils ne seront plus affiches.
                                </p>
                            </div>
                        </div>
                        <div class="codes-recuperation-grille">
                            @foreach (session('codes_recuperation_2fa') as $codeRecuperation)
                                <code>{{ $codeRecuperation }}</code>
                            @endforeach
                        </div>
                    </div>
                @elseif ($doubleAuthentification['active'])
                    <div class="etat-2fa actif mt-3">
                        <i class="bi bi-shield-check"></i>
                        <div>
                            <strong>Protection active</strong>
                            <p class="mb-0">Votre compte utilise la double authentification.</p>
                        </div>
                    </div>

                    <div class="actions-2fa mt-3">
                        <button type="button"
                                class="btn btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#modalRegenererCodes2fa">
                            <i class="bi bi-arrow-clockwise"></i>
                            Regenerer les codes
                        </button>
                        <button type="button"
                                class="btn btn-outline-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#modalDesactiver2fa">
                            <i class="bi bi-shield-x"></i>
                            Desactiver
                        </button>
                    </div>
                @elseif ($doubleAuthentification['en_attente'])
                    <p class="text-muted mt-3 mb-3">
                        Scannez ce QR Code avec Google Authenticator ou Microsoft Authenticator.
                    </p>

                    <div class="qr-code-2fa">
                        {!! $doubleAuthentification['qr_code'] !!}
                    </div>

                    <div class="secret-2fa mt-3">
                        <span>Cle de configuration manuelle</span>
                        <code>{{ $doubleAuthentification['secret'] }}</code>
                    </div>

                    <form method="POST" action="{{ route('parametres.double-authentification.confirmer') }}" class="mt-3">
                        @csrf
                        <label class="param-label" for="code_2fa">Code a 6 chiffres</label>
                        <input type="text"
                               name="code_2fa"
                               id="code_2fa"
                               class="form-control text-center code-2fa @error('code_2fa') is-invalid @enderror"
                               value="{{ old('code_2fa') }}"
                               inputmode="numeric"
                               pattern="[0-9]{6}"
                               maxlength="6"
                               autocomplete="one-time-code"
                               required>
                        <button type="submit" class="btn btn-primary w-100 mt-3">
                            <i class="bi bi-shield-check"></i>
                            Confirmer l activation
                        </button>
                    </form>

                    <form method="POST" action="{{ route('parametres.double-authentification.annuler') }}" class="mt-2">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-secondary w-100">
                            Annuler
                        </button>
                    </form>
                @else
                    <p class="text-muted mt-3 mb-3">
                        Ajoutez une etape de verification supplementaire a chaque connexion.
                    </p>

                    <form method="POST" action="{{ route('parametres.double-authentification.preparer') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-shield-plus"></i>
                            Activer la double authentification
                        </button>
                    </form>
                @endif
            </div>
        </div>

    </div>

    @if ($doubleAuthentification['active'])
        <div class="modal fade" id="modalRegenererCodes2fa" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST"
                      action="{{ route('parametres.double-authentification.codes-regenerer') }}"
                      class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-arrow-clockwise me-2"></i>Regenerer les codes
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">
                            Les anciens codes de recuperation seront immediatement invalides.
                        </p>
                        <label class="param-label" for="mot_de_passe_codes_2fa">Mot de passe actuel</label>
                        <input type="password"
                               name="mot_de_passe_2fa"
                               id="mot_de_passe_codes_2fa"
                               class="form-control"
                               autocomplete="current-password"
                               required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-arrow-clockwise"></i>
                            Regenerer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="modalDesactiver2fa" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST"
                      action="{{ route('parametres.double-authentification.desactiver') }}"
                      class="modal-content">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-danger">
                            <i class="bi bi-shield-x me-2"></i>Desactiver la double authentification
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">
                            Votre compte ne demandera plus de code de securite lors de la connexion.
                        </p>
                        <label class="param-label" for="mot_de_passe_desactivation_2fa">Mot de passe actuel</label>
                        <input type="password"
                               name="mot_de_passe_2fa"
                               id="mot_de_passe_desactivation_2fa"
                               class="form-control"
                               autocomplete="current-password"
                               required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-shield-x"></i>
                            Desactiver
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

@endsection
