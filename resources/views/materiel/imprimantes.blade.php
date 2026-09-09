{{-- resources/views/materiel/imprimantes.blade.php --}}

@extends('layouts.app')

@section('title', 'Imprimantes')

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
        <small class="text-muted">Dashboard &gt; Matériel &gt; Imprimantes</small>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <h2 class="fw-bold mb-0">🖨️ Imprimantes</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary">
                    <i class="bi bi-download"></i> Exporter
                </button>
                <button class="btn btn-primary" type="button"
                        data-bs-toggle="modal" data-bs-target="#modalAjout" onclick="reinitialiserModalMateriel()">
                    <i class="bi bi-plus"></i> Ajouter une Imprimante
                </button>
            </div>
        </div>
    </div>

    {{-- Les 3 cartes statistiques --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-4">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0f4ff">
                    <i class="bi bi-printer" style="color:#3b82f6"></i>
                </div>
                <div>
                    <div class="stat-label">Total Imprimantes</div>
                    <div class="stat-number">{{ $total }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-4">
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
        <div class="col-12 col-md-6 col-xl-4">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff0f0">
                    <i class="bi bi-exclamation-triangle" style="color:#ef4444"></i>
                </div>
                <div>
                    <div class="stat-label">En panne</div>
                    <div class="stat-number">{{ $enPanne }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Barre de recherche + Filtres --}}
    <div class="bg-white rounded-3 p-3 mb-4 shadow-sm">
        <form method="GET" action="{{ route('imprimantes.index') }}">
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
                    <a href="{{ route('imprimantes.index') }}"
                       class="btn btn-filtre {{ !request('etat') ? 'active-filtre' : '' }}">Tous</a>
                    <a href="{{ route('imprimantes.index', ['etat' => 'disponible']) }}"
                       class="btn btn-filtre {{ request('etat') == 'disponible' ? 'active-filtre' : '' }}">
                       <span class="point-vert"></span> Disponible</a>
                    <a href="{{ route('imprimantes.index', ['etat' => 'en_panne']) }}"
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
                                <th>#</th>
                                <th>NOM / MODÈLE</th>
                                <th>MARQUE</th>
                                <th>N° SÉRIE</th>
                                <th>TYPE</th>
                                <th>CONNEXION</th>
                                <th>ÉTAT</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($imprimantes as $imprimante)
                            <tr>
                                <td class="text-muted small">{{ $imprimante->reference }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="icone-appareil"><i class="bi bi-printer"></i></div>
                                        <div>
                                            <div class="fw-semibold">{{ $imprimante->nom }}</div>
                                            <div class="text-muted small">
                                                Acheté le {{ $imprimante->date_achat ? $imprimante->date_achat->format('d/m/Y') : '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $imprimante->marque }}</td>
                                <td><span class="badge-serie">{{ $imprimante->numero_serie }}</span></td>
                                <td>{{ $imprimante->type_impression }}</td>
                                <td><span class="badge-os"><i class="bi bi-wifi"></i> {{ $imprimante->connexion }}</span></td>
                                <td>
                                    <span class="badge-etat {{ $imprimante->etat }}">
                                        ● {{ ucfirst(str_replace('_', ' ', $imprimante->etat)) }}
                                    </span>
                                </td>
                                <td>
                                    <button type="button"
                                            class="btn btn-sm btn-action"
                                            onclick="ouvrirDetail(this)"
                                            data-type-materiel="imprimante"
                                            data-id="{{ $imprimante->id }}"
                                            data-reference="{{ $imprimante->reference }}"
                                            data-nom="{{ $imprimante->nom }}"
                                            data-marque="{{ $imprimante->marque }}"
                                            data-numero-serie="{{ $imprimante->numero_serie }}"
                                            data-type-impression="{{ $imprimante->type_impression }}"
                                            data-couleur="{{ $imprimante->couleur ? '1' : '0' }}"
                                            data-couleur-label="{{ $imprimante->couleur ? 'Couleur' : 'N&B' }}"
                                            data-connexion="{{ $imprimante->connexion }}"
                                            data-vitesse="{{ $imprimante->vitesse ?? '-' }}"
                                            data-etat="{{ $imprimante->etat }}"
                                            data-etat-label="{{ ucfirst(str_replace('_', ' ', $imprimante->etat)) }}"
                                            data-emplacement="{{ $imprimante->emplacement ?? '-' }}"
                                            data-date-achat="{{ $imprimante->date_achat ? $imprimante->date_achat->format('d/m/Y') : '-' }}"
                                            data-date-achat-iso="{{ $imprimante->date_achat ? $imprimante->date_achat->format('Y-m-d') : '' }}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Aucune imprimante trouvée
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <small class="text-muted">
                        Affichage {{ $imprimantes->count() }} sur {{ $total }} Imprimantes
                    </small>
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
                        <small class="text-muted" id="detail-reference">-</small>
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
                    <i class="bi bi-printer"></i>
                </div>

                <div class="section-detail">
                    <div class="section-detail-titre">
                        <i class="bi bi-printer"></i> Spécifications
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-6">
                            <div class="spec-box">
                                <div class="spec-label">TYPE</div>
                                <div class="spec-value" id="detail-type">-</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="spec-box">
                                <div class="spec-label">COULEUR</div>
                                <div class="spec-value" id="detail-couleur">-</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="spec-box">
                                <div class="spec-label">CONNEXION</div>
                                <div class="spec-value" id="detail-connexion">-</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="spec-box">
                                <div class="spec-label">VITESSE</div>
                                <div class="spec-value" id="detail-vitesse">-</div>
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
                    </div>
                </div>
            </div>

            <div class="panneau-footer">
                <div class="d-flex gap-2 mb-2">
                    <button class="btn btn-primary btn-sm flex-fill" type="button" id="btn-modifier">
                        <i class="bi bi-pencil"></i> Modifier
                    </button>
                </div>
                <div class="d-flex gap-2">
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
                        <i class="bi bi-plus-circle me-2"></i> Ajouter une imprimante
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('imprimantes.store') }}" id="form-modal-materiel" data-store-url="{{ route('imprimantes.store') }}" data-update-url-base="{{ url('/imprimantes') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Référence *</label>
                                <input type="text" name="reference" id="champ-reference" class="form-control champ-identifiant" placeholder="ex: IM-001" value="{{ old('reference') }}" maxlength="50" pattern="[A-Za-z0-9_-]+" title="Lettres, chiffres, tiret et underscore uniquement." required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nom / Modèle *</label>
                                <input type="text" name="nom" id="champ-nom" class="form-control" placeholder="ex: Canon MF445dw" value="{{ old('nom') }}" maxlength="80" pattern="[A-Za-z0-9 ._+\-]+" title="Lettres, chiffres, espaces, point, tiret, underscore et plus uniquement." required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Marque *</label>
                                <input type="text" name="marque" id="champ-marque" class="form-control" placeholder="ex: Canon" value="{{ old('marque') }}" maxlength="50" pattern="[A-Za-z0-9 ._+\-]+" title="Lettres, chiffres, espaces, point, tiret, underscore et plus uniquement." required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">N° Série *</label>
                                <input type="text" name="numero_serie" id="champ-numero-serie" class="form-control champ-identifiant" placeholder="ex: CN2022-MF445-001" value="{{ old('numero_serie') }}" maxlength="100" pattern="[A-Za-z0-9 ._-]+" title="Lettres, chiffres, espaces, point, tiret et underscore uniquement." required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Type d'impression *</label>
                                <select name="type_impression" id="champ-type-impression" class="form-select champ-select-autre" data-autre-target="bloc-type-impression-autre" required>
                                    <option value="">Choisir...</option>
                                    <option value="Laser" {{ old('type_impression') == 'Laser' ? 'selected' : '' }}>Laser</option>
                                    <option value="Jet d'encre" {{ old('type_impression') == "Jet d'encre" ? 'selected' : '' }}>Jet d'encre</option>
                                    <option value="Thermique" {{ old('type_impression') == 'Thermique' ? 'selected' : '' }}>Thermique</option>
                                    <option value="autre" {{ old('type_impression') == 'autre' ? 'selected' : '' }}>Autre</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-none" id="bloc-type-impression-autre">
                                <label class="form-label fw-semibold">Autre type *</label>
                                <input type="text" name="type_impression_autre" class="form-control" placeholder="ex: Sublimation" value="{{ old('type_impression_autre') }}" maxlength="30" pattern="[A-Za-z0-9 ._+()\/-]+" title="Lettres, chiffres et signes techniques simples uniquement.">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Couleur</label>
                                <select name="couleur" id="champ-couleur" class="form-select">
                                    <option value="0" {{ old('couleur') == '0' ? 'selected' : '' }}>Noir & Blanc</option>
                                    <option value="1" {{ old('couleur') == '1' ? 'selected' : '' }}>Couleur</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Connexion *</label>
                                <select name="connexion" id="champ-connexion" class="form-select champ-select-autre" data-autre-target="bloc-connexion-autre" required>
                                    <option value="">Choisir...</option>
                                    <option value="Wi-Fi" {{ old('connexion') == 'Wi-Fi' ? 'selected' : '' }}>Wi-Fi</option>
                                    <option value="USB" {{ old('connexion') == 'USB' ? 'selected' : '' }}>USB</option>
                                    <option value="Ethernet" {{ old('connexion') == 'Ethernet' ? 'selected' : '' }}>Ethernet</option>
                                    <option value="Bluetooth" {{ old('connexion') == 'Bluetooth' ? 'selected' : '' }}>Bluetooth</option>
                                    <option value="autre" {{ old('connexion') == 'autre' ? 'selected' : '' }}>Autre</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-none" id="bloc-connexion-autre">
                                <label class="form-label fw-semibold">Autre connexion *</label>
                                <input type="text" name="connexion_autre" class="form-control" placeholder="ex: Wi-Fi Direct" value="{{ old('connexion_autre') }}" maxlength="30" pattern="[A-Za-z0-9 ._+()\/-]+" title="Lettres, chiffres et signes techniques simples uniquement.">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Vitesse</label>
                                <input type="text" name="vitesse" id="champ-vitesse" class="form-control" placeholder="ex: 38 ppm" value="{{ old('vitesse') }}" maxlength="10" pattern="[0-9]{1,3}\s?[Pp][Pp][Mm]" title="Format attendu : nombre + ppm, exemple 38 ppm.">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">État</label>
                                <select name="etat" id="champ-etat" class="form-select">
                                    <option value="disponible" {{ old('etat') == 'disponible' ? 'selected' : '' }}>Disponible</option>
                                    <option value="en_panne" {{ old('etat') == 'en_panne' ? 'selected' : '' }}>En panne</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Emplacement</label>
                                <input type="text" name="emplacement" id="champ-emplacement" class="form-control" placeholder="ex: Salle 101" value="{{ old('emplacement') }}" maxlength="100" pattern="[A-Za-z0-9 ._+()\/-]+" title="Lettres, chiffres et signes simples uniquement.">
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
                    <p class="mb-0">Voulez-vous vraiment supprimer <strong id="texte-nom-suppression">cette imprimante</strong> ? Cette action est irréversible.</p>
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
    <form method="POST" id="form-suppression" data-delete-url-base="{{ url('/imprimantes') }}" class="d-none">
        @csrf
        @method('DELETE')
    </form>

    {{-- Formulaire caché utilisé pour signaler une panne / marquer comme réparé --}}
    <form method="POST" id="form-panne" data-panne-url-base="{{ url('/imprimantes') }}" class="d-none">
        @csrf
        @method('PATCH')
    </form>

@endsection

@section('scripts')
    <script src="{{ asset('js/materiel.js') }}"></script>
    <script src="{{ asset('js/materiel-crud.js') }}"></script>
@endsection
