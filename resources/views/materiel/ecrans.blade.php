{{-- resources/views/materiel/ecrans.blade.php --}}

@extends('layouts.app')

@section('title', 'Écrans')

@section('styles')
    <link href="{{ asset('css/materiel.css') }}" rel="stylesheet">
@endsection

@section('content')

    {{-- Fil d'ariane + Titre --}}
    <div class="mb-4">
        <small class="text-muted">Dashboard &gt; Matériel &gt; Écrans</small>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <h2 class="fw-bold mb-0">🖥️ Écrans</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary">
                    <i class="bi bi-download"></i> Exporter
                </button>
                <button class="btn btn-primary" type="button"
                        data-bs-toggle="modal" data-bs-target="#modalAjout">
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
                    <a href="{{ route('ecrans.index', ['etat' => 'hors_service']) }}"
                       class="btn btn-filtre {{ request('etat') == 'hors_service' ? 'active-filtre' : '' }}">
                       <span class="point-rouge"></span> Hors service</a>
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
                                <th>TAILLE</th>
                                <th>RÉSOLUTION</th>
                                <th>DALLE</th>
                                <th>ÉTAT</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ecrans as $ecran)
                            @php
                                $affectationActive = $ecran->affectations->where('statut', 'active')->first();
                                $empruntActif = $ecran->emprunts->where('statut', 'emprunte')->first();
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
                                <td class="text-muted small">{{ $ecran->reference }}</td>
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
                                <td><span class="badge-serie">{{ $ecran->numero_serie }}</span></td>
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
                                            data-reference="{{ $ecran->reference }}"
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
                                            data-affecte-a="{{ $affecteA }}"
                                            data-historique="{{ json_encode($historique) }}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Aucun écran trouvé</td>
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
    <div class="modal fade" id="modalAjout" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle me-2"></i> Ajouter un écran
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('ecrans.store') }}">
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
                                <input type="text" name="reference" class="form-control" placeholder="ex: EC-001" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nom / Modèle *</label>
                                <input type="text" name="nom" class="form-control" placeholder="ex: LG 27UK850-W" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Marque *</label>
                                <input type="text" name="marque" class="form-control" placeholder="ex: LG" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">N° Série *</label>
                                <input type="text" name="numero_serie" class="form-control" placeholder="ex: LG2022-27UK-001" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Taille *</label>
                                <input type="text" name="taille" class="form-control" placeholder="ex: 27 pouces" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Résolution *</label>
                                <input type="text" name="resolution" class="form-control" placeholder="ex: 4K UHD" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Dalle</label>
                                <input type="text" name="dalle" class="form-control" placeholder="ex: IPS">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Taux de rafraîchissement</label>
                                <input type="text" name="taux_rafraichissement" class="form-control" placeholder="ex: 60 Hz">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">État</label>
                                <select name="etat" id="etat" class="form-select">
                                    <option value="disponible">Disponible</option>
                                    <option value="affecte">Affecté</option>
                                    <option value="emprunte">Emprunté</option>
                                    <option value="en_panne">En panne</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="hors_service">Hors service</option>
                                </select>
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
                                <input type="text" name="emplacement" class="form-control" placeholder="ex: Salle 101">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Date d'achat</label>
                                <input type="date" name="date_achat" class="form-control">
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