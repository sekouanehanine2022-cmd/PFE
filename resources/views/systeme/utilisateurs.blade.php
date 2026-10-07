@extends('layouts.app')

@section('title', 'Comptes utilisateurs - IEG')

@section('styles')
    <link href="{{ asset('css/materiel.css') }}" rel="stylesheet">
    <link href="{{ asset('css/utilisateurs.css') }}" rel="stylesheet">
@endsection

@section('content')
    @php
        $ancienTypeCompte = old('type_compte', 'personnel');
    @endphp

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mt-2 gap-3 flex-wrap">
            <h2 class="fw-bold mb-0">Comptes utilisateurs</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNouvelUtilisateur">
                <i class="bi bi-person-plus"></i> Nouveau compte
            </button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0f4ff">
                    <i class="bi bi-people" style="color:#3b82f6"></i>
                </div>
                <div>
                    <div class="stat-label">Total comptes</div>
                    <div class="stat-number">{{ $total }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0fff4">
                    <i class="bi bi-person-badge" style="color:#22c55e"></i>
                </div>
                <div>
                    <div class="stat-label">Personnel</div>
                    <div class="stat-number">{{ $totalPersonnels }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff7ed">
                    <i class="bi bi-mortarboard" style="color:#f97316"></i>
                </div>
                <div>
                    <div class="stat-label">Etudiants</div>
                    <div class="stat-number">{{ $totalEtudiants }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff0f0">
                    <i class="bi bi-envelope-exclamation" style="color:#ef4444"></i>
                </div>
                <div>
                    <div class="stat-label">Verification en attente</div>
                    <div class="stat-number">{{ $enAttenteVerification }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3 p-3 mb-4 shadow-sm">
        <form method="GET" action="{{ route('utilisateurs.index') }}">
            @if ($filtreType !== '')
                <input type="hidden" name="type" value="{{ $filtreType }}">
            @endif

            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="input-group utilisateurs-recherche">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="search"
                           name="search"
                           class="form-control border-start-0"
                           value="{{ $recherche }}"
                           placeholder="Rechercher par nom ou adresse e-mail">
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('utilisateurs.index', array_filter(['search' => $recherche])) }}"
                       class="btn btn-filtre {{ $filtreType === '' ? 'active-filtre' : '' }}">
                        Tous
                    </a>
                    <a href="{{ route('utilisateurs.index', array_filter(['type' => 'personnel', 'search' => $recherche])) }}"
                       class="btn btn-filtre {{ $filtreType === 'personnel' ? 'active-filtre' : '' }}">
                        <span class="point-bleu"></span> Personnel
                    </a>
                    <a href="{{ route('utilisateurs.index', array_filter(['type' => 'etudiant', 'search' => $recherche])) }}"
                       class="btn btn-filtre {{ $filtreType === 'etudiant' ? 'active-filtre' : '' }}">
                        <span class="point-orange"></span> Etudiants
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="row g-3 align-items-stretch inventaire-layout">
        <div class="col-12">
            <div class="bg-white rounded-3 shadow-sm tableau-card utilisateurs-tableau">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Type</th>
                        <th>Profil</th>
                        <th>Adresse e-mail</th>
                        <th>Verification</th>
                        <th>Mot de passe</th>
                        <th>Acces</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($utilisateurs as $utilisateur)
                        @php
                            $initiales = collect(explode(' ', $utilisateur->name))
                                ->filter()
                                ->take(2)
                                ->map(fn ($partie) => mb_strtoupper(mb_substr($partie, 0, 1)))
                                ->implode('');

                            if ($utilisateur->personnel) {
                                $typeCompte = $utilisateur->personnel->role === 'admin' ? 'Administrateur' : 'Personnel';
                                $profil = $utilisateur->personnel->service ?: 'Non renseigne';
                                $classeType = $utilisateur->personnel->role === 'admin' ? 'admin' : 'personnel';
                            } elseif ($utilisateur->etudiant) {
                                $typeCompte = 'Etudiant';
                                $profil = $utilisateur->etudiant->promotion ?: 'Non renseignee';
                                $classeType = 'etudiant';
                            } else {
                                $typeCompte = 'Non defini';
                                $profil = 'Profil incomplet';
                                $classeType = 'inconnu';
                            }
                        @endphp
                        <tr>
                            <td>
                                <div class="utilisateur-identite">
                                    <span class="utilisateur-avatar">{{ $initiales ?: '?' }}</span>
                                    <strong>{{ $utilisateur->name }}</strong>
                                </div>
                            </td>
                            <td><span class="badge-type {{ $classeType }}">{{ $typeCompte }}</span></td>
                            <td>{{ $profil }}</td>
                            <td>{{ $utilisateur->email }}</td>
                            <td>
                                @if ($utilisateur->email_verified_at)
                                    <span class="badge-etat valide"><i class="bi bi-check-circle"></i> Verifiee</span>
                                @else
                                    <span class="badge-etat attente"><i class="bi bi-clock"></i> En attente</span>
                                @endif
                            </td>
                            <td>
                                @if ($utilisateur->mot_de_passe_change)
                                    <span class="badge-etat valide"><i class="bi bi-check-circle"></i> Modifie</span>
                                @else
                                    <span class="badge-etat temporaire"><i class="bi bi-key"></i> Temporaire</span>
                                @endif
                            </td>
                            <td>
                                @if ($utilisateur->acces_bloque)
                                    <span class="badge-etat bloque"><i class="bi bi-lock"></i> Bloque</span>
                                @else
                                    <span class="badge-etat actif"><i class="bi bi-unlock"></i> Actif</span>
                                @endif
                            </td>
                            <td>
                                <div class="utilisateur-actions">
                                    <button type="button"
                                            class="btn btn-sm btn-action btn-reinitialisation-utilisateur"
                                            data-url="{{ route('utilisateurs.mot-de-passe', $utilisateur) }}"
                                            data-utilisateur-id="{{ $utilisateur->id }}"
                                            data-nom="{{ $utilisateur->name }}"
                                            title="Reinitialiser le mot de passe"
                                            aria-label="Reinitialiser le mot de passe de {{ $utilisateur->name }}"
                                            @disabled(auth()->id() === $utilisateur->id)>
                                        <i class="bi bi-key"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-sm btn-action btn-blocage-utilisateur"
                                            data-url="{{ route('utilisateurs.blocage', $utilisateur) }}"
                                            data-nom="{{ $utilisateur->name }}"
                                            data-acces-bloque="{{ $utilisateur->acces_bloque ? '1' : '0' }}"
                                            title="{{ $utilisateur->acces_bloque ? 'Debloquer le compte' : 'Bloquer le compte' }}"
                                            aria-label="{{ $utilisateur->acces_bloque ? 'Debloquer' : 'Bloquer' }} le compte de {{ $utilisateur->name }}"
                                            @disabled(auth()->id() === $utilisateur->id)>
                                        <i class="bi {{ $utilisateur->acces_bloque ? 'bi-unlock' : 'bi-lock' }}"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-sm btn-action btn-suppression-utilisateur"
                                            data-url="{{ route('utilisateurs.destroy', $utilisateur) }}"
                                            data-nom="{{ $utilisateur->name }}"
                                            title="Supprimer le compte"
                                            aria-label="Supprimer le compte de {{ $utilisateur->name }}"
                                            @disabled(auth()->id() === $utilisateur->id)>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="utilisateurs-vide">Aucun compte trouve.</td>
                        </tr>
                    @endforelse
                </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap p-3 border-top">
                    <small class="text-muted">
                        Affichage {{ $utilisateurs->count() }} sur {{ $utilisateurs->total() }} compte(s)
                    </small>
                    @if ($utilisateurs->hasPages())
                        {{ $utilisateurs->onEachSide(1)->links('pagination.comptes-utilisateurs') }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade"
         id="modalNouvelUtilisateur"
         tabindex="-1"
         aria-hidden="true"
        data-reouvrir="{{ $errors->any() ? '1' : '0' }}">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <form method="POST"
                  action="{{ route('utilisateurs.store') }}"
                  id="form-utilisateur"
                  class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-plus me-2"></i>Nouveau compte
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>

                <div class="modal-body">
                        <div class="form-section">
                            <h6>Informations du compte</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="type-compte" class="form-label fw-semibold">Type de compte *</label>
                                    <select name="type_compte" id="type-compte" class="form-select" required>
                                        <option value="personnel" @selected($ancienTypeCompte === 'personnel')>Personnel</option>
                                        <option value="etudiant" @selected($ancienTypeCompte === 'etudiant')>Etudiant</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="nom-utilisateur" class="form-label fw-semibold">Nom et prenom *</label>
                                    <input type="text"
                                           name="name"
                                           id="nom-utilisateur"
                                           class="form-control"
                                           value="{{ old('name') }}"
                                           maxlength="100"
                                           autocomplete="off"
                                           required>
                                </div>
                                <div class="col-12">
                                    <label for="email-utilisateur" class="form-label fw-semibold">Adresse e-mail professionnelle *</label>
                                    <input type="email"
                                           name="email"
                                           id="email-utilisateur"
                                           class="form-control"
                                           value="{{ old('email') }}"
                                           maxlength="255"
                                           autocomplete="off"
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label for="password-temporaire" class="form-label fw-semibold">Mot de passe temporaire *</label>
                                    <input type="password"
                                           name="password_temporaire"
                                           id="password-temporaire"
                                           class="form-control"
                                           minlength="8"
                                           autocomplete="new-password"
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label for="password-temporaire-confirmation" class="form-label fw-semibold">Confirmer le mot de passe *</label>
                                    <input type="password"
                                           name="password_temporaire_confirmation"
                                           id="password-temporaire-confirmation"
                                           class="form-control"
                                           minlength="8"
                                           autocomplete="new-password"
                                           required>
                                </div>
                            </div>
                        </div>

                        <div class="form-section {{ $ancienTypeCompte === 'personnel' ? '' : 'd-none' }}" id="champs-personnel">
                            <h6>Informations professionnelles</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="service-utilisateur" class="form-label fw-semibold">Service *</label>
                                    <input type="text" name="service" id="service-utilisateur" class="form-control" value="{{ old('service') }}" maxlength="100">
                                </div>
                                <div class="col-md-6">
                                    <label for="poste-utilisateur" class="form-label fw-semibold">Poste *</label>
                                    <input type="text" name="poste" id="poste-utilisateur" class="form-control" value="{{ old('poste') }}" maxlength="100">
                                </div>
                                <div class="col-md-6">
                                    <label for="telephone-utilisateur" class="form-label fw-semibold">Telephone</label>
                                    <input type="text" name="telephone" id="telephone-utilisateur" class="form-control" value="{{ old('telephone') }}" maxlength="20">
                                </div>
                                <div class="col-md-6">
                                    <label for="type-contrat-utilisateur" class="form-label fw-semibold">Type de contrat *</label>
                                    <select name="type_contrat" id="type-contrat-utilisateur" class="form-select">
                                        <option value="">Choisir...</option>
                                        <option value="cdi" @selected(old('type_contrat') === 'cdi')>CDI</option>
                                        <option value="cdd" @selected(old('type_contrat') === 'cdd')>CDD</option>
                                        <option value="alternant_interne" @selected(old('type_contrat') === 'alternant_interne')>Alternant interne</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section {{ $ancienTypeCompte === 'etudiant' ? '' : 'd-none' }}" id="champs-etudiant">
                            <h6>Informations de formation</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="type-etudiant" class="form-label fw-semibold">Type d'etudiant *</label>
                                    <select name="type_etudiant" id="type-etudiant" class="form-select">
                                        <option value="">Choisir...</option>
                                        <option value="alt_externe" @selected(old('type_etudiant') === 'alt_externe')>Alternance externe</option>
                                        <option value="alt_interne" @selected(old('type_etudiant') === 'alt_interne')>Alternance interne</option>
                                        <option value="etud_initial" @selected(old('type_etudiant') === 'etud_initial')>Formation initiale</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="promotion-utilisateur" class="form-label fw-semibold">Promotion *</label>
                                    <input type="text" name="promotion" id="promotion-utilisateur" class="form-control" value="{{ old('promotion') }}" maxlength="100">
                                </div>
                                <div class="col-md-6">
                                    <label for="date-fin-formation" class="form-label fw-semibold">Date de fin de formation</label>
                                    <input type="date" name="date_fin_formation" id="date-fin-formation" class="form-control" value="{{ old('date_fin_formation') }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="etablissement-utilisateur" class="form-label fw-semibold">Etablissement *</label>
                                    <input type="text" name="etablissement" id="etablissement-utilisateur" class="form-control" value="{{ old('etablissement') }}" maxlength="100">
                                </div>
                            </div>
                        </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-enregistrer-utilisateur">
                        <i class="bi bi-save me-1"></i> Creer le compte
                    </button>
                </div>
            </form>
        </div>
    </div>

    @php
        $erreursReinitialisation = $errors->getBag('reinitialisationMotDePasse');
        $reinitialisationUtilisateurId = old('reinitialisation_utilisateur_id');
        $forcerChangementCoche = old('reinitialisation_formulaire_envoye')
            ? old('forcer_changement_mot_de_passe') === '1'
            : true;
    @endphp

    <div class="modal fade"
         id="modalReinitialisationMotDePasse"
         tabindex="-1"
         aria-hidden="true"
         data-reouvrir="{{ $erreursReinitialisation->any() ? '1' : '0' }}"
         data-utilisateur-id="{{ $reinitialisationUtilisateurId }}">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="form-reinitialisation-mot-de-passe" class="modal-content">
                @csrf
                @method('PATCH')
                <input type="hidden" name="reinitialisation_utilisateur_id" id="reinitialisation-utilisateur-id" value="{{ $reinitialisationUtilisateurId }}">
                <input type="hidden" name="reinitialisation_formulaire_envoye" value="1">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-key me-2"></i>Reinitialiser le mot de passe
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>

                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Nouveau mot de passe pour <strong id="nom-reinitialisation-utilisateur"></strong>.
                    </p>

                    <div class="mb-3">
                        <label for="nouveau-mot-de-passe-utilisateur" class="form-label fw-semibold">Nouveau mot de passe *</label>
                        <input type="password"
                               name="nouveau_mot_de_passe"
                               id="nouveau-mot-de-passe-utilisateur"
                               class="form-control {{ $erreursReinitialisation->has('nouveau_mot_de_passe') ? 'is-invalid' : '' }}"
                               minlength="8"
                               autocomplete="new-password"
                               required>
                        @if ($erreursReinitialisation->has('nouveau_mot_de_passe'))
                            <div class="invalid-feedback">{{ $erreursReinitialisation->first('nouveau_mot_de_passe') }}</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="confirmation-mot-de-passe-utilisateur" class="form-label fw-semibold">Confirmer le nouveau mot de passe *</label>
                        <input type="password"
                               name="nouveau_mot_de_passe_confirmation"
                               id="confirmation-mot-de-passe-utilisateur"
                               class="form-control"
                               minlength="8"
                               autocomplete="new-password"
                               required>
                    </div>

                    <p class="small text-muted mb-3">
                        8 caracteres minimum, avec une majuscule, une minuscule, un chiffre et un caractere special.
                    </p>

                    <div class="form-check">
                        <input class="form-check-input"
                               type="checkbox"
                               name="forcer_changement_mot_de_passe"
                               value="1"
                               id="forcer-changement-mot-de-passe"
                               @checked($forcerChangementCoche)>
                        <label class="form-check-label" for="forcer-changement-mot-de-passe">
                            Demander a l utilisateur de changer son mot de passe a la prochaine connexion
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-confirmer-reinitialisation-mot-de-passe">
                        <i class="bi bi-key me-1"></i> Reinitialiser
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalBlocageUtilisateur" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="form-blocage-utilisateur" class="modal-content">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="titre-blocage-utilisateur">
                        <i class="bi bi-lock me-2"></i>Bloquer l'acces
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2" id="texte-blocage-utilisateur">
                        Voulez-vous bloquer l'acces de <strong id="nom-blocage-utilisateur"></strong> ?
                    </p>
                    <p class="text-muted small mb-0" id="consequence-blocage-utilisateur">
                        Toutes ses sessions ouvertes seront fermees.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning" id="btn-confirmer-blocage-utilisateur">
                        <i class="bi bi-lock me-1"></i> Bloquer l'acces
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalSuppressionUtilisateur" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="form-suppression-utilisateur" class="modal-content">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="bi bi-trash me-2"></i>Confirmer la suppression
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">
                        Voulez-vous vraiment supprimer le compte de
                        <strong id="nom-suppression-utilisateur"></strong> ?
                    </p>
                    <p class="text-muted small mb-0">
                        La suppression sera refusee si ce compte possede un historique.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger" id="btn-confirmer-suppression-utilisateur">
                        <i class="bi bi-trash me-1"></i> Supprimer le compte
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/utilisateurs.js') }}"></script>
@endsection
