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
            <h2 class="fw-bold mb-0">Affectations</h2>
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
        <div class="col-12 col-md-6 col-xl">
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
        <div class="col-12 col-md-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon" style="background:#eff6ff">
                    <i class="bi bi-clock" style="color:#3b82f6"></i>
                </div>
                <div>
                    <div class="stat-label">En cours</div>
                    <div class="stat-number">{{ $enCours }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff7e6">
                    <i class="bi bi-bell" style="color:#f59e0b"></i>
                </div>
                <div>
                    <div class="stat-label">Échéance &lt; 7 jours</div>
                    <div class="stat-number">{{ $echeanceProche }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff0f0">
                    <i class="bi bi-exclamation-triangle" style="color:#ef4444"></i>
                </div>
                <div>
                    <div class="stat-label">En retard</div>
                    <div class="stat-number">{{ $enRetard }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0fff4">
                    <i class="bi bi-check-circle" style="color:#22c55e"></i>
                </div>
                <div>
                    <div class="stat-label">Rendus</div>
                    <div class="stat-number">{{ $rendus }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Barre de recherche + Filtres --}}
    <div class="bg-white rounded-3 p-3 mb-4 shadow-sm">
        <form method="GET" action="{{ route('affectations.index') }}" class="d-flex align-items-center gap-3 flex-wrap">
            <div class="input-group" style="max-width: 350px;">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" name="search"
                       class="form-control border-start-0"
                       placeholder="Rechercher un collaborateur, matériel..."
                       value="{{ request('search') }}">
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button type="submit" name="statut" value="" class="btn btn-filtre {{ request('statut') == '' ? 'active-filtre' : '' }}">Tous les statuts</button>
                <button type="submit" name="statut" value="en_cours" class="btn btn-filtre {{ request('statut') == 'en_cours' ? 'active-filtre' : '' }}"><span class="point-bleu"></span> En cours</button>
                <button type="submit" name="statut" value="echeance_proche" class="btn btn-filtre {{ request('statut') == 'echeance_proche' ? 'active-filtre' : '' }}"><span class="point-orange"></span> Échéance proche</button>
                <button type="submit" name="statut" value="en_retard" class="btn btn-filtre {{ request('statut') == 'en_retard' ? 'active-filtre' : '' }}"><span class="point-rouge"></span> En retard</button>
                <button type="submit" name="statut" value="rendu" class="btn btn-filtre {{ request('statut') == 'rendu' ? 'active-filtre' : '' }}"><span class="point-vert"></span> Rendu</button>
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
                                <th>N° SÉRIE</th>
                                <th>DÉBUT</th>
                                <th>FIN PRÉVUE</th>
                                <th>ÉCHÉANCE</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($affectations as $affectation)
                            @php
                                $materiel     = $affectation->materiel;
                                $materielSpecifique = match ($materiel->type_materiel ?? null) {
                                    'pc_portable' => $materiel->pcPortable,
                                    'mini_pc' => $materiel->miniPc,
                                    'ecran' => $materiel->ecran,
                                    'clavier' => $materiel->clavier,
                                    'souris' => $materiel->souris,
                                    'casque' => $materiel->casque,
                                    default => null,
                                };
                                $numeroSerie = $materielSpecifique->numero_serie ?? '-';
                                $nom          = $affectation->personnel->user->name ?? '-';
                                $initiales    = collect(explode(' ', $nom))->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->join('');
                                $couleurs     = ['#3b82f6','#22c55e','#f97316','#7c3aed','#ef4444'];
                                $couleur      = $couleurs[crc32($nom) % count($couleurs)];
                                $typeMaterielSlug = match ($materiel->type_materiel ?? null) {
                                    'pc_portable' => 'pc-portable',
                                    'mini_pc' => 'mini-pc',
                                    'ecran' => 'ecran',
                                    'clavier' => 'clavier',
                                    'souris' => 'souris',
                                    'casque' => 'casque',
                                    default => 'materiel',
                                };
                                $typeMaterielLabel = match ($materiel->type_materiel ?? null) {
                                    'pc_portable' => 'PC portable',
                                    'mini_pc' => 'Mini PC',
                                    'ecran' => 'Ecran',
                                    'clavier' => 'Clavier',
                                    'souris' => 'Souris',
                                    'casque' => 'Casque',
                                    default => 'Materiel',
                                };
                                $dateDebut = $affectation->date_debut ? \Carbon\Carbon::parse($affectation->date_debut)->format('d/m/Y') : '-';
                                $dateFin = $affectation->date_fin ? \Carbon\Carbon::parse($affectation->date_fin)->format('d/m/Y') : '-';
                                $dateFinIso = $affectation->date_fin ? \Carbon\Carbon::parse($affectation->date_fin)->format('Y-m-d') : '';
                                $joursRestants = $affectation->date_fin
                                    ? (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($affectation->date_fin)->startOfDay(), false)
                                    : null;

                                if ($affectation->statut === 'rendu') {
                                    $echeanceClass = 'normal';
                                    $echeanceLabel = 'Rendu' . ($affectation->date_fin ? ' le ' . $dateFin : '');
                                    $alerteTitre = 'Affectation rendue';
                                    $alerteTexte = $affectation->date_fin
                                        ? 'Le materiel a ete rendu le ' . $dateFin
                                        : 'Le materiel a ete rendu.';
                                } elseif ($joursRestants === null) {
                                    $echeanceClass = 'normal';
                                    $echeanceLabel = 'Indeterminee';
                                    $alerteTitre = 'Duree indeterminee';
                                    $alerteTexte = 'Aucune date de fin prevue pour cette affectation.';
                                } elseif ($joursRestants < 0) {
                                    $echeanceClass = 'retard';
                                    $echeanceLabel = 'Retard ' . abs($joursRestants) . 'j';
                                    $alerteTitre = 'Retard de retour';
                                    $alerteTexte = 'Le materiel devait etre rendu le ' . $dateFin;
                                } elseif ($joursRestants <= 7) {
                                    $echeanceClass = $joursRestants === 0 ? 'aujourd-hui' : 'proche';
                                    $echeanceLabel = $joursRestants === 0 ? "Aujourd'hui" : 'Dans ' . $joursRestants . ' jours';
                                    $alerteTitre = $joursRestants === 0 ? 'Echeance aujourd hui' : 'Echeance dans ' . $joursRestants . ' jours';
                                    $alerteTexte = 'Le materiel doit etre rendu le ' . $dateFin;
                                } else {
                                    $echeanceClass = 'normal';
                                    $echeanceLabel = 'Dans ' . $joursRestants . ' jours';
                                    $alerteTitre = 'Echeance dans ' . $joursRestants . ' jours';
                                    $alerteTexte = 'Le materiel doit etre rendu le ' . $dateFin;
                                }
                            @endphp
                            <tr class="{{ $echeanceClass === 'retard' ? 'ligne-retard' : '' }}">
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
                                <td><span class="badge-serie">{{ $numeroSerie }}</span></td>
                                <td>{{ $dateDebut }}</td>
                                <td class="text-muted">{{ $dateFin }}</td>
                                <td>
                                    <span class="echeance {{ $echeanceClass }}">
                                        @if ($echeanceClass === 'retard')
                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                        @endif
                                        {{ $echeanceLabel }}
                                    </span>
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
                                            data-materiel-type-label="{{ $typeMaterielLabel }}"
                                            data-materiel-numero-serie="{{ $numeroSerie }}"
                                            data-materiel-nom="{{ $materiel->nom ?? '-' }}"
                                            data-numero-serie="{{ $numeroSerie }}"
                                            data-date-debut="{{ $affectation->date_debut ? $affectation->date_debut->format('Y-m-d') : '' }}"
                                            data-date-debut-label="{{ $dateDebut }}"
                                            data-date-fin="{{ $dateFinIso }}"
                                            data-date-fin-label="{{ $dateFin }}"
                                            data-echeance-label="{{ $echeanceLabel }}"
                                            data-echeance-class="{{ $echeanceClass }}"
                                            data-alerte-titre="{{ $alerteTitre }}"
                                            data-alerte-texte="{{ $alerteTexte }}"
                                            data-statut="{{ $affectation->statut }}"
                                            data-update-url="{{ route('affectations.update', $affectation) }}"
                                            data-retour-url="{{ route('affectations.retour', $affectation) }}"
                                            data-suppression-url="{{ route('affectations.destroy', $affectation) }}">
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
                            <span class="info-label">N° Série</span>
                            <span class="info-value" id="detail-affectation-serie">-</span>
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
                <div class="d-flex gap-2 mb-2">
                    <button class="btn btn-primary btn-sm flex-fill" type="button" id="btn-modifier-affectation">
                        <i class="bi bi-pencil"></i> Modifier
                    </button>
                    <form method="POST" id="form-valider-retour-affectation" class="flex-fill">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-success btn-sm w-100" type="button" id="btn-valider-retour-affectation">
                            <i class="bi bi-check-circle"></i> Valider retour
                        </button>
                    </form>
                </div>
                <button class="btn btn-outline-danger btn-sm w-100" type="button" id="btn-supprimer-affectation">
                    <i class="bi bi-trash"></i> Supprimer
                </button>
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
                                <select name="materiel_numero_serie" id="materiel_numero_serie" class="form-select" required disabled data-base-url="{{ url('/materiel-disponible') }}">
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

    {{-- Modal confirmation retour --}}
    <div class="modal fade" id="modalConfirmRetourAffectation" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-check-circle me-2 text-success"></i>Valider le retour
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">
                        Voulez-vous vraiment valider le retour de
                        <strong id="texte-nom-retour-affectation">ce collaborateur</strong> ?
                        Le materiel affecte repassera en disponible.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-success" id="btn-confirmer-retour-affectation">
                        <i class="bi bi-check me-1"></i> Confirmer
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal confirmation suppression --}}
    <div class="modal fade" id="modalConfirmSuppressionAffectation" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="form-supprimer-affectation" class="modal-content">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="bi bi-trash me-2"></i>Confirmer la suppression
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">
                        Voulez-vous vraiment supprimer l'affectation de
                        <strong id="texte-nom-suppression">ce collaborateur</strong> ?
                        Si elle est encore active, le materiel affecte repassera en disponible.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Supprimer l'affectation
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@section('scripts')
    <script src="{{ asset('js/materiel.js') }}"></script>
    <script src="{{ asset('js/affectations.js') }}"></script>
@endsection
