{{-- resources/views/systeme/parametres.blade.php --}}

@extends('layouts.app')

@section('title', 'Parametres')

@section('styles')
    <link href="{{ asset('css/parametres.css') }}" rel="stylesheet">
@endsection

@section('scripts')
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

                <p class="text-muted mt-3 mb-3">
                    Ajoutez une etape de verification supplementaire a chaque connexion.
                </p>

                <button type="button" class="btn btn-primary w-100" disabled>
                    <i class="bi bi-shield-plus"></i>
                    Bientot disponible
                </button>
            </div>
        </div>

    </div>

@endsection
