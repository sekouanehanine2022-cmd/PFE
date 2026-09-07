{{-- resources/views/materiel/mini-pc.blade.php --}}

@extends('layouts.app')

@section('title', 'Mini PC')

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
        <small class="text-muted">Dashboard &gt; Matériel &gt; Mini PC</small>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <h2 class="fw-bold mb-0">
                🖥️ Mini PC
            </h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary">
                    <i class="bi bi-download"></i> Exporter
                </button>
                <button class="btn btn-primary" type="button"
                        data-bs-toggle="modal" data-bs-target="#modalAjout" onclick="reinitialiserModalMateriel()">
                    <i class="bi bi-plus"></i> Ajouter un Mini PC
                </button>
            </div>
        </div>
    </div>

    {{-- Les 4 cartes statistiques --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0f4ff">
                    <i class="bi bi-pc" style="color:#3b82f6"></i>
                </div>
                <div>
                    <div class="stat-label">Total Mini PC</div>
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
                    <div class="stat-label">En panne</div>
                    <div class="stat-number">{{ $enPanne }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Barre de recherche + Filtres --}}
    <div class="bg-white rounded-3 p-3 mb-4 shadow-sm">
        <form method="GET" action="{{ route('mini-pc.index') }}">
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
                    <a href="{{ route('mini-pc.index') }}"
                       class="btn btn-filtre {{ !request('etat') ? 'active-filtre' : '' }}">Tous</a>
                    <a href="{{ route('mini-pc.index', ['etat' => 'disponible']) }}"
                       class="btn btn-filtre {{ request('etat') == 'disponible' ? 'active-filtre' : '' }}">
                       <span class="point-vert"></span> Disponible</a>
                    <a href="{{ route('mini-pc.index', ['etat' => 'affecte']) }}"
                       class="btn btn-filtre {{ request('etat') == 'affecte' ? 'active-filtre' : '' }}">
                       <span class="point-bleu"></span> Affecté</a>
                    <a href="{{ route('mini-pc.index', ['etat' => 'emprunte']) }}"
                       class="btn btn-filtre {{ request('etat') == 'emprunte' ? 'active-filtre' : '' }}">
                       <span class="point-orange"></span> Emprunté</a>
                    <a href="{{ route('mini-pc.index', ['etat' => 'en_panne']) }}"
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
                                <th>ADRESSE MAC</th>
                                <th>CPU</th>
                                <th>RAM</th>
                                <th>OS</th>
                                <th>ÉTAT</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($miniPcs as $miniPc)
                            @php
                                $affectationActive = $miniPc->affectations->where('statut', 'active')->first();
                                $empruntActif = $miniPc->emprunts->where('statut', 'en_cours')->first();
                                $affecteA = '-';
                                if ($affectationActive && $affectationActive->personnel && $affectationActive->personnel->user) {
                                    $affecteA = $affectationActive->personnel->user->name;
                                } elseif ($empruntActif && $empruntActif->etudiant && $empruntActif->etudiant->user) {
                                    $affecteA = $empruntActif->etudiant->user->name;
                                }

                                $historique = [];
                                foreach ($miniPc->affectations as $aff) {
                                    if ($aff->personnel && $aff->personnel->user) {
                                        $historique[] = [
                                            'type'   => 'affectation',
                                            'nom'    => $aff->personnel->user->name,
                                            'date'   => $aff->date_debut ? \Carbon\Carbon::parse($aff->date_debut)->format('d/m/Y') : '-',
                                            'statut' => $aff->statut,
                                        ];
                                    }
                                }
                                foreach ($miniPc->emprunts as $emp) {
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
                                <td class="text-muted small">{{ $miniPc->reference }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="icone-appareil"><i class="bi bi-pc"></i></div>
                                        <div>
                                            <div class="fw-semibold">{{ $miniPc->nom }}</div>
                                            <div class="text-muted small">
                                                Acheté le {{ $miniPc->date_achat ? $miniPc->date_achat->format('d/m/Y') : '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $miniPc->marque }}</td>
                                <td><span class="badge-serie">{{ $miniPc->numero_serie }}</span></td>
                                <td class="text-muted small">{{ $miniPc->adresse_mac ?: '-' }}</td>
                                <td>{{ $miniPc->cpu }}</td>
                                <td>{{ $miniPc->ram }}</td>
                                <td><span class="badge-os"><i class="bi bi-windows"></i> {{ $miniPc->os }}</span></td>
                                <td>
                                    <span class="badge-etat {{ $miniPc->etat }}">
                                        ● {{ ucfirst(str_replace('_', ' ', $miniPc->etat)) }}
                                    </span>
                                </td>
                                <td>
                                    <button type="button"
                                            class="btn btn-sm btn-action"
                                            onclick="ouvrirDetail(this)"
                                            data-type-materiel="mini-pc"
                                            data-id="{{ $miniPc->id }}"
                                            data-reference="{{ $miniPc->reference }}"
                                            data-nom="{{ $miniPc->nom }}"
                                            data-marque="{{ $miniPc->marque }}"
                                            data-numero-serie="{{ $miniPc->numero_serie }}"
                                            data-adresse-mac="{{ $miniPc->adresse_mac ?: '-' }}"
                                            data-cpu="{{ $miniPc->cpu }}"
                                            data-ram="{{ $miniPc->ram }}"
                                            data-stockage="{{ $miniPc->stockage }}"
                                            data-os="{{ $miniPc->os }}"
                                            data-etat="{{ $miniPc->etat }}"
                                            data-etat-label="{{ ucfirst(str_replace('_', ' ', $miniPc->etat)) }}"
                                            data-emplacement="{{ $miniPc->emplacement ?: '-' }}"
                                            data-date-achat="{{ $miniPc->date_achat ? $miniPc->date_achat->format('d/m/Y') : '-' }}"
                                            data-date-achat-iso="{{ $miniPc->date_achat ? $miniPc->date_achat->format('Y-m-d') : '' }}"
                                            data-affecte-a="{{ $affecteA }}"
                                            data-historique="{{ json_encode($historique) }}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    Aucun Mini PC trouvé
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <small class="text-muted">
                        Affichage {{ $miniPcs->count() }} sur {{ $total }} Mini PC
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
                    <i class="bi bi-pc"></i>
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
                                <div class="spec-label">STOCKAGE</div>
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
                            <i class="bi bi-hdd-network"></i>
                            <span class="info-label">Adresse MAC</span>
                            <span class="info-value" id="detail-adresse-mac">-</span>
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
                        <i class="bi bi-plus-circle me-2"></i> Ajouter un Mini PC
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('mini-pc.store') }}" id="form-modal-materiel" data-store-url="{{ route('mini-pc.store') }}" data-update-url-base="{{ url('/mini-pc') }}">
                    @csrf
                    <div class="modal-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">{{ $errors->first() }}</div>
                        @endif
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Référence *</label>
                                <input type="text" name="reference" id="champ-reference" class="form-control champ-identifiant" placeholder="ex: MP-001" value="{{ old('reference') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nom / Modèle *</label>
                                <input type="text" name="nom" id="champ-nom" class="form-control" placeholder="ex: Dell OptiPlex 3000" value="{{ old('nom') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Marque *</label>
                                <input type="text" name="marque" id="champ-marque" class="form-control" placeholder="ex: Dell" value="{{ old('marque') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">N° Série *</label>
                                <input type="text" name="numero_serie" id="champ-numero-serie" class="form-control champ-identifiant" placeholder="ex: DP2022-3000-001" value="{{ old('numero_serie') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">CPU *</label>
                                <input type="text" name="cpu" id="champ-cpu" class="form-control" placeholder="ex: Intel i3-1215U" value="{{ old('cpu') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">RAM *</label>
                                <input type="text" name="ram" id="champ-ram" class="form-control" placeholder="ex: 8 Go DDR4" value="{{ old('ram') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Stockage *</label>
                                <input type="text" name="stockage" id="champ-stockage" class="form-control" placeholder="ex: 256 Go SSD" value="{{ old('stockage') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">OS *</label>
                                <select name="os" id="champ-os" class="form-select" required>
                                    <option value="">Choisir...</option>
                                    <option value="Windows 11" {{ old('os') == 'Windows 11' ? 'selected' : '' }}>Windows 11</option>
                                    <option value="Windows 10" {{ old('os') == 'Windows 10' ? 'selected' : '' }}>Windows 10</option>
                                    <option value="Linux" {{ old('os') == 'Linux' ? 'selected' : '' }}>Linux</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Adresse MAC</label>
                                <input type="text" name="adresse_mac" id="champ-adresse-mac" class="form-control" placeholder="ex: A1:B2:C3:D4:E5:F6" value="{{ old('adresse_mac') }}">
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
                                <input type="text" name="emplacement" id="champ-emplacement" class="form-control" placeholder="ex: Bureau Admin" value="{{ old('emplacement') }}">
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
                    <p class="mb-0">Voulez-vous vraiment supprimer <strong id="texte-nom-suppression">ce Mini PC</strong> ? Cette action est irréversible.</p>
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
    <form method="POST" id="form-suppression" data-delete-url-base="{{ url('/mini-pc') }}" class="d-none">
        @csrf
        @method('DELETE')
    </form>

    {{-- Formulaire caché utilisé pour signaler une panne / marquer comme réparé --}}
    <form method="POST" id="form-panne" data-panne-url-base="{{ url('/mini-pc') }}" class="d-none">
        @csrf
        @method('PATCH')
    </form>

@endsection

@section('scripts')
    <script src="{{ asset('js/materiel.js') }}"></script>
    <script src="{{ asset('js/materiel-crud.js') }}"></script>
@endsection