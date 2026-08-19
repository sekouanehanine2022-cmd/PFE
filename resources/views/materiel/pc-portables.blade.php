{{-- resources/views/materiel/pc-portables.blade.php --}}

@extends('layouts.app')

@section('title', 'PC Portables')

@section('styles')
    <link href="{{ asset('css/materiel.css') }}" rel="stylesheet">
@endsection

@section('content')
    {{-- En-tete --}}
    <div class="mb-4">
        <small class="text-muted">Dashboard &gt; Matériel &gt; PC Portables</small>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <h2 class="fw-bold mb-0">
                <i class="bi bi-laptop me-2"></i>PC Portables
            </h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" type="button">
                    <i class="bi bi-download"></i> Exporter
                </button>
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalAjout">
                    <i class="bi bi-plus"></i> Ajouter un PC Portable
                </button>
            </div>
        </div>
    </div>

    {{-- Statistiques --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0f4ff">
                    <i class="bi bi-laptop" style="color:#3b82f6"></i>
                </div>
                <div>
                    <div class="stat-label">Total PC Portables</div>
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

    {{-- Recherche et filtres --}}
    <div class="bg-white rounded-3 p-3 mb-4 shadow-sm">
        <form method="GET" action="{{ route('pc-portables.index') }}">
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
                    <a href="{{ route('pc-portables.index') }}"
                       class="btn btn-filtre {{ !request('etat') ? 'active-filtre' : '' }}">Tous</a>
                    <a href="{{ route('pc-portables.index', ['etat' => 'disponible', 'search' => request('search')]) }}"
                       class="btn btn-filtre {{ request('etat') == 'disponible' ? 'active-filtre' : '' }}">
                       <span class="point-vert"></span> Disponible</a>
                    <a href="{{ route('pc-portables.index', ['etat' => 'affecte', 'search' => request('search')]) }}"
                       class="btn btn-filtre {{ request('etat') == 'affecte' ? 'active-filtre' : '' }}">
                       <span class="point-bleu"></span> Affecté</a>
                    <a href="{{ route('pc-portables.index', ['etat' => 'emprunte', 'search' => request('search')]) }}"
                       class="btn btn-filtre {{ request('etat') == 'emprunte' ? 'active-filtre' : '' }}">
                       <span class="point-orange"></span> Emprunté</a>
                    <a href="{{ route('pc-portables.index', ['etat' => 'hors_service', 'search' => request('search')]) }}"
                       class="btn btn-filtre {{ request('etat') == 'hors_service' ? 'active-filtre' : '' }}">
                       <span class="point-rouge"></span> Hors service</a>
                </div>
            </div>
        </form>
    </div>

    {{-- Tableau --}}
    <div class="row g-3 align-items-stretch inventaire-layout">
        <div class="col-12">
            <div class="bg-white rounded-3 shadow-sm tableau-card" id="tableau-pcs-card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nom / Modèle</th>
                                <th>Marque</th>
                                <th>N° Série</th>
                                <th>CPU</th>
                                <th>RAM</th>
                                <th>OS</th>
                                <th>État</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pcPortables as $pc)
                            @php
                            
    $affectationActive = $pc->affectations->where('statut', 'active')->first();
    $empruntActif = $pc->emprunts->where('statut', 'emprunte')->first();
    $affecteA = '-';
    if ($affectationActive && $affectationActive->personnel && $affectationActive->personnel->user) {
        $affecteA = $affectationActive->personnel->user->name;
    } elseif ($empruntActif && $empruntActif->etudiant && $empruntActif->etudiant->user) {
        $affecteA = $empruntActif->etudiant->user->name;
    }

    // Historique
    $historique = [];
    foreach ($pc->affectations as $aff) {
        if ($aff->personnel && $aff->personnel->user) {
            $historique[] = [
                'type'  => 'affectation',
                'nom'   => $aff->personnel->user->name,
                'date'  => $aff->date_debut ? \Carbon\Carbon::parse($aff->date_debut)->format('d/m/Y') : '-',
                'statut'=> $aff->statut,
            ];
        }
    }
    foreach ($pc->emprunts as $emp) {
        if ($emp->etudiant && $emp->etudiant->user) {
            $historique[] = [
                'type'  => 'emprunt',
                'nom'   => $emp->etudiant->user->name,
                'date'  => $emp->date_debut ? \Carbon\Carbon::parse($emp->date_debut)->format('d/m/Y') : '-',
                'statut'=> $emp->statut,
            ];
        }
    }

                            @endphp
                            <tr>
                                <td class="text-muted small">{{ $pc->reference }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="icone-appareil">
                                            <i class="bi bi-laptop"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $pc->nom }}</div>
                                            <div class="text-muted small">
                                                Acheté le {{ $pc->date_achat ? $pc->date_achat->format('d/m/Y') : '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $pc->marque }}</td>
                                <td><span class="badge-serie">{{ $pc->numero_serie }}</span></td>
                                <td>{{ $pc->cpu }}</td>
                                <td>{{ $pc->ram }}</td>
                                <td>
                                    <span class="badge-os">
                                        <i class="bi bi-windows"></i> {{ $pc->os }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-etat {{ $pc->etat }}">
                                        ● {{ ucfirst(str_replace('_', ' ', $pc->etat)) }}
                                    </span>
                                </td>
                                <td>
                                    <button type="button"
                                            class="btn btn-sm btn-action"
                                            onclick="ouvrirDetail(this)"
                                            aria-label="Voir les details"
                                            data-type-materiel="pc"
                                            data-reference="{{ $pc->reference }}"
                                            data-nom="{{ $pc->nom }}"
                                            data-marque="{{ $pc->marque }}"
                                            data-numero-serie="{{ $pc->numero_serie }}"
                                            data-cpu="{{ $pc->cpu }}"
                                            data-ram="{{ $pc->ram }}"
                                            data-stockage="{{ $pc->stockage }}"
                                            data-os="{{ $pc->os }}"
                                            data-ecran="{{ $pc->ecran ?: '-' }}"
                                            data-etat="{{ $pc->etat }}"
                                            data-etat-label="{{ ucfirst(str_replace('_', ' ', $pc->etat)) }}"
                                            data-emplacement="{{ $pc->emplacement ?: '-' }}"
                                            data-date-achat="{{ $pc->date_achat ? $pc->date_achat->format('d/m/Y') : '-' }}"
                                            data-affecte-a="{{ $affecteA }}"
                                            data-historique="{{ json_encode($historique) }}">
                                            
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    Aucun PC Portable trouvé
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <small class="text-muted">
                        Affichage {{ $pcPortables->count() }} sur {{ $total }} PC Portables
                    </small>
                </div>
            </div>
        </div>
    </div>

    {{-- Popup detail --}}
    <div class="panneau-backdrop d-none" id="panneau-detail-backdrop" onclick="fermerDetail()"></div>

    <div class="d-none" id="panneau-detail" aria-hidden="true">
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
                    <i class="bi bi-laptop"></i>
                </div>

                <div class="section-detail">
                    <div class="section-detail-titre">
                        <i class="bi bi-cpu"></i> Spécifications techniques
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-6">
                            <div class="spec-box">
                                <div class="spec-label">CPU</div>
                                <div class="spec-value" id="detail-cpu">-</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="spec-box">
                                <div class="spec-label">RAM</div>
                                <div class="spec-value" id="detail-ram">-</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="spec-box">
                                <div class="spec-label">Stockage</div>
                                <div class="spec-value" id="detail-stockage">-</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="spec-box">
                                <div class="spec-label">OS</div>
                                <div class="spec-value" id="detail-os">-</div>
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
                            <i class="bi bi-display"></i>
                            <span class="info-label">Écran</span>
                            <span class="info-value" id="detail-ecran">-</span>
                        </div>
                        <div class="info-ligne">
                            <i class="bi bi-person"></i>
                            <span class="info-label">Affecté à</span>
                            <span class="info-value" id="detail-affecte-a">-</span>
                        </div>
                    </div>
                </div>
            </div>
{{-- Historique --}}
<div class="section-detail mt-3">
    <div class="section-detail-titre">
        <i class="bi bi-clock-history"></i> Historique
    </div>
    <div class="mt-2" id="detail-historique">
        <p class="text-muted small">Aucun historique</p>
    </div>
</div>
            <div class="panneau-footer">
                <div class="d-flex gap-2 mb-2">
                    <button class="btn btn-primary btn-sm flex-fill" type="button">
                        <i class="bi bi-pencil"></i> Modifier
                    </button>
                    <button class="btn btn-outline-secondary btn-sm flex-fill"
                            id="btn-affecter" type="button">
                        <i class="bi bi-person-plus"></i> Affecter
                    </button>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-danger btn-sm flex-fill" type="button">
                        <i class="bi bi-exclamation-triangle"></i> Incident
                    </button>
                    <button class="btn btn-outline-danger btn-sm flex-fill" type="button">
                        <i class="bi bi-trash"></i> Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal ajout --}}
    <div class="modal fade" id="modalAjout" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle me-2"></i>Ajouter un PC Portable
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" action="{{ route('pc-portables.store') }}">
                    @csrf
                    <div class="modal-body">
                        @if (session('success'))
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                            </div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}
                            </div>
                        @endif
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Référence *</label>
                                <input type="text" name="reference" class="form-control" placeholder="ex: PP-001" value="{{ old('reference') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nom / Modèle *</label>
                                <input type="text" name="nom" class="form-control" placeholder="ex: Dell Latitude 5540" value="{{ old('nom') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Marque *</label>
                                <input type="text" name="marque" class="form-control" placeholder="ex: Dell" value="{{ old('marque') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">N° Série *</label>
                                <input type="text" name="numero_serie" class="form-control" placeholder="ex: DL2023-5540-001" value="{{ old('numero_serie') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">CPU *</label>
                                <input type="text" name="cpu" class="form-control" placeholder="ex: Intel i5-1335U" value="{{ old('cpu') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">RAM *</label>
                                <input type="text" name="ram" class="form-control" placeholder="ex: 16 Go DDR5" value="{{ old('ram') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Stockage *</label>
                                <input type="text" name="stockage" class="form-control" placeholder="ex: 512 Go SSD" value="{{ old('stockage') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Système d'exploitation *</label>
                                <select name="os" class="form-select" required>
                                    <option value="">Choisir...</option>
                                    <option value="Windows 11" {{ old('os') == 'Windows 11' ? 'selected' : '' }}>Windows 11</option>
                                    <option value="Windows 10" {{ old('os') == 'Windows 10' ? 'selected' : '' }}>Windows 10</option>
                                    <option value="macOS" {{ old('os') == 'macOS' ? 'selected' : '' }}>macOS</option>
                                    <option value="Linux" {{ old('os') == 'Linux' ? 'selected' : '' }}>Linux</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Taille écran</label>
                                <input type="text" name="ecran" class="form-control" placeholder="ex: 15.6 pouces" value="{{ old('ecran') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">État</label>
                                <select name="etat" id="etat" class="form-select">
                                    <option value="disponible" {{ old('etat') == 'disponible' ? 'selected' : '' }}>Disponible</option>
                                    <option value="affecte" {{ old('etat') == 'affecte' ? 'selected' : '' }}>Affecté</option>
                                    <option value="emprunte" {{ old('etat') == 'emprunte' ? 'selected' : '' }}>Emprunté</option>
                                    <option value="en_panne" {{ old('etat') == 'en_panne' ? 'selected' : '' }}>En panne</option>
                                    <option value="maintenance" {{ old('etat') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                    <option value="hors_service" {{ old('etat') == 'hors_service' ? 'selected' : '' }}>Hors service</option>
                                </select>
                            </div>
                            <div class="col-md-6" style="position: relative;">
                                <label class="form-label fw-semibold">À qui</label>
                                <input type="text"
                                       id="champ-a-qui"
                                       name="a_qui_nom"
                                       class="form-control"
                                       placeholder="Rechercher une personne..."
                                       autocomplete="off"
                                       data-personnels-url="{{ route('search.personnels') }}"
                                       data-etudiants-url="{{ route('search.etudiants') }}"
                                       disabled>
                                <input type="hidden" name="a_qui_id" id="a-qui-id">
                                <small class="text-muted" id="a-qui-aide">
                                    Sélectionnez d'abord un état
                                </small>
                                <div id="suggestions" class="suggestions-container d-none"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Emplacement</label>
                                <input type="text" name="emplacement" class="form-control" placeholder="ex: Salle 101" value="{{ old('emplacement') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Date d'achat</label>
                                <input type="date" name="date_achat" class="form-control" value="{{ old('date_achat') }}">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
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