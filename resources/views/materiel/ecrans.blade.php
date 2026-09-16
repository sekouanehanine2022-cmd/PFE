{{-- resources/views/materiel/ecrans.blade.php --}}

@extends('layouts.app')

@section('title', 'Écrans')

@section('styles')
    <link href="{{ asset('css/materiel.css') }}" rel="stylesheet">
@endsection

@section('content')

    {{-- Message de succès --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    {{-- Fil d'ariane + Titre --}}
    <div class="mb-4">
        <small class="text-muted">Dashboard &gt; Matériel &gt; Écrans</small>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <h2 class="fw-bold mb-0">Écrans</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary">
                    <i class="bi bi-download"></i> Exporter
                </button>
                <button class="btn btn-primary" type="button"
                        data-bs-toggle="modal" data-bs-target="#modalAjout" onclick="reinitialiserModalMateriel()">
                    <i class="bi bi-plus"></i> Ajouter un écran
                </button>
            </div>
        </div>
    </div>

    {{-- Les 4 cartes statistiques --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0f4ff">
                    <i class="bi bi-display" style="color:#3b82f6"></i>
                </div>
                <div>
                    <div class="stat-label">Total Écrans</div>
                    <div class="stat-number">{{ $total }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0fff4">
                    <i class="bi bi-check-circle" style="color:#22c55e"></i>
                </div>
                <div>
                    <div class="stat-label">Disponibles</div>
                    <div class="stat-number">{{ $disponibles }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff7f0">
                    <i class="bi bi-link-45deg" style="color:#f97316"></i>
                </div>
                <div>
                    <div class="stat-label">Affectés</div>
                    <div class="stat-number">{{ $affectes }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff0f0">
                    <i class="bi bi-exclamation-triangle" style="color:#ef4444"></i>
                </div>
                <div>
                    <div class="stat-label">En panne / Maint.</div>
                    <div class="stat-number">{{ $enPanne }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Barre de recherche + Filtres --}}
    <div class="bg-white rounded-3 p-3 mb-4 shadow-sm">
        <form method="GET" action="{{ route('ecrans.index') }}">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="input-group" style="max-width: 300px;">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0"
                           placeholder="Rechercher par nom, N° série..."
                           value="{{ request('search') }}">
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('ecrans.index') }}"
                       class="btn btn-filtre {{ !request('etat') ? 'active-filtre' : '' }}">Tous</a>
                    <a href="{{ route('ecrans.index', ['etat' => 'disponible']) }}"
                       class="btn btn-filtre {{ request('etat') == 'disponible' ? 'active-filtre' : '' }}">
                       <span class="point-vert"></span> Disponible</a>
                    <a href="{{ route('ecrans.index', ['etat' => 'affecte']) }}"
                       class="btn btn-filtre {{ request('etat') == 'affecte' ? 'active-filtre' : '' }}">
                       <span class="point-bleu"></span> Affecté</a>
                    <a href="{{ route('ecrans.index', ['etat' => 'emprunte']) }}"
                       class="btn btn-filtre {{ request('etat') == 'emprunte' ? 'active-filtre' : '' }}">
                       <span class="point-orange"></span> Emprunté</a>
                    <a href="{{ route('ecrans.index', ['etat' => 'en_panne']) }}"
                       class="btn btn-filtre {{ request('etat') == 'en_panne' ? 'active-filtre' : '' }}">
                       <span class="point-rouge"></span> En panne</a>
                </div>
            </div>
        </form>
    </div>

    {{-- Tableau --}}
    <div class="row g-3 align-items-stretch inventaire-layout">
        <div class="col-12">
            <div class="bg-white rounded-3 shadow-sm tableau-card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>N° Série</th>
                                <th>Nom / Modèle</th>
                                <th>Marque</th>
                                <th>Taille</th>
                                <th>Résolution</th>
                                <th>Dalle</th>
                                <th>État</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ecrans as $ecran)
                            @php
                                $affectationActive = $ecran->affectations->where('statut', 'active')->first();
                                $empruntActif = $ecran->emprunts->where('statut', 'en_cours')->first();
                                $affecteA = '-';
                                if ($affectationActive && $affectationActive->personnel && $affectationActive->personnel->user) {
                                    $affecteA = $affectationActive->personnel->user->name;
                                } elseif ($empruntActif && $empruntActif->etudiant && $empruntActif->etudiant->user) {
                                    $affecteA = $empruntActif->etudiant->user->name;
                                }

                                $historique = [];
                                foreach ($ecran->affectations as $aff) {
                                    if ($aff->personnel && $aff->personnel->user) {
                                        $historique[] = [
                                            'type'   => 'affectation',
                                            'nom'    => $aff->personnel->user->name,
                                            'date'   => $aff->date_debut ? \Carbon\Carbon::parse($aff->date_debut)->format('d/m/Y') : '-',
                                            'statut' => $aff->statut,
                                        ];
                                    }
                                }
                                foreach ($ecran->emprunts as $emp) {
                                    if ($emp->etudiant && $emp->etudiant->user) {
                                        $historique[] = [
                                            'type'   => 'emprunt',
                                            'nom'    => $emp->etudiant->user->name,
                                            'date'   => $emp->date_debut ? \Carbon\Carbon::parse($emp->date_debut)->format('d/m/Y') : '-',
                                            'statut' => $emp->statut,
                                        ];
                                    }
                                }
                            @endphp
                            <tr>
                                <td><span class="badge-serie">{{ $ecran->numero_serie }}</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="icone-appareil"><i class="bi bi-display"></i></div>
                                        <div>
                                            <div class="fw-semibold">{{ $ecran->nom }}</div>
                                            <div class="text-muted small">
                                                Acheté le {{ $ecran->date_achat ? $ecran->date_achat->format('d/m/Y') : '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $ecran->marque }}</td>
                                <td>{{ $ecran->taille }}</td>
                                <td>{{ $ecran->resolution }}</td>
                                <td>{{ $ecran->dalle ?? '-' }}</td>
                                <td>
                                    <span class="badge-etat {{ $ecran->etat }}">
                                        ● {{ ucfirst(str_replace('_', ' ', $ecran->etat)) }}
                                    </span>
                                </td>
                                <td>
                                    <button type="button"
                                            class="btn btn-sm btn-action"
                                            onclick="ouvrirDetail(this)"
                                            data-type-materiel="ecran"
                                            data-id="{{ $ecran->numero_serie }}"
                                            data-nom="{{ $ecran->nom }}"
                                            data-marque="{{ $ecran->marque }}"
                                            data-numero-serie="{{ $ecran->numero_serie }}"
                                            data-taille="{{ $ecran->taille }}"
                                            data-resolution="{{ $ecran->resolution }}"
                                            data-dalle="{{ $ecran->dalle ?? '-' }}"
                                            data-refresh="{{ $ecran->taux_rafraichissement ?? '-' }}"
                                            data-etat="{{ $ecran->etat }}"
                                            data-etat-label="{{ ucfirst(str_replace('_', ' ', $ecran->etat)) }}"
                                            data-emplacement="{{ $ecran->emplacement ?? '-' }}"
                                            data-date-achat="{{ $ecran->date_achat ? $ecran->date_achat->format('d/m/Y') : '-' }}"
                                            data-date-achat-iso="{{ $ecran->date_achat ? $ecran->date_achat->format('Y-m-d') : '' }}"
                                            data-affecte-a="{{ $affecteA }}"
                                            data-historique="{{ json_encode($historique) }}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Aucun écran trouvé</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <small class="text-muted">Affichage {{ $ecrans->count() }} sur {{ $total }} Écrans</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Popup détail --}}
    <div class="panneau-backdrop d-none" id="panneau-detail-backdrop" onclick="fermerDetail()"></div>

    <div class="d-none" id="panneau-detail">
        <div class="panneau-container">
            <div class="panneau-header">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge-etat disponible" id="detail-etat">● Disponible</span>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-action" type="button" onclick="fermerDetail()">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
                <h6 class="fw-bold mb-0" id="detail-nom">-</h6>
                <small class="text-muted">
                    N/S : <span id="detail-serie">-</span> · Acheté le <span id="detail-date">-</span>
                </small>
            </div>

            <div class="panneau-body">
                <div class="icone-detail my-3">
                    <i class="bi bi-display"></i>
                </div>

                <div class="section-detail">
                    <div class="section-detail-titre">
                        <i class="bi bi-display"></i> Spécifications
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-6">
                            <div class="spec-box">
                                <div class="spec-label">TAILLE</div>
                                <div class="spec-value" id="detail-taille">-</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="spec-box">
                                <div class="spec-label">RÉSOLUTION</div>
                                <div class="spec-value" id="detail-resolution">-</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="spec-box">
                                <div class="spec-label">DALLE</div>
                                <div class="spec-value" id="detail-dalle">-</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="spec-box">
                                <div class="spec-label">REFRESH</div>
                                <div class="spec-value" id="detail-refresh">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-detail mt-3">
                    <div class="section-detail-titre">
                        <i class="bi bi-info-circle"></i> Informations
                    </div>
                    <div class="mt-2">
                        <div class="info-ligne">
                            <i class="bi bi-tag"></i>
                            <span class="info-label">Marque</span>
                            <span class="info-value" id="detail-marque">-</span>
                        </div>
                        <div class="info-ligne">
                            <i class="bi bi-upc-scan"></i>
                            <span class="info-label">N° Série</span>
                            <span class="info-value" id="detail-serie-info">-</span>
                        </div>
                        <div class="info-ligne">
                            <i class="bi bi-geo-alt"></i>
                            <span class="info-label">Emplacement</span>
                            <span class="info-value" id="detail-emplacement">-</span>
                        </div>
                        <div class="info-ligne">
                            <i class="bi bi-person"></i>
                            <span class="info-label">Affecté à</span>
                            <span class="info-value" id="detail-affecte-a">-</span>
                        </div>
                    </div>
                </div>

                <div class="section-detail mt-3">
                    <div class="section-detail-titre">
                        <i class="bi bi-clock-history"></i> Historique
                    </div>
                    <div class="mt-2" id="detail-historique">
                        <p class="text-muted small">Aucun historique</p>
                    </div>
                </div>
            </div>

            <div class="panneau-footer">
                <div class="d-flex gap-2 mb-2">
                    <button class="btn btn-primary btn-sm flex-fill" type="button" id="btn-modifier">
                        <i class="bi bi-pencil"></i> Modifier
                    </button>
                </div>                <div class="d-flex gap-2">
                    <button class="btn btn-outline-danger btn-sm flex-fill" type="button" id="btn-incident">
                        <i class="bi bi-exclamation-triangle"></i> <span id="texte-btn-incident">Incident</span>
                    </button>
                    <button class="btn btn-outline-danger btn-sm flex-fill" type="button" id="btn-supprimer">
                        <i class="bi bi-trash"></i> Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal ajout / modification (réutilisé pour les deux) --}}
    <div class="modal fade" id="modalAjout" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalAjoutTitre">
                        <i class="bi bi-plus-circle me-2"></i> Ajouter un écran
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('ecrans.store') }}" id="form-modal-materiel" data-store-url="{{ route('ecrans.store') }}" data-update-url-base="{{ url('/ecrans') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nom / Modèle *</label>
                                <input type="text" name="nom" id="champ-nom" class="form-control" placeholder="ex: LG 27UK850-W" value="{{ old('nom') }}" maxlength="80" pattern="[A-Za-z0-9 ._-]+" title="Lettres, chiffres, espaces, points, tirets et underscores uniquement." required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Marque *</label>
                                <input type="text" name="marque" id="champ-marque" class="form-control" placeholder="ex: LG" value="{{ old('marque') }}" maxlength="50" pattern="[A-Za-z0-9 ._-]+" title="Lettres, chiffres, espaces, points, tirets et underscores uniquement." required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">N° Série *</label>
                                <input type="text" name="numero_serie" id="champ-numero-serie" class="form-control champ-identifiant" placeholder="ex: LG2022-27UK-001" value="{{ old('numero_serie') }}" maxlength="100" pattern="[A-Za-z0-9._-]+" title="Lettres, chiffres, points, tirets et underscores uniquement." required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Taille *</label>
                                <select name="taille" id="champ-taille" class="form-select champ-select-autre" data-autre-target="bloc-taille-autre" required>
                                    <option value="">Choisir...</option>
                                    <option value="19" {{ old('taille') == '19' ? 'selected' : '' }}>19 pouces</option>
                                    <option value="22" {{ old('taille') == '22' ? 'selected' : '' }}>22 pouces</option>
                                    <option value="24" {{ old('taille') == '24' ? 'selected' : '' }}>24 pouces</option>
                                    <option value="27" {{ old('taille') == '27' ? 'selected' : '' }}>27 pouces</option>
                                    <option value="32" {{ old('taille') == '32' ? 'selected' : '' }}>32 pouces</option>
                                    <option value="autre" {{ old('taille') == 'autre' ? 'selected' : '' }}>Autre</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-none" id="bloc-taille-autre">
                                <label class="form-label fw-semibold">Préciser la taille *</label>
                                <input type="text" name="taille_autre" id="champ-taille-autre" class="form-control" placeholder="ex: 34" value="{{ old('taille_autre') }}" maxlength="5" pattern="[0-9]{2}([.,][0-9])?" title="Indiquez seulement le nombre, ex : 34">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Résolution *</label>
                                <select name="resolution" id="champ-resolution" class="form-select champ-select-autre" data-autre-target="bloc-resolution-autre" required>
                                    <option value="">Choisir...</option>
                                    <option value="1366x768" {{ old('resolution') == '1366x768' ? 'selected' : '' }}>1366x768</option>
                                    <option value="1920x1080" {{ old('resolution') == '1920x1080' ? 'selected' : '' }}>1920x1080</option>
                                    <option value="2560x1440" {{ old('resolution') == '2560x1440' ? 'selected' : '' }}>2560x1440</option>
                                    <option value="3840x2160" {{ old('resolution') == '3840x2160' ? 'selected' : '' }}>3840x2160</option>
                                    <option value="autre" {{ old('resolution') == 'autre' ? 'selected' : '' }}>Autre</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-none" id="bloc-resolution-autre">
                                <label class="form-label fw-semibold">Préciser la résolution *</label>
                                <input type="text" name="resolution_autre" id="champ-resolution-autre" class="form-control" placeholder="ex: 3440x1440" value="{{ old('resolution_autre') }}" maxlength="20" pattern="[0-9]{3,4}x[0-9]{3,4}" title="Format attendu : 3440x1440">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Dalle</label>
                                <select name="dalle" id="champ-dalle" class="form-select champ-select-autre" data-autre-target="bloc-dalle-autre">
                                    <option value="">Choisir...</option>
                                    <option value="IPS" {{ old('dalle') == 'IPS' ? 'selected' : '' }}>IPS</option>
                                    <option value="TN" {{ old('dalle') == 'TN' ? 'selected' : '' }}>TN</option>
                                    <option value="VA" {{ old('dalle') == 'VA' ? 'selected' : '' }}>VA</option>
                                    <option value="OLED" {{ old('dalle') == 'OLED' ? 'selected' : '' }}>OLED</option>
                                    <option value="autre" {{ old('dalle') == 'autre' ? 'selected' : '' }}>Autre</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-none" id="bloc-dalle-autre">
                                <label class="form-label fw-semibold">Préciser la dalle *</label>
                                <input type="text" name="dalle_autre" id="champ-dalle-autre" class="form-control" placeholder="ex: Mini LED" value="{{ old('dalle_autre') }}" maxlength="30" pattern="[A-Za-z0-9 ._-]+" title="Lettres, chiffres, espaces, points, tirets et underscores uniquement.">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Taux de rafraîchissement</label>
                                <input type="text" name="taux_rafraichissement" id="champ-refresh" class="form-control" placeholder="ex: 60" value="{{ old('taux_rafraichissement') }}" maxlength="3" pattern="[0-9]{2,3}" title="Indiquez seulement le nombre, ex : 60">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">État</label>
                                <select name="etat" id="etat" class="form-select">
                                    <option value="disponible" {{ old('etat') == 'disponible' ? 'selected' : '' }}>Disponible</option>
                                    <option value="affecte" {{ old('etat') == 'affecte' ? 'selected' : '' }}>Affecté</option>
                                    <option value="emprunte" {{ old('etat') == 'emprunte' ? 'selected' : '' }}>Emprunté</option>
                                    <option value="en_panne" {{ old('etat') == 'en_panne' ? 'selected' : '' }}>En panne</option>
                                </select>
                                <small class="text-muted d-none" id="etat-aide-edition">
                                    Le changement d'état se fait via Affectation / Emprunt.
                                </small>
                            </div>
                            <div class="col-md-6" style="position:relative">
                                <label class="form-label fw-semibold">À qui</label>
                                <input type="text" id="champ-a-qui" name="a_qui_nom"
                                       class="form-control" placeholder="Choisir un état d'abord"
                                       autocomplete="off" disabled
                                       data-personnels-url="{{ route('search.personnels') }}"
                                       data-etudiants-url="{{ route('search.etudiants') }}">
                                <input type="hidden" name="a_qui_id" id="a-qui-id">
                                <small class="text-muted" id="a-qui-aide">Sélectionnez d'abord un état</small>
                                <div id="suggestions" class="suggestions-container d-none"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Emplacement</label>
                                <input type="text" name="emplacement" id="champ-emplacement" class="form-control" placeholder="ex: Salle 101" value="{{ old('emplacement') }}" maxlength="100" pattern="[A-Za-z0-9 ._()/-]+" title="Lettres, chiffres, espaces, points, tirets, underscores et parentheses uniquement.">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Date d'achat</label>
                                <input type="date" name="date_achat" id="champ-date-achat" class="form-control" value="{{ old('date_achat') }}">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" id="btn-submit-modal">
                            <i class="bi bi-save me-1"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal confirmation suppression --}}
    <div class="modal fade" id="modalConfirmSuppression" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>Confirmer la suppression
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Voulez-vous vraiment supprimer <strong id="texte-nom-suppression">cet écran</strong> ? Cette action est irréversible.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="btn-confirmer-suppression">
                        <i class="bi bi-trash me-1"></i> Supprimer définitivement
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Formulaire caché utilisé pour envoyer la suppression --}}
    <form method="POST" id="form-suppression" data-delete-url-base="{{ url('/ecrans') }}" class="d-none">
        @csrf
        @method('DELETE')
    </form>

    {{-- Formulaire caché utilisé pour signaler une panne / marquer comme réparé --}}
    <form method="POST" id="form-panne" data-panne-url-base="{{ url('/ecrans') }}" class="d-none">
        @csrf
        @method('PATCH')
    </form>

@endsection

@section('scripts')
    <script src="{{ asset('js/materiel.js') }}"></script>
    <script src="{{ asset('js/materiel-crud.js') }}"></script>
@endsection
