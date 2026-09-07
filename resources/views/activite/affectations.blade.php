{{-- resources/views/activite/affectations.blade.php --}}

@extends('layouts.app')

@section('title', 'Affectations')

@section('styles')
    <link href="{{ asset('css/materiel.css') }}" rel="stylesheet">
    <link href="{{ asset('css/affectations.css') }}" rel="stylesheet">
@endsection

@section('content')

    {{-- Fil d'ariane + Titre --}}
    <div class="mb-4">
        <small class="text-muted">Dashboard &gt; Activité &gt; Affectations</small>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <h2 class="fw-bold mb-0">🔗 Affectations</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary">
                    <i class="bi bi-download"></i> Exporter
                </button>
                <button class="btn btn-primary" type="button" id="btn-nouvelle-affectation" data-bs-toggle="modal" data-bs-target="#modalAjoutAffectation">
                    <i class="bi bi-plus"></i> Nouvelle affectation
                </button>
            </div>
        </div>
    </div>

    {{-- Les cartes statistiques --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-4">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0f4ff">
                    <i class="bi bi-link-45deg" style="color:#3b82f6"></i>
                </div>
                <div>
                    <div class="stat-label">Total affectations</div>
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
                    <div class="stat-label">Actives</div>
                    <div class="stat-number">{{ $actives }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff0f0">
                    <i class="bi bi-x-circle" style="color:#ef4444"></i>
                </div>
                <div>
                    <div class="stat-label">Clôturées</div>
                    <div class="stat-number">{{ $cloturees }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Barre de recherche + Filtres --}}
    <div class="bg-white rounded-3 p-3 mb-4 shadow-sm">
        <form method="GET" action="{{ route('affectations.index') }}">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="input-group" style="max-width: 350px;">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" name="search"
                           class="form-control border-start-0"
                           placeholder="Rechercher un collaborateur..."
                           value="{{ request('search') }}">
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('affectations.index') }}"
                       class="btn btn-filtre {{ !request('statut') ? 'active-filtre' : '' }}">Tous</a>
                    <a href="{{ route('affectations.index', ['statut' => 'active']) }}"
                       class="btn btn-filtre {{ request('statut') == 'active' ? 'active-filtre' : '' }}">
                       <span class="point-vert"></span> Active</a>
                    <a href="{{ route('affectations.index', ['statut' => 'cloturee']) }}"
                       class="btn btn-filtre {{ request('statut') == 'cloturee' ? 'active-filtre' : '' }}">
                       <span class="point-rouge"></span> Clôturée</a>
                </div>
            </div>
        </form>
    </div>

    {{-- Tableau --}}
    <div class="row g-3 align-items-stretch inventaire-layout">
        <div class="col-12" id="colonne-tableau">
            <div class="bg-white rounded-3 shadow-sm tableau-card" id="tableau-pcs-card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>UTILISATEUR</th>
                                <th>SERVICE</th>
                                <th>MATÉRIEL AFFECTÉ</th>
                                <th>RÉF.</th>
                                <th>DEPUIS</th>
                                <th>FIN</th>
                                <th>STATUT</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($affectations as $affectation)
                            @php
                                $materiel     = $affectation->materiel;
                                $typeMateriel = class_basename($affectation->materiel_type);
                                $nom          = $affectation->personnel->user->name ?? '-';
                                $initiales    = collect(explode(' ', $nom))->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->join('');
                                $couleurs     = ['#3b82f6','#22c55e','#f97316','#7c3aed','#ef4444'];
                                $couleur      = $couleurs[crc32($nom) % count($couleurs)];
                                $typeMaterielSlug = match ($typeMateriel) {
                                    'PcPortable' => 'pc-portable',
                                    'MiniPc' => 'mini-pc',
                                    'Ecran' => 'ecran',
                                    'Imprimante' => 'imprimante',
                                    default => strtolower($materiel->sous_type ?? 'peripherique'),
                                };
                            @endphp
                            <tr>
                                <td class="text-muted small">{{ $affectation->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar" style="background:{{ $couleur }}">{{ $initiales }}</div>
                                        <div>
                                            <div class="fw-semibold">{{ $nom }}</div>
                                            <div class="text-muted small">{{ $affectation->personnel->poste ?? '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-service">
                                        {{ $affectation->personnel->service ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-laptop text-muted"></i>
                                        <span>{{ $materiel->nom ?? '-' }}</span>
                                    </div>
                                </td>
                                <td><span class="badge-serie">{{ $materiel->reference ?? '-' }}</span></td>
                                <td>{{ $affectation->date_debut ? \Carbon\Carbon::parse($affectation->date_debut)->format('d/m/Y') : '-' }}</td>
                                <td class="text-muted">{{ $affectation->date_fin ? \Carbon\Carbon::parse($affectation->date_fin)->format('d/m/Y') : '—' }}</td>
                                <td>
                                    @if($affectation->statut === 'active')
                                        <span class="badge-etat disponible">● Active</span>
                                    @else
                                        <span class="badge-etat hors_service">● Clôturée</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button"
                                            class="btn btn-sm btn-action"
                                            onclick="ouvrirDetailAffectation(this)"
                                            aria-label="Voir les details de l'affectation"
                                            data-id="{{ $affectation->id }}"
                                            data-utilisateur="{{ $nom }}"
                                            data-personnel-id="{{ $affectation->personnel_id }}"
                                            data-poste="{{ $affectation->personnel->poste ?? '-' }}"
                                            data-service="{{ $affectation->personnel->service ?? '-' }}"
                                            data-materiel-type="{{ $typeMaterielSlug }}"
                                            data-materiel-type-label="{{ $typeMateriel }}"
                                            data-materiel-id="{{ $affectation->materiel_id }}"
                                            data-materiel-nom="{{ $materiel->nom ?? '-' }}"
                                            data-reference="{{ $materiel->reference ?? '-' }}"
                                            data-date-debut="{{ $affectation->date_debut ? $affectation->date_debut->format('Y-m-d') : '' }}"
                                            data-date-debut-label="{{ $affectation->date_debut ? $affectation->date_debut->format('d/m/Y') : '-' }}"
                                            data-date-fin="{{ $affectation->date_fin ? $affectation->date_fin->format('Y-m-d') : '' }}"
                                            data-date-fin-label="{{ $affectation->date_fin ? $affectation->date_fin->format('d/m/Y') : '-' }}"
                                            data-statut="{{ $affectation->statut }}"
                                            data-update-url="{{ route('affectations.update', $affectation) }}"
                                            data-cloturer-url="{{ route('affectations.cloturer', $affectation) }}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    Aucune affectation trouvée
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <small class="text-muted">
                        Affichage {{ $affectations->count() }} sur {{ $total }} affectations
                    </small>
                </div>
            </div>
        </div>
    </div>

    {{-- Popup detail affectation --}}
    <div class="panneau-backdrop d-none" id="panneau-detail-backdrop" onclick="fermerDetailAffectation()"></div>

    <div class="d-none" id="panneau-detail" aria-hidden="true">
        <div class="panneau-container">
            <div class="panneau-header">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge-etat disponible" id="detail-affectation-statut">● Active</span>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted">Affectation #<span id="detail-affectation-id">-</span></small>
                        <button class="btn btn-sm btn-action" type="button" onclick="fermerDetailAffectation()">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
                <h6 class="fw-bold mb-0" id="detail-affectation-utilisateur">-</h6>
                <small class="text-muted">
                    <span id="detail-affectation-poste">-</span> · <span id="detail-affectation-service">-</span>
                </small>
            </div>

            <div class="panneau-body">
                <div class="icone-detail my-3">
                    <i class="bi bi-link-45deg"></i>
                </div>

                <div class="section-detail">
                    <div class="section-detail-titre">
                        <i class="bi bi-person"></i> Collaborateur
                    </div>
                    <div class="mt-2">
                        <div class="info-ligne">
                            <i class="bi bi-person-badge"></i>
                            <span class="info-label">Nom</span>
                            <span class="info-value" id="detail-affectation-nom">-</span>
                        </div>
                        <div class="info-ligne">
                            <i class="bi bi-briefcase"></i>
                            <span class="info-label">Poste</span>
                            <span class="info-value" id="detail-affectation-poste-info">-</span>
                        </div>
                        <div class="info-ligne">
                            <i class="bi bi-building"></i>
                            <span class="info-label">Service</span>
                            <span class="info-value" id="detail-affectation-service-info">-</span>
                        </div>
                    </div>
                </div>

                <div class="section-detail mt-3">
                    <div class="section-detail-titre">
                        <i class="bi bi-laptop"></i> Materiel affecte
                    </div>
                    <div class="mt-2">
                        <div class="info-ligne">
                            <i class="bi bi-box"></i>
                            <span class="info-label">Type</span>
                            <span class="info-value" id="detail-affectation-type">-</span>
                        </div>
                        <div class="info-ligne">
                            <i class="bi bi-pc-display"></i>
                            <span class="info-label">Materiel</span>
                            <span class="info-value" id="detail-affectation-materiel">-</span>
                        </div>
                        <div class="info-ligne">
                            <i class="bi bi-upc-scan"></i>
                            <span class="info-label">Reference</span>
                            <span class="info-value" id="detail-affectation-reference">-</span>
                        </div>
                    </div>
                </div>

                <div class="section-detail mt-3 mb-3">
                    <div class="section-detail-titre">
                        <i class="bi bi-calendar-event"></i> Periode
                    </div>
                    <div class="mt-2">
                        <div class="info-ligne">
                            <i class="bi bi-calendar-check"></i>
                            <span class="info-label">Date de debut</span>
                            <span class="info-value" id="detail-affectation-date-debut">-</span>
                        </div>
                        <div class="info-ligne">
                            <i class="bi bi-calendar-x"></i>
                            <span class="info-label">Date de fin</span>
                            <span class="info-value" id="detail-affectation-date-fin">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panneau-footer">
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm flex-fill" type="button" id="btn-modifier-affectation">
                        <i class="bi bi-pencil"></i> Modifier
                    </button>
                    <form method="POST" id="form-cloturer-affectation" class="flex-fill">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-outline-danger btn-sm w-100" type="button" id="btn-cloturer-affectation">
                            <i class="bi bi-check-circle"></i> Cloturer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal ajout affectation --}}
    <div class="modal fade" id="modalAjoutAffectation" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modal-affectation-titre">
                        <i class="bi bi-plus-circle me-2"></i>Nouvelle affectation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST"
                      action="{{ route('affectations.store') }}"
                      id="form-affectation"
                      data-store-url="{{ route('affectations.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="form-affectation-method" value="PATCH" disabled>
                    <div class="modal-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}
                            </div>
                        @endif
                        <div class="row g-3">

                            {{-- Collaborateur --}}
                            <div class="col-md-12" style="position: relative;">
                                <label class="form-label fw-semibold">Collaborateur *</label>
                                <input type="text"
                                       id="champ-personnel"
                                       class="form-control"
                                       placeholder="Rechercher un collaborateur..."
                                       autocomplete="off"
                                       data-personnels-url="{{ route('search.personnels') }}">
                                <input type="hidden" name="personnel_id" id="personnel-id" required>
                                <div id="suggestions-personnel" class="suggestions-container d-none"></div>
                            </div>

                            {{-- Type de matériel --}}
                            <div class="col-md-6" id="bloc-type-materiel">
                                <label class="form-label fw-semibold">Type de matériel *</label>
                                <select name="materiel_type" id="materiel_type" class="form-select" required>
                                    <option value="">Choisir...</option>
                                    <option value="pc-portable">PC Portable</option>
                                    <option value="mini-pc">Mini PC</option>
                                    <option value="ecran">Écran</option>
                                    <option value="clavier">Clavier</option>
                                    <option value="souris">Souris</option>
                                    <option value="casque">Casque</option>
                                </select>
                            </div>

                            {{-- Matériel (rempli dynamiquement) --}}
                            <div class="col-md-6" id="bloc-materiel">
                                <label class="form-label fw-semibold">Matériel *</label>
                                <select name="materiel_id" id="materiel_id" class="form-select" required disabled data-base-url="{{ url('/materiel-disponible') }}">
                                    <option value="">Choisir d'abord un type</option>
                                </select>
                            </div>

                            <div class="col-md-12 d-none" id="bloc-materiel-lecture">
                                <label class="form-label fw-semibold">Matériel affecté</label>
                                <div class="form-control bg-light" id="materiel-lecture-seule">-</div>
                            </div>

                            {{-- Date --}}
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Date de début *</label>
                                <input type="date" name="date_debut" id="date-debut-affectation" class="form-control" required>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" id="btn-submit-affectation">
                            <i class="bi bi-save me-1"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal confirmation cloture --}}
    <div class="modal fade" id="modalConfirmCloture" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>Confirmer la cloture
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">
                        Voulez-vous vraiment cloturer l'affectation de
                        <strong id="texte-nom-cloture">ce collaborateur</strong> ?
                        Le materiel affecte repassera en disponible.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="btn-confirmer-cloture">
                        <i class="bi bi-check-circle me-1"></i> Cloturer l'affectation
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script src="{{ asset('js/materiel.js') }}"></script>
    <script src="{{ asset('js/affectations.js') }}"></script>
@endsection
