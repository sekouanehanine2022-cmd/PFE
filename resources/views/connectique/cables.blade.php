{{-- resources/views/connectique/cables.blade.php --}}

@extends('layouts.app')

@section('title', 'Câbles')

@section('styles')
    <link href="{{ asset('css/materiel.css') }}" rel="stylesheet">
    <link href="{{ asset('css/cables.css') }}" rel="stylesheet">
@endsection

@section('content')

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    {{-- Fil d'ariane + Titre --}}
    <div class="mb-4">
        <small class="text-muted">Dashboard > Connectique > Câbles</small>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <h2 class="fw-bold mb-0">🔌 Câbles</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary">
                    <i class="bi bi-download"></i> Exporter
                </button>
                <button class="btn btn-primary" type="button"
                        data-bs-toggle="modal" data-bs-target="#modalAjout" onclick="reinitialiserModalMateriel()">
                    <i class="bi bi-plus"></i> Nouveau type de câble
                </button>
            </div>
        </div>
    </div>

    {{-- Les 4 cartes statistiques --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0f4ff">
                    <i class="bi bi-plug" style="color:#3b82f6"></i>
                </div>
                <div>
                    <div class="stat-label">Total câbles (stock)</div>
                    <div class="stat-number">{{ $totalStock }}</div>
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
                    <i class="bi bi-arrow-left-right" style="color:#f97316"></i>
                </div>
                <div>
                    <div class="stat-label">En utilisation</div>
                    <div class="stat-number">{{ $enUtilisation }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff0f0">
                    <i class="bi bi-exclamation-triangle" style="color:#ef4444"></i>
                </div>
                <div>
                    <div class="stat-label">Stock bas (< seuil)</div>
                    <div class="stat-number">{{ $stockBas }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Barre de recherche + Filtres --}}
    <div class="bg-white rounded-3 p-3 mb-4 shadow-sm">
        <form method="GET" action="{{ route('cables.index') }}">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="input-group" style="max-width: 300px;">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" name="search"
                           class="form-control border-start-0"
                           placeholder="Rechercher un type de câble..."
                           value="{{ request('search') }}">
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" name="statut" value="" class="btn btn-filtre {{ !request('statut') ? 'active-filtre' : '' }}">Tous les types</button>
                    <button type="submit" name="statut" value="stock_ok" class="btn btn-filtre {{ request('statut') == 'stock_ok' ? 'active-filtre' : '' }}"><span class="point-vert"></span> Stock OK</button>
                    <button type="submit" name="statut" value="stock_bas" class="btn btn-filtre {{ request('statut') == 'stock_bas' ? 'active-filtre' : '' }}"><span class="point-orange"></span> Stock bas</button>
                    <button type="submit" name="statut" value="rupture" class="btn btn-filtre {{ request('statut') == 'rupture' ? 'active-filtre' : '' }}"><span class="point-rouge"></span> En rupture</button>
                </div>
            </div>
        </form>
    </div>

    {{-- Tableau --}}
    <div class="row g-3">
        <div class="col-12">
            <div class="bg-white rounded-3 shadow-sm tableau-card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>TYPE DE CÂBLE</th>
                                <th>STOCK TOTAL</th>
                                <th>DISPONIBLES</th>
                                <th>EN UTILISATION</th>
                                <th>DISPONIBILITÉ</th>
                                <th>STOCK</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cables as $cable)
                            @php
                                $enUtil  = $cable->quantite - $cable->quantite_disponible;
                                $pct     = $cable->quantite > 0
                                           ? round(($cable->quantite_disponible / $cable->quantite) * 100)
                                           : 0;
                                $stockOk = $cable->quantite_disponible > $cable->seuil_alerte;
                                $couleur = $pct >= 50 ? '#22c55e' : ($pct >= 20 ? '#f97316' : '#ef4444');
                                $dispoCl = $cable->quantite_disponible > $cable->seuil_alerte
                                           ? 'text-success' : ($cable->quantite_disponible > 0 ? 'text-warning' : 'text-danger');
                            @endphp
                            <tr>
                                <td class="text-muted small">{{ $cable->reference }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="icone-cable" style="background:#f0f4ff">
                                            <i class="bi bi-plug" style="color:#3b82f6"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $cable->type_cable }}</div>
                                            <div class="text-muted small">{{ $cable->longueur }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-bold">{{ $cable->quantite }}</td>
                                <td class="fw-bold {{ $dispoCl }}">{{ $cable->quantite_disponible }}</td>
                                <td>{{ $enUtil }}</td>
                                <td>
                                    <div class="dispo-bar">
                                        <div class="dispo-progress"
                                             style="width: {{ $pct }}%; background: {{ $couleur }}">
                                        </div>
                                    </div>
                                    <small class="text-muted">{{ $pct }}%</small>
                                </td>
                                <td>
                                    @if($cable->quantite_disponible == 0)
                                        <span class="badge-stock bas">● Rupture</span>
                                    @elseif($stockOk)
                                        <span class="badge-stock ok">● Stock OK</span>
                                    @else
                                        <span class="badge-stock bas">● Stock bas</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <form method="POST"
                                              action="{{ route('cables.incrementer', $cable) }}">
                                            @csrf
                                            <button class="btn btn-stock-plus" type="submit">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </form>
                                        <form method="POST"
                                              action="{{ route('cables.decrementer', $cable) }}">
                                            @csrf
                                            <button class="btn btn-stock-minus" type="submit">
                                                <i class="bi bi-dash"></i>
                                            </button>
                                        </form>
                                        <button class="btn btn-sm btn-action" type="button"
        onclick="ouvrirDetail(this)"
        data-type-materiel="cable"
        data-id="{{ $cable->id }}"
        data-reference="{{ $cable->reference }}"
        data-nom="{{ $cable->type_cable }}"
        data-longueur="{{ $cable->longueur }}"
        data-quantite="{{ $cable->quantite }}"
        data-disponible="{{ $cable->quantite_disponible }}"
        data-utilisation="{{ $enUtil }}"
        data-seuil="{{ $cable->seuil_alerte }}"
        data-emplacement="{{ $cable->emplacement ?? '-' }}"
        data-pct="{{ $pct }}">
    <i class="bi bi-eye"></i>
</button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Aucun câble trouvé
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <small class="text-muted">
                        Affichage {{ $cables->count() }} types de câbles
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
                <span class="badge-stock ok" id="detail-stock-badge">● Stock OK</span>
                <div class="d-flex align-items-center gap-2">
                    <small class="text-muted" id="detail-reference">-</small>
                    <button class="btn btn-sm btn-action" type="button" onclick="fermerDetail()">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
            <h6 class="fw-bold mb-0" id="detail-nom">-</h6>
            <small class="text-muted" id="detail-longueur-header">-</small>
        </div>

        <div class="panneau-body">
            <div class="icone-detail my-3">
                <i class="bi bi-plug"></i>
            </div>

            {{-- Stock --}}
            <div class="section-detail">
                <div class="section-detail-titre">
                    <i class="bi bi-box"></i> Stock
                </div>
                <div class="row g-2 mt-1">
                    <div class="col-6">
                        <div class="spec-box">
                            <div class="spec-label">STOCK TOTAL</div>
                            <div class="spec-value" id="detail-quantite">-</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="spec-box">
                            <div class="spec-label">DISPONIBLES</div>
                            <div class="spec-value text-success" id="detail-disponible">-</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="spec-box">
                            <div class="spec-label">EN UTILISATION</div>
                            <div class="spec-value" id="detail-utilisation">-</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="spec-box">
                            <div class="spec-label">SEUIL D'ALERTE</div>
                            <div class="spec-value" id="detail-seuil">-</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Informations --}}
            <div class="section-detail mt-3">
                <div class="section-detail-titre">
                    <i class="bi bi-info-circle"></i> Informations
                </div>
                <div class="mt-2">
                    <div class="info-ligne">
                        <i class="bi bi-rulers"></i>
                        <span class="info-label">Longueur</span>
                        <span class="info-value" id="detail-longueur-info">-</span>
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
                <button class="btn btn-success btn-sm flex-fill" type="button" id="btn-ajouter-stock">
                    <i class="bi bi-plus"></i> Ajouter stock
                </button>
                <button class="btn btn-outline-secondary btn-sm flex-fill" type="button" id="btn-modifier">
                    <i class="bi bi-pencil"></i> Modifier
                </button>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-danger btn-sm flex-fill" type="button" id="btn-retirer-stock">
                    <i class="bi bi-dash"></i> Retirer stock
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
                        <i class="bi bi-plus-circle me-2"></i> Nouveau type de câble
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('cables.store') }}" id="form-modal-materiel" data-store-url="{{ route('cables.store') }}" data-update-url-base="{{ url('/cables') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Référence *</label>
                                <input type="text" name="reference" id="champ-reference" class="form-control champ-identifiant"
                                       placeholder="ex: CB-001" maxlength="50" pattern="[A-Za-z0-9_-]+" title="Lettres, chiffres, tiret et underscore uniquement." required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Type de câble *</label>
                                <select name="type_cable" id="champ-nom" class="form-select champ-select-autre" data-autre-target="bloc-type-cable-autre" required>
                                    <option value="">Choisir...</option>
                                    <option value="HDMI" {{ old('type_cable') == 'HDMI' ? 'selected' : '' }}>HDMI</option>
                                    <option value="VGA" {{ old('type_cable') == 'VGA' ? 'selected' : '' }}>VGA</option>
                                    <option value="DisplayPort" {{ old('type_cable') == 'DisplayPort' ? 'selected' : '' }}>DisplayPort</option>
                                    <option value="USB-A" {{ old('type_cable') == 'USB-A' ? 'selected' : '' }}>USB-A</option>
                                    <option value="USB-C" {{ old('type_cable') == 'USB-C' ? 'selected' : '' }}>USB-C</option>
                                    <option value="RJ45" {{ old('type_cable') == 'RJ45' ? 'selected' : '' }}>RJ45</option>
                                    <option value="Alimentation" {{ old('type_cable') == 'Alimentation' ? 'selected' : '' }}>Alimentation</option>
                                    <option value="autre" {{ old('type_cable') == 'autre' ? 'selected' : '' }}>Autre</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-none" id="bloc-type-cable-autre">
                                <label class="form-label fw-semibold">Autre type *</label>
                                <input type="text" name="type_cable_autre" class="form-control" placeholder="ex: Jack 3.5mm" value="{{ old('type_cable_autre') }}" maxlength="30" pattern="[A-Za-z0-9 ._+()\/-]+" title="Lettres, chiffres et signes techniques simples uniquement.">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Longueur *</label>
                                <input type="text" name="longueur" id="champ-longueur" class="form-control"
                                       placeholder="ex: 1.5m" maxlength="10" pattern="[0-9]+([.,][0-9]{1,2})?\s?(m|cm)" title="Format attendu : nombre + m ou cm, exemple 1.5m ou 50cm." required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Quantité *</label>
                                <input type="number" name="quantite" id="champ-quantite" class="form-control champ-identifiant"
                                       placeholder="ex: 42" min="0" max="9999" required>
                                <small class="text-muted d-none" id="quantite-aide-edition">
                                    La quantité se gère via les boutons +/- ou "Ajouter/Retirer stock".
                                </small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Seuil d'alerte</label>
                                <input type="number" name="seuil_alerte" id="champ-seuil" class="form-control"
                                       placeholder="ex: 5" min="0" max="9999" value="5">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Couleur</label>
                                <input type="text" name="couleur" id="champ-couleur" class="form-control"
                                       placeholder="ex: Noir" maxlength="30" pattern="[A-Za-z0-9 ._+()\/-]+" title="Lettres, chiffres et signes simples uniquement.">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Emplacement</label>
                                <input type="text" name="emplacement" id="champ-emplacement" class="form-control"
                                       placeholder="ex: Stock Salle 101" maxlength="100" pattern="[A-Za-z0-9 ._+()\/-]+" title="Lettres, chiffres et signes simples uniquement.">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Annuler</button>
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
                    <p class="mb-0">Voulez-vous vraiment supprimer <strong id="texte-nom-suppression">ce câble</strong> ? Cette action est irréversible.</p>
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
    <form method="POST" id="form-suppression" data-delete-url-base="{{ url('/cables') }}" class="d-none">
        @csrf
        @method('DELETE')
    </form>

    {{-- Formulaires cachés pour Ajouter/Retirer du stock (vrai mouvement de stock) --}}
    <form method="POST" id="form-ajouter-stock" data-url-base="{{ url('/cables') }}" class="d-none">
        @csrf
    </form>
    <form method="POST" id="form-retirer-stock" data-url-base="{{ url('/cables') }}" class="d-none">
        @csrf
    </form>

    {{-- Modal quantité pour Ajouter/Retirer stock --}}
    <div class="modal fade" id="modalQuantiteStock" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalQuantiteTitre">Ajouter au stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">Quantité</label>
                    <input type="number" id="champ-quantite-stock" class="form-control" min="1" max="9999" value="1">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="btn-confirmer-quantite-stock">Confirmer</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script src="{{ asset('js/materiel.js') }}"></script>
    <script src="{{ asset('js/materiel-crud.js') }}"></script>
    <script src="{{ asset('js/cables-stock.js') }}"></script>
@endsection
