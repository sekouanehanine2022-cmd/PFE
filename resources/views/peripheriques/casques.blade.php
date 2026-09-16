{{-- resources/views/peripheriques/casques.blade.php --}}

@extends('layouts.app')

@section('title', 'Casques')

@section('styles')
    <link href="{{ asset('css/materiel.css') }}" rel="stylesheet">
    <link href="{{ asset('css/peripheriques.css') }}" rel="stylesheet">
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
        <small class="text-muted">Dashboard &gt; Périphériques &gt; Casques</small>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <h2 class="fw-bold mb-0">Casques</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary">
                    <i class="bi bi-download"></i> Exporter
                </button>
                <button class="btn btn-primary" type="button"
                        data-bs-toggle="modal" data-bs-target="#modalAjout" onclick="reinitialiserModalMateriel()">
                    <i class="bi bi-plus"></i> Ajouter un casque
                </button>
            </div>
        </div>
    </div>

    {{-- Les 4 cartes statistiques --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0f4ff">
                    <i class="bi bi-headphones" style="color:#3b82f6"></i>
                </div>
                <div>
                    <div class="stat-label">Total casques</div>
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
        <form method="GET" action="{{ route('casques.index') }}">
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
                    <a href="{{ route('casques.index') }}"
                       class="btn btn-filtre {{ !request('etat') ? 'active-filtre' : '' }}">Tous</a>
                    <a href="{{ route('casques.index', ['etat' => 'disponible']) }}"
                       class="btn btn-filtre {{ request('etat') == 'disponible' ? 'active-filtre' : '' }}">
                       <span class="point-vert"></span> Disponible</a>
                    <a href="{{ route('casques.index', ['etat' => 'affecte']) }}"
                       class="btn btn-filtre {{ request('etat') == 'affecte' ? 'active-filtre' : '' }}">
                       <span class="point-bleu"></span> Affecté</a>
                    <a href="{{ route('casques.index', ['etat' => 'emprunte']) }}"
                       class="btn btn-filtre {{ request('etat') == 'emprunte' ? 'active-filtre' : '' }}">
                       <span class="point-orange"></span> Emprunté</a>
                    <a href="{{ route('casques.index', ['etat' => 'en_panne']) }}"
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
                                <th>Connexion</th>
                                <th>Micro</th>
                                <th>Réduction bruit</th>
                                <th>État</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($casques as $casque)
                            @php
                                $affectationActive = $casque->affectations->where('statut', 'active')->first();
                                $empruntActif = $casque->emprunts->where('statut', 'en_cours')->first();
                                $affecteA = '-';
                                if ($affectationActive && $affectationActive->personnel && $affectationActive->personnel->user) {
                                    $affecteA = $affectationActive->personnel->user->name;
                                } elseif ($empruntActif && $empruntActif->etudiant && $empruntActif->etudiant->user) {
                                    $affecteA = $empruntActif->etudiant->user->name;
                                }

                                $historique = [];
                                foreach ($casque->affectations as $aff) {
                                    if ($aff->personnel && $aff->personnel->user) {
                                        $historique[] = [
                                            'type'   => 'affectation',
                                            'nom'    => $aff->personnel->user->name,
                                            'date'   => $aff->date_debut ? \Carbon\Carbon::parse($aff->date_debut)->format('d/m/Y') : '-',
                                            'statut' => $aff->statut,
                                        ];
                                    }
                                }
                                foreach ($casque->emprunts as $emp) {
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
                                <td><span class="badge-serie">{{ $casque->numero_serie }}</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="icone-appareil"><i class="bi bi-headphones"></i></div>
                                        <div>
                                            <div class="fw-semibold">{{ $casque->nom }}</div>
                                            <div class="text-muted small">
                                                Acheté le {{ $casque->date_achat ? $casque->date_achat->format('d/m/Y') : '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $casque->marque }}</td>
                                <td>{{ $casque->connexion }}</td>
                                <td>{{ $casque->micro ? 'Oui' : 'Non' }}</td>
                                <td>{{ $casque->reduction_bruit ? 'Oui' : 'Non' }}</td>
                                <td>
                                    <span class="badge-etat {{ $casque->etat }}">
                                        ● {{ ucfirst(str_replace('_', ' ', $casque->etat)) }}
                                    </span>
                                </td>
                                <td>
                                    <button type="button"
                                            class="btn btn-sm btn-action"
                                            onclick="ouvrirDetail(this)"
                                            data-type-materiel="casque"
                                            data-id="{{ $casque->numero_serie }}"
                                            data-nom="{{ $casque->nom }}"
                                            data-marque="{{ $casque->marque }}"
                                            data-numero-serie="{{ $casque->numero_serie }}"
                                            data-connexion="{{ $casque->connexion }}"
                                            data-micro="{{ $casque->micro ? '1' : '0' }}"
                                            data-micro-label="{{ $casque->micro ? 'Oui' : 'Non' }}"
                                            data-reduction-bruit="{{ $casque->reduction_bruit ? '1' : '0' }}"
                                            data-reduction-bruit-label="{{ $casque->reduction_bruit ? 'Oui' : 'Non' }}"
                                            data-etat="{{ $casque->etat }}"
                                            data-etat-label="{{ ucfirst(str_replace('_', ' ', $casque->etat)) }}"
                                            data-emplacement="{{ $casque->emplacement ?? '-' }}"
                                            data-date-achat="{{ $casque->date_achat ? $casque->date_achat->format('d/m/Y') : '-' }}"
                                            data-date-achat-iso="{{ $casque->date_achat ? $casque->date_achat->format('Y-m-d') : '' }}"
                                            data-affecte-a="{{ $affecteA }}"
                                            data-historique="{{ json_encode($historique) }}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Aucun casque trouvé
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <small class="text-muted">
                        Affichage {{ $casques->count() }} sur {{ $total }} casques
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
                    <i class="bi bi-headphones"></i>
                </div>

                <div class="section-detail">
                    <div class="section-detail-titre">
                        <i class="bi bi-sliders"></i> Caractéristiques
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-6">
                            <div class="spec-box">
                                <div class="spec-label">CONNEXION</div>
                                <div class="spec-value" id="detail-connexion-periph">-</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="spec-box">
                                <div class="spec-label">MICRO</div>
                                <div class="spec-value" id="detail-micro">-</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="spec-box">
                                <div class="spec-label">RÉDUCTION BRUIT</div>
                                <div class="spec-value" id="detail-reduction-bruit">-</div>
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
                        <i class="bi bi-plus-circle me-2"></i> Ajouter un casque
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('casques.store') }}" id="form-modal-materiel" data-store-url="{{ route('casques.store') }}" data-update-url-base="{{ url('/casques') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nom / Modèle *</label>
                                <input type="text" name="nom" id="champ-nom" class="form-control" placeholder="ex: Sony WH-1000XM5" value="{{ old('nom') }}" maxlength="80" pattern="[A-Za-z0-9 ._+\-]+" title="Lettres, chiffres, espaces, point, tiret, underscore et plus uniquement." required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Marque *</label>
                                <input type="text" name="marque" id="champ-marque" class="form-control" placeholder="ex: Sony" value="{{ old('marque') }}" maxlength="50" pattern="[A-Za-z0-9 ._+\-]+" title="Lettres, chiffres, espaces, point, tiret, underscore et plus uniquement." required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">N° Série *</label>
                                <input type="text" name="numero_serie" id="champ-numero-serie" class="form-control champ-identifiant" placeholder="ex: SN-WH1000XM5-001" value="{{ old('numero_serie') }}" maxlength="100" pattern="[A-Za-z0-9._-]+" title="Lettres, chiffres, point, tiret et underscore uniquement." required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Connexion *</label>
                                <select name="connexion" id="champ-connexion" class="form-select champ-select-autre" data-autre-target="bloc-connexion-autre" required>
                                    <option value="">Choisir...</option>
                                    <option value="bluetooth" {{ old('connexion') == 'bluetooth' ? 'selected' : '' }}>Bluetooth</option>
                                    <option value="filaire" {{ old('connexion') == 'filaire' ? 'selected' : '' }}>Filaire USB</option>
                                    <option value="sans_fil" {{ old('connexion') == 'sans_fil' ? 'selected' : '' }}>Sans fil</option>
                                    <option value="autre" {{ old('connexion') == 'autre' ? 'selected' : '' }}>Autre</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-none" id="bloc-connexion-autre">
                                <label class="form-label fw-semibold">Autre connexion *</label>
                                <input type="text" name="connexion_autre" class="form-control" placeholder="ex: USB-C" value="{{ old('connexion_autre') }}" maxlength="30" pattern="[A-Za-z0-9 ._+()\/-]+" title="Lettres, chiffres et signes techniques simples uniquement.">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Micro</label>
                                <select name="micro" id="champ-micro" class="form-select">
                                    <option value="1" {{ old('micro', '1') == '1' ? 'selected' : '' }}>Oui</option>
                                    <option value="0" {{ old('micro') == '0' ? 'selected' : '' }}>Non</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Réduction de bruit</label>
                                <select name="reduction_bruit" id="champ-reduction-bruit" class="form-select">
                                    <option value="0" {{ old('reduction_bruit', '0') == '0' ? 'selected' : '' }}>Non</option>
                                    <option value="1" {{ old('reduction_bruit') == '1' ? 'selected' : '' }}>Oui</option>
                                </select>
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
                    <p class="mb-0">Voulez-vous vraiment supprimer <strong id="texte-nom-suppression">ce casque</strong> ? Cette action est irréversible.</p>
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
    <form method="POST" id="form-suppression" data-delete-url-base="{{ url('/casques') }}" class="d-none">
        @csrf
        @method('DELETE')
    </form>

    {{-- Formulaire caché utilisé pour signaler une panne / marquer comme réparé --}}
    <form method="POST" id="form-panne" data-panne-url-base="{{ url('/casques') }}" class="d-none">
        @csrf
        @method('PATCH')
    </form>

@endsection

@section('scripts')
    <script src="{{ asset('js/materiel.js') }}"></script>
    <script src="{{ asset('js/materiel-crud.js') }}"></script>
@endsection

