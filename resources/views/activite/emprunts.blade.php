{{-- resources/views/activite/emprunts.blade.php --}}

@extends('layouts.app')

@section('title', 'Emprunts')

@section('styles')
    <link href="{{ asset('css/materiel.css') }}" rel="stylesheet">
    <link href="{{ asset('css/affectations.css') }}" rel="stylesheet">
    <link href="{{ asset('css/emprunts.css') }}" rel="stylesheet">
@endsection

@section('content')

    {{-- Fil d'ariane + Titre --}}
    <div class="mb-4">
        <small class="text-muted">Dashboard > Activité > Emprunts</small>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <h2 class="fw-bold mb-0">⇄ Emprunts</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary">
                    <i class="bi bi-download"></i> Exporter
                </button>
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalAjoutEmprunt">
                    <i class="bi bi-plus"></i> Nouvel emprunt
                </button>
            </div>
        </div>
    </div>

    {{-- Bandeau alerte retard --}}
    <div class="alerte-retard mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                <span><strong>1 emprunt en retard</strong> — Martin L. n'a pas rendu le HP EliteBook 840 G9 (échéance dépassée de 2 jours)</span>
            </div>
            <a href="#" class="text-danger fw-semibold">Voir →</a>
        </div>
    </div>

    {{-- Les 5 cartes statistiques --}}
    <div class="row g-3 mb-4">

        <div class="col-12 col-md-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0f4ff">
                    <i class="bi bi-arrow-left-right" style="color:#3b82f6"></i>
                </div>
                <div>
                    <div class="stat-label">Total emprunts</div>
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
                    <div class="stat-label">Rendus ce mois</div>
                    <div class="stat-number">{{ $rendusCeMois }}</div>
                </div>
            </div>
        </div>

    </div>

    {{-- Barre de recherche + Filtres --}}
    <div class="bg-white rounded-3 p-3 mb-4 shadow-sm">
        <form method="GET" action="{{ route('emprunts.index') }}" class="d-flex align-items-center gap-3 flex-wrap">

            <div class="input-group" style="max-width: 350px;">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       class="form-control border-start-0"
                       placeholder="Rechercher un emprunteur, matériel...">
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

    {{-- Tableau + Panneau détail --}}
    <div class="row g-3 align-items-stretch inventaire-layout">

        <div class="col-12" id="colonne-tableau">
            <div class="bg-white rounded-3 shadow-sm tableau-card" id="tableau-pcs-card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">

                        <thead>
                            <tr>
                                <th>#</th>
                                <th>EMPRUNTEUR</th>
                                <th>TYPE</th>
                                <th>MATÉRIEL EMPRUNTÉ</th>
                                <th>DÉBUT</th>
                                <th>FIN PRÉVUE</th>
                                <th>ÉCHÉANCE</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse ($emprunts as $emprunt)
                                @php
                                    $etudiant = $emprunt->etudiant;
                                    $user     = $etudiant->user ?? null;
                                    $nom      = $user->name ?? 'Inconnu';
                                    $email    = $user->email ?? '-';

                                    // Initiales pour l'avatar (ex: "Martin L." -> "ML")
                                    $mots      = explode(' ', trim($nom));
                                    $initiales = strtoupper(substr($mots[0] ?? '', 0, 1) . substr($mots[1] ?? '', 0, 1));

                                    // Type d'emprunteur
                                    $typeLabel = $etudiant && $etudiant->type == 'alt_externe' ? 'Alt. Externe' : 'Étud. Initial';
                                    $typeClass = $etudiant && $etudiant->type == 'alt_externe' ? 'externe' : 'etudiant';

                                    // Matériel emprunté
                                    $materielNom = $emprunt->materiel->nom ?? '-';

                                    // Dates
                                    $dateDebut     = $emprunt->date_debut ? \Carbon\Carbon::parse($emprunt->date_debut)->format('d/m/Y') : '-';
                                    $dateFinPrevue = $emprunt->date_fin_prevue ? \Carbon\Carbon::parse($emprunt->date_fin_prevue)->format('d/m/Y') : '-';

                                    // Échéance selon le statut
                                    $joursRestants = $emprunt->date_fin_prevue
                                        ? (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($emprunt->date_fin_prevue)->startOfDay(), false)
                                        : null;
                                @endphp
                                <tr class="{{ $emprunt->statut == 'en_retard' ? 'ligne-retard' : '' }}">
                                    <td class="text-muted small">EM-{{ str_pad($emprunt->id, 3, '0', STR_PAD_LEFT) }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar" style="background:#3b82f6">{{ $initiales }}</div>
                                            <div>
                                                <div class="fw-semibold">{{ $nom }}</div>
                                                <div class="text-muted small">{{ $email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge-type-emprunt {{ $typeClass }}">{{ $typeLabel }}</span></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-laptop text-muted"></i>
                                            <span>{{ $materielNom }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $dateDebut }}</td>
                                    <td>{{ $dateFinPrevue }}</td>
                                    <td>
                                        @if ($emprunt->statut == 'rendu')
                                            <span class="echeance normal">
                                                Rendu {{ $emprunt->date_retour ? 'le '.\Carbon\Carbon::parse($emprunt->date_retour)->format('d/m/Y') : '' }}
                                            </span>
                                        @elseif ($emprunt->statut == 'en_retard')
                                            <span class="echeance retard">
                                                <i class="bi bi-exclamation-triangle-fill"></i>
                                                Retard {{ $joursRestants !== null ? abs($joursRestants) : '' }}j
                                            </span>
                                        @elseif ($emprunt->statut == 'echeance_proche')
                                            <span class="echeance proche">
                                                {{ $joursRestants === 0 ? "Aujourd'hui" : 'Dans '.$joursRestants.' jours' }}
                                            </span>
                                        @else
                                            <span class="echeance normal">
                                                {{ $joursRestants !== null ? 'Dans '.$joursRestants.' jours' : '-' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-action" onclick="ouvrirDetail(this)"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                                            <button class="btn btn-sm btn-action"><i class="bi bi-bell"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        Aucun emprunt trouvé
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <small class="text-muted">Affichage {{ $emprunts->count() }} sur {{ $total }} emprunts</small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled"><a class="page-link">«</a></li>
                            <li class="page-item active"><a class="page-link">1</a></li>
                            <li class="page-item"><a class="page-link">2</a></li>
                            <li class="page-item"><a class="page-link">3</a></li>
                            <li class="page-item"><a class="page-link">»</a></li>
                        </ul>
                    </nav>
                </div>

            </div>
        </div>

        {{-- Panneau détail (caché par défaut) --}}
        <div class="col-12 col-lg-4 d-none" id="panneau-detail">
            <div class="panneau-container">

                {{-- PARTIE FIXE HAUT --}}
                <div class="panneau-header">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="echeance proche">● Échéance proche</span>
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted">EM-002</small>
                            <button class="btn btn-sm btn-action" onclick="fermerDetail()">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 mt-2">
                        <div class="avatar-large" style="background:#f59e0b">SB</div>
                        <div>
                            <h6 class="fw-bold mb-0">Sophie B.</h6>
                            <div class="d-flex gap-1 mt-1">
                                <span class="badge-type-emprunt externe">Alt. Externe</span>
                                <small class="text-muted">sophie.b@efel.fr</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- PARTIE SCROLLABLE --}}
                <div class="panneau-body">

                    {{-- Alerte échéance --}}
                    <div class="alerte-echeance mt-3">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-clock text-warning"></i>
                            <div>
                                <div class="fw-semibold">Fin de formation dans 3 jours</div>
                                <div class="text-muted small">Le matériel doit être restitué le 18/06/2024</div>
                            </div>
                        </div>
                    </div>

                    {{-- Statut de restitution --}}
                    <div class="section-detail mt-3">
                        <div class="section-detail-titre">
                            <i class="bi bi-arrow-repeat"></i> Statut de restitution
                        </div>
                        <div class="timeline-restitution mt-3">
                            <div class="timeline-step done">
                                <div class="timeline-circle done">
                                    <i class="bi bi-check"></i>
                                </div>
                                <div class="timeline-label">Emprunté</div>
                            </div>
                            <div class="timeline-line done"></div>
                            <div class="timeline-step">
                                <div class="timeline-circle attente">
                                    <i class="bi bi-clock"></i>
                                </div>
                                <div class="timeline-label">En attente</div>
                            </div>
                            <div class="timeline-line"></div>
                            <div class="timeline-step">
                                <div class="timeline-circle rendu">
                                    <i class="bi bi-check-all"></i>
                                </div>
                                <div class="timeline-label">Rendu</div>
                            </div>
                        </div>
                    </div>

                    {{-- Matériel emprunté --}}
                    <div class="section-detail mt-3">
                        <div class="section-detail-titre">
                            <i class="bi bi-laptop"></i> Matériel emprunté
                        </div>
                        <div class="materiel-card mt-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icone-appareil">
                                    <i class="bi bi-laptop"></i>
                                </div>
                                <div class="flex-fill">
                                    <div class="fw-semibold">Lenovo ThinkPad L15</div>
                                    <div class="text-muted small">PC-003 · LN2023-L15G3-003</div>
                                </div>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Période d'emprunt --}}
                    <div class="section-detail mt-3 mb-3">
                        <div class="section-detail-titre">
                            <i class="bi bi-calendar"></i> Période d'emprunt
                        </div>
                        <div class="row g-2 mt-1">
                            <div class="col-6">
                                <div class="spec-box">
                                    <div class="spec-label">DÉBUT</div>
                                    <div class="spec-value">18/09/2023</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="spec-box">
                                    <div class="spec-label">FIN PRÉVUE</div>
                                    <div class="spec-value text-warning">18/06/2024</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- PARTIE FIXE BAS --}}
                <div class="panneau-footer">
                    <div class="d-flex gap-2 mb-2">
                        <button class="btn btn-success btn-sm flex-fill">
                            <i class="bi bi-check"></i> Valider retour
                        </button>
                        <button class="btn btn-outline-warning btn-sm flex-fill">
                            <i class="bi bi-bell"></i> Relancer
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm flex-fill">
                            <i class="bi bi-calendar-plus"></i> Prolonger
                        </button>
                        <button class="btn btn-outline-danger btn-sm flex-fill">
                            <i class="bi bi-arrow-return-left"></i> Récupérer
                        </button>
                    </div>
                </div>

            </div>
        </div>

    </div> {{-- fin row --}}

    {{-- Modal ajout emprunt --}}
    <div class="modal fade" id="modalAjoutEmprunt" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle me-2"></i>Nouvel emprunt
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" action="{{ route('emprunts.store') }}">
                    @csrf
                    <div class="modal-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}
                            </div>
                        @endif
                        <div class="row g-3">

                            {{-- Étudiant --}}
                            <div class="col-md-12" style="position: relative;">
                                <label class="form-label fw-semibold">Étudiant *</label>
                                <input type="text"
                                       id="champ-etudiant"
                                       class="form-control"
                                       placeholder="Rechercher un étudiant..."
                                       autocomplete="off"
                                       data-etudiants-url="{{ route('search.etudiants') }}">
                                <input type="hidden" name="etudiant_id" id="etudiant-id" required>
                                <div id="suggestions-etudiant" class="suggestions-container d-none"></div>
                            </div>

                            {{-- Type de matériel --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Type de matériel *</label>
                                <select name="materiel_type" id="materiel_type" class="form-select" required data-base-url="{{ url('/materiel-disponible') }}">
                                    <option value="">Choisir...</option>
                                    <option value="pc-portable">PC Portable</option>
                                    <option value="mini-pc">Mini PC</option>
                                    <option value="ecran">Écran</option>
                                    <option value="imprimante">Imprimante</option>
                                    <option value="clavier">Clavier</option>
                                    <option value="souris">Souris</option>
                                    <option value="casque">Casque</option>
                                </select>
                            </div>

                            {{-- Matériel (rempli dynamiquement) --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Matériel *</label>
                                <select name="materiel_id" id="materiel_id" class="form-select" required disabled>
                                    <option value="">Choisir d'abord un type</option>
                                </select>
                            </div>

                            {{-- Dates --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Date de début *</label>
                                <input type="date" name="date_debut" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Date de fin prévue *</label>
                                <input type="date" name="date_fin_prevue" class="form-control" required>
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
    <script src="{{ asset('js/emprunts-ajout.js') }}"></script>
@endsection
