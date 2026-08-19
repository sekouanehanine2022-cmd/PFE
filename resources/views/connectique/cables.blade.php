{{-- resources/views/connectique/cables.blade.php --}}

@extends('layouts.app')

@section('title', 'Câbles')

@section('styles')
    <link href="{{ asset('css/materiel.css') }}" rel="stylesheet">
    <link href="{{ asset('css/cables.css') }}" rel="stylesheet">
@endsection

@section('content')

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
                        data-bs-toggle="modal" data-bs-target="#modalAjout">
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
                    <a href="{{ route('cables.index') }}"
                       class="btn btn-filtre {{ !request('search') ? 'active-filtre' : '' }}">
                       Tous les types</a>
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
                                    @if($stockOk)
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
                <button class="btn btn-success btn-sm flex-fill" type="button">
                    <i class="bi bi-plus"></i> Ajouter stock
                </button>
                <button class="btn btn-outline-secondary btn-sm flex-fill" type="button">
                    <i class="bi bi-pencil"></i> Modifier
                </button>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-danger btn-sm flex-fill" type="button">
                    <i class="bi bi-dash"></i> Retirer stock
                </button>
                <button class="btn btn-outline-danger btn-sm flex-fill" type="button">
                    <i class="bi bi-trash"></i> Supprimer
                </button>
            </div>
        </div>

    </div>
</div>
    {{-- Modal ajout --}}
    <div class="modal fade" id="modalAjout" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle me-2"></i> Nouveau type de câble
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('cables.store') }}">
                    @csrf
                    <div class="modal-body">
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger">{{ $errors->first() }}</div>
                        @endif
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Référence *</label>
                                <input type="text" name="reference" class="form-control"
                                       placeholder="ex: CB-001" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Type de câble *</label>
                                <input type="text" name="type_cable" class="form-control"
                                       placeholder="ex: HDMI" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Longueur *</label>
                                <input type="text" name="longueur" class="form-control"
                                       placeholder="ex: 1.5m / 3m" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Quantité *</label>
                                <input type="number" name="quantite" class="form-control"
                                       placeholder="ex: 42" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Seuil d'alerte</label>
                                <input type="number" name="seuil_alerte" class="form-control"
                                       placeholder="ex: 5" min="0" value="5">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Couleur</label>
                                <input type="text" name="couleur" class="form-control"
                                       placeholder="ex: Noir">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Emplacement</label>
                                <input type="text" name="emplacement" class="form-control"
                                       placeholder="ex: Stock Salle 101">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script src="{{ asset('js/materiel.js') }}"></script>
@endsection