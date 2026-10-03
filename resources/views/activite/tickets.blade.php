{{-- resources/views/activite/tickets.blade.php --}}

@extends('layouts.app')

@section('title', 'Tickets')

@section('styles')
    <link href="{{ asset('css/materiel.css') }}" rel="stylesheet">
    <link href="{{ asset('css/affectations.css') }}" rel="stylesheet">
    <link href="{{ asset('css/tickets.css') }}" rel="stylesheet">
@endsection

@section('content')

    {{-- Titre --}}
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mt-2">
            <h2 class="fw-bold mb-0">{{ $estAdmin ? 'Tickets' : 'Mes tickets' }}</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary">
                    <i class="bi bi-download"></i> Exporter
                </button>
                @unless ($estAdmin)
                    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalAjoutTicket">
                        <i class="bi bi-plus"></i> Nouveau ticket
                    </button>
                @endunless
            </div>
        </div>
    </div>

    {{-- Les 6 cartes statistiques --}}
    <div class="row g-3 mb-4">

        <div class="col-12 col-md-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0f4ff">
                    <i class="bi bi-ticket" style="color:#3b82f6"></i>
                </div>
                <div>
                    <div class="stat-label">Total tickets</div>
                    <div class="stat-number">{{ $total }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon" style="background:#eff6ff">
                    <i class="bi bi-circle" style="color:#3b82f6"></i>
                </div>
                <div>
                    <div class="stat-label">Ouverts</div>
                    <div class="stat-number">{{ $ouverts }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff7e6">
                    <i class="bi bi-arrow-repeat" style="color:#f59e0b"></i>
                </div>
                <div>
                    <div class="stat-label">En cours</div>
                    <div class="stat-number">{{ $enCours }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0fff4">
                    <i class="bi bi-check-circle" style="color:#22c55e"></i>
                </div>
                <div>
                    <div class="stat-label">Résolus</div>
                    <div class="stat-number">{{ $resolus }}</div>
                </div>
            </div>
        </div>

        @if ($estAdmin)
        <div class="col-12 col-md-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff0f0">
                    <i class="bi bi-fire" style="color:#ef4444"></i>
                </div>
                <div>
                    <div class="stat-label">Priorité haute</div>
                    <div class="stat-number">{{ $prioriteHaute }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff1f2">
                    <i class="bi bi-x-circle" style="color:#e11d48"></i>
                </div>
                <div>
                    <div class="stat-label">Refusés</div>
                    <div class="stat-number">{{ $refuses }}</div>
                </div>
            </div>
        </div>
        @endif

    </div>

    {{-- Onglets filtres --}}
    <div class="bg-white rounded-3 p-3 mb-4 shadow-sm">
        <form method="GET" action="{{ route('tickets.index') }}" class="d-flex align-items-center gap-3 flex-wrap">

            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       class="form-control border-start-0"
                       placeholder="Rechercher un ticket, demandeur, matériel...">
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button type="submit" name="statut" value="" class="btn btn-filtre {{ request('statut') == '' ? 'active-filtre' : '' }}">Tous <span class="badge-count">{{ $total }}</span></button>
                <button type="submit" name="statut" value="ouvert" class="btn btn-filtre {{ request('statut') == 'ouvert' ? 'active-filtre' : '' }}"><span class="point-bleu"></span> Ouverts <span class="badge-count">{{ $ouverts }}</span></button>
                <button type="submit" name="statut" value="en_cours" class="btn btn-filtre {{ request('statut') == 'en_cours' ? 'active-filtre' : '' }}"><span class="point-orange"></span> En cours <span class="badge-count">{{ $enCours }}</span></button>
                <button type="submit" name="statut" value="resolu" class="btn btn-filtre {{ request('statut') == 'resolu' ? 'active-filtre' : '' }}"><span class="point-vert"></span> Résolus <span class="badge-count">{{ $resolus }}</span></button>
                <button type="submit" name="statut" value="refuse" class="btn btn-filtre {{ request('statut') == 'refuse' ? 'active-filtre' : '' }}"><span class="point-rouge"></span> Refusés <span class="badge-count">{{ $refuses }}</span></button>
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
                                <th>TITRE</th>
                                <th>TYPE</th>
                                <th>SERVICE</th>
                                <th>DEMANDEUR</th>
                                <th>ASSIGNÉ À</th>
                                <th>STATUT</th>
                                <th>PRIORITÉ</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse ($tickets as $ticket)
                                @php
                                    $demandeur = $ticket->demandeur;
                                    $nomDemandeur = $demandeur->name ?? 'Inconnu';
                                    $mots = explode(' ', trim($nomDemandeur));
                                    $initialesDemandeur = strtoupper(substr($mots[0] ?? '', 0, 1) . substr($mots[1] ?? '', 0, 1));

                                    // Service : uniquement si le demandeur est un collaborateur (Personnel)
                                    $service = optional($demandeur->personnel ?? null)->service;
                                    $typeContrat = optional($demandeur->personnel ?? null)->type_contrat;

                                    $technicien = $ticket->technicien;

                                    $typeTicket = $ticket->incident
                                        ? 'incident'
                                        : $ticket->demande?->type_demande;

                                    // Le badge "type" n'a que 2 styles CSS : incident / demande
                                    $typeClasse = $typeTicket === 'incident' ? 'incident' : 'demande';
                                    $typeLabel  = $typeTicket === 'incident' ? 'Incident' : 'Demande';

                                    // La classe CSS du statut utilise un tiret, pas un underscore
                                    $statutClasse = str_replace('_', '-', $ticket->statut);
                                    $statutLabels = [
                                        'ouvert'   => 'Ouvert',
                                        'en_cours' => 'En cours',
                                        'resolu'   => 'Résolu',
                                        'ferme'    => 'Fermé',
                                        'refuse'   => 'Refusé',
                                    ];

                                    $materiel = $ticket->materiel;
                                    $materielSpecifique = match ($materiel->type_materiel ?? null) {
                                        'pc_portable' => $materiel->pcPortable,
                                        'mini_pc' => $materiel->miniPc,
                                        'ecran' => $materiel->ecran,
                                        'imprimante' => $materiel->imprimante,
                                        'clavier' => $materiel->clavier,
                                        'souris' => $materiel->souris,
                                        'casque' => $materiel->casque,
                                        default => null,
                                    };
                                    $materielNom = $materiel->nom ?? null;
                                    $materielSerie = $materielSpecifique->numero_serie ?? null;
                                    $materielType = $materiel->type_materiel ?? null;
                                    $materielTypeLabel = match ($materielType) {
                                        'pc_portable' => 'PC portable',
                                        'mini_pc' => 'Mini PC',
                                        'ecran' => 'Ecran',
                                        'imprimante' => 'Imprimante',
                                        'clavier' => 'Clavier',
                                        'souris' => 'Souris',
                                        'casque' => 'Casque',
                                        default => 'Aucun matériel lié',
                                    };
                                    $materielIcon = match ($materielType) {
                                        'pc_portable' => 'bi-laptop',
                                        'mini_pc' => 'bi-pc',
                                        'ecran' => 'bi-display',
                                        'imprimante' => 'bi-printer',
                                        'clavier' => 'bi-keyboard',
                                        'souris' => 'bi-mouse',
                                        'casque' => 'bi-headphones',
                                        default => 'bi-box',
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $ticket->titre }}</div>
                                        <div class="text-muted small">
                                            {{ $ticket->created_at->format('d/m/Y') }}
                                            @if($materielNom) · {{ $materielNom }} @endif
                                        </div>
                                    </td>
                                    <td><span class="badge-ticket-type {{ $typeClasse }}">{{ $typeLabel }}</span></td>
                                    <td>
                                        @if($service)
                                            <span class="badge-service">{{ $service }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <div class="avatar-sm" style="background:#3b82f6">{{ $initialesDemandeur }}</div>
                                            <span>{{ $nomDemandeur }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($technicien)
                                            @php
                                                $motsTech = explode(' ', trim($technicien->name));
                                                $initialesTech = strtoupper(substr($motsTech[0] ?? '', 0, 1) . substr($motsTech[1] ?? '', 0, 1));
                                            @endphp
                                            <div class="d-flex align-items-center gap-1">
                                                <div class="avatar-sm" style="background:#6366f1">{{ $initialesTech }}</div>
                                                <span>{{ $technicien->name }}</span>
                                            </div>
                                        @else
                                            <span class="text-muted fst-italic">Non assigné</span>
                                        @endif
                                    </td>
                                    <td><span class="badge-ticket-statut {{ $statutClasse }}">● {{ $statutLabels[$ticket->statut] ?? $ticket->statut }}</span></td>
                                    <td><span class="badge-priorite {{ $ticket->priorite }}">{{ ucfirst($ticket->priorite) }}</span></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button type="button"
                                                    class="btn btn-sm btn-action"
                                                    onclick="ouvrirDetailTicket(this)"
                                                    data-titre="{{ $ticket->titre }}"
                                                    data-type-label="{{ $typeLabel }}"
                                                    data-type-classe="{{ $typeClasse }}"
                                                    data-type="{{ $typeTicket }}"
                                                    data-statut="{{ $ticket->statut }}"
                                                    data-est-assigne="{{ $ticket->technicien_id ? '1' : '0' }}"
                                                    data-peut-traiter="{{ $ticket->statut === 'en_cours' && $ticket->technicien_id === auth()->id() ? '1' : '0' }}"
                                                    data-peut-repondre="{{ $ticket->incident && $ticket->statut === 'en_cours' && $ticket->technicien_id === auth()->id() ? '1' : '0' }}"
                                                    data-peut-resoudre="{{ $ticket->incident && $ticket->statut === 'en_cours' && $ticket->technicien_id === auth()->id() && filled($ticket->incident->reponse_admin) ? '1' : '0' }}"
                                                    data-statut-label="{{ $statutLabels[$ticket->statut] ?? $ticket->statut }}"
                                                    data-statut-classe="{{ $statutClasse }}"
                                                    data-priorite="{{ $ticket->priorite }}"
                                                    data-priorite-label="{{ ucfirst($ticket->priorite) }}"
                                                    data-date="{{ $ticket->created_at->format('d/m/Y H:i') }}"
                                                    data-demandeur="{{ $nomDemandeur }}"
                                                    data-type-contrat="{{ $typeContrat }}"
                                                    data-service="{{ $service ?: '-' }}"
                                                    data-technicien="{{ $technicien->name ?? 'Non assigné' }}"
                                                    data-materiel-nom="{{ $materielNom ?: 'Aucun matériel lié' }}"
                                                    data-materiel-type="{{ $materielTypeLabel }}"
                                                    data-materiel-serie="{{ $materielSerie ?: '-' }}"
                                                    data-materiel-icon="{{ $materielIcon }}"
                                                    data-description="{{ $ticket->description ?: '-' }}"
                                                    data-reponse-admin="{{ $ticket->incident?->reponse_admin ?: '' }}"
                                                    data-date-reponse="{{ $ticket->incident?->date_reponse?->format('d/m/Y H:i') ?: '' }}"
                                                    data-motif-refus="{{ $ticket->demande?->motif_refus ?: '' }}"
                                                    data-date-refus="{{ $ticket->demande?->date_refus?->format('d/m/Y H:i') ?: '' }}"
                                                    data-peut-refuser="{{ $ticket->demande && $ticket->statut === 'en_cours' && $ticket->technicien_id === auth()->id() ? '1' : '0' }}"
                                                    data-assign-url="{{ route('tickets.assigner', $ticket) }}"
                                                    data-resolve-url="{{ route('tickets.resoudre', $ticket) }}"
                                                    data-affectation-url="{{ route('tickets.affectation.store', $ticket) }}"
                                                    data-emprunt-url="{{ route('tickets.emprunt.store', $ticket) }}"
                                                    data-refuse-url="{{ route('tickets.refuser', $ticket) }}"
                                                    data-reponse-url="{{ route('tickets.repondre', $ticket) }}"
                                                    data-ticket-id="{{ $ticket->id }}">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            @if ($estAdmin)
                                                <button type="button"
                                                        class="btn btn-sm btn-action"
                                                        onclick="ouvrirConfirmationAssignationTicket(this)"
                                                        data-url="{{ route('tickets.assigner', $ticket) }}"
                                                        data-titre="{{ $ticket->titre }}"
                                                        @disabled($ticket->technicien_id || in_array($ticket->statut, ['resolu', 'refuse'], true))
                                                        aria-label="Assigner le ticket">
                                                    <i class="bi bi-person-plus"></i>
                                                </button>
                                                @if ($ticket->incident)
                                                    <button type="button"
                                                            class="btn btn-sm btn-action"
                                                            onclick="ouvrirConfirmationResolutionTicket(this)"
                                                            data-url="{{ route('tickets.resoudre', $ticket) }}"
                                                            data-titre="{{ $ticket->titre }}"
                                                            @disabled(! ($ticket->statut === 'en_cours' && $ticket->technicien_id === auth()->id() && filled($ticket->incident->reponse_admin)))
                                                            aria-label="Resoudre le ticket">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        Aucun ticket trouvé
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <small class="text-muted">Affichage {{ $tickets->count() }} sur {{ $total }} tickets</small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled"><a class="page-link">«</a></li>
                            <li class="page-item active"><a class="page-link">1</a></li>
                            <li class="page-item"><a class="page-link">2</a></li>
                            <li class="page-item"><a class="page-link">3</a></li>
                            <li class="page-item"><a class="page-link">...</a></li>
                            <li class="page-item"><a class="page-link">5</a></li>
                            <li class="page-item"><a class="page-link">»</a></li>
                        </ul>
                    </nav>
                </div>

            </div>
        </div>

        {{-- Panneau détail (caché par défaut) --}}
        <div class="panneau-backdrop d-none" id="panneau-detail-backdrop" onclick="fermerDetailTicket()"></div>

        <div class="d-none" id="panneau-detail" aria-hidden="true">
            <div class="panneau-container">

                {{-- PARTIE FIXE HAUT --}}
                <div class="panneau-header">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge-ticket-type incident" id="detail-ticket-type-header">-</span>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-action" onclick="fermerDetailTicket()">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mb-2">
                        <span class="badge-ticket-type incident" id="detail-ticket-type">-</span>
                        <span class="badge-priorite haute" id="detail-ticket-priorite">-</span>
                    </div>
                    <h6 class="fw-bold mb-1" id="detail-ticket-titre">-</h6>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge-ticket-statut ouvert" id="detail-ticket-statut">● Ouvert</span>
                        <small class="text-muted" id="detail-ticket-date">-</small>
                    </div>
                </div>

                {{-- PARTIE SCROLLABLE --}}
                <div class="panneau-body">

                    {{-- Détails --}}
                    <div class="section-detail mt-3">
                        <div class="section-detail-titre">
                            <i class="bi bi-info-circle"></i> Détails
                        </div>
                        <div class="mt-2">
                            <div class="info-ligne">
                                <i class="bi bi-person"></i>
                                <span class="info-label">Demandeur</span>
                                <span class="info-value" id="detail-ticket-demandeur">-</span>
                            </div>
                            <div class="info-ligne">
                                <i class="bi bi-building"></i>
                                <span class="info-label">Service</span>
                                <span class="info-value">
                                    <span class="badge-service" id="detail-ticket-service">-</span>
                                </span>
                            </div>
                            <div class="info-ligne">
                                <i class="bi bi-person-badge"></i>
                                <span class="info-label">Assigné à</span>
                                <span class="info-value text-muted fst-italic" id="detail-ticket-technicien">-</span>
                            </div>
                            <div class="info-ligne">
                                <i class="bi bi-calendar"></i>
                                <span class="info-label">Créé le</span>
                                <span class="info-value" id="detail-ticket-date-info">-</span>
                            </div>
                        </div>
                    </div>

                    {{-- Matériel concerné --}}
                    <div class="section-detail mt-3">
                        <div class="section-detail-titre">
                            <i class="bi bi-laptop"></i> Matériel concerné
                        </div>
                        <div class="materiel-card mt-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icone-appareil">
                                    <i class="bi bi-box" id="detail-ticket-materiel-icon"></i>
                                </div>
                                <div class="flex-fill">
                                    <div class="fw-semibold" id="detail-ticket-materiel-nom">-</div>
                                    <div class="text-muted small" id="detail-ticket-materiel-info">-</div>
                                </div>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="section-detail mt-3 mb-3">
                        <div class="section-detail-titre">
                            <i class="bi bi-chat-text"></i> Description
                        </div>
                        <div class="description-ticket mt-2" id="detail-ticket-description">-</div>
                    </div>

                    <div class="section-detail mt-3 mb-3 d-none" id="detail-ticket-reponse-section">
                        <div class="section-detail-titre text-primary">
                            <i class="bi bi-chat-left-text"></i> Réponse du support IT
                        </div>
                        <div class="reponse-ticket mt-2">
                            <div id="detail-ticket-reponse-admin">-</div>
                            <small class="text-muted d-block mt-2" id="detail-ticket-date-reponse"></small>
                        </div>
                    </div>

                    <div class="section-detail mt-3 mb-3 d-none" id="detail-ticket-refus-section">
                        <div class="section-detail-titre text-danger">
                            <i class="bi bi-x-circle"></i> Demande refusée
                        </div>
                        <div class="refus-ticket mt-2">
                            <div class="fw-semibold mb-1">Motif du refus</div>
                            <div id="detail-ticket-motif-refus">-</div>
                            <small class="text-muted d-block mt-2" id="detail-ticket-date-refus"></small>
                        </div>
                    </div>

                </div>

                {{-- PARTIE FIXE BAS --}}
                @if ($estAdmin)
                <div class="panneau-footer">
                    <div class="d-flex gap-2 mb-2">
                        <button type="button"
                                class="btn btn-primary btn-sm flex-fill"
                                id="btn-assigner-ticket-detail"
                                onclick="ouvrirConfirmationAssignationDepuisDetail()">
                            <i class="bi bi-person-plus"></i> Assigner
                        </button>
                        <button type="button"
                                class="btn btn-outline-secondary btn-sm flex-fill d-none"
                                id="btn-traiter-demande-ticket"
                                onclick="ouvrirTraitementDemandeTicket()">
                            <i class="bi bi-box-arrow-in-right" id="icone-traiter-demande-ticket"></i>
                            <span id="libelle-traiter-demande-ticket">Traiter la demande</span>
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button"
                                class="btn btn-success btn-sm flex-fill"
                                id="btn-resoudre-ticket-detail"
                                onclick="ouvrirConfirmationResolutionDepuisDetail()">
                            <i class="bi bi-check"></i> Résoudre
                        </button>
                        <button type="button"
                                class="btn btn-outline-danger btn-sm flex-fill d-none"
                                id="btn-refuser-ticket-detail"
                                onclick="ouvrirRefusDepuisDetail()">
                            <i class="bi bi-x-circle"></i> Refuser
                        </button>
                    </div>
                </div>
                @endif

            </div>
        </div>

    </div> {{-- fin row --}}

    @if ($estAdmin)
    {{-- Modal confirmation assignation --}}
    <div class="modal fade" id="modalConfirmationAssignationTicket" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="form-assigner-ticket" class="modal-content">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-check me-2 text-primary"></i>Assigner le ticket
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">
                        Voulez-vous vous assigner le ticket
                        <strong id="confirmation-assignation-ticket">sélectionné</strong> ?
                    </p>
                    <p class="text-muted small mb-0">
                        Le ticket passera au statut En cours et vous serez indiqué comme responsable.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-person-check me-1"></i> Confirmer l'assignation
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if ($estAdmin)
    {{-- Modal confirmation resolution --}}
    <div class="modal fade" id="modalConfirmationResolutionTicket" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="form-resoudre-ticket" class="modal-content">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-check-circle me-2 text-success"></i>Résoudre le ticket
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">
                        Voulez-vous marquer le ticket
                        <strong id="confirmation-resolution-ticket">sélectionné</strong>
                        comme résolu ?
                    </p>
                    <p class="text-muted small mb-0">
                        Le demandeur verra que son ticket a été résolu.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i> Confirmer la résolution
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if ($estAdmin)
    {{-- Modal de réponse à un incident --}}
    <div class="modal fade" id="modalReponseIncident" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="form-reponse-incident" class="modal-content">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-chat-left-text me-2 text-primary"></i>
                        <span id="titre-modal-reponse-incident">Répondre à l'incident</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        Réponse concernant <strong id="reponse-incident-titre-ticket">l'incident sélectionné</strong>.
                    </p>
                    <label for="reponse-admin-ticket" class="form-label fw-semibold">Réponse du support IT *</label>
                    <textarea name="reponse_admin"
                              id="reponse-admin-ticket"
                              class="form-control"
                              rows="5"
                              minlength="5"
                              maxlength="2000"
                              required
                              placeholder="Indiquez au demandeur la marche à suivre..."></textarea>
                    <small class="text-muted">Cette réponse sera visible dans Mes tickets et envoyée par e-mail.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i>
                        <span id="libelle-envoi-reponse-incident">Envoyer la réponse</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if ($estAdmin)
    {{-- Modal de refus d'une demande --}}
    <div class="modal fade" id="modalRefusTicket" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="form-refuser-ticket" class="modal-content">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-x-circle me-2 text-danger"></i>Refuser la demande
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        Indiquez pourquoi la demande <strong id="refus-ticket-titre">sélectionnée</strong> est refusée.
                    </p>
                    <label for="motif-refus-ticket" class="form-label fw-semibold">Motif du refus *</label>
                    <textarea name="motif_refus"
                              id="motif-refus-ticket"
                              class="form-control"
                              rows="4"
                              minlength="5"
                              maxlength="1000"
                              required
                              placeholder="Expliquez clairement le motif au demandeur..."></textarea>
                    <small class="text-muted">Ce motif sera visible sur le compte du demandeur.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i> Confirmer le refus
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if ($estAdmin)
    {{-- Formulaire d'affectation depuis un ticket --}}
    <div class="modal fade" id="modalAffectationDepuisTicket" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="POST"
                  id="form-affectation-depuis-ticket"
                  class="modal-content"
                  data-materiel-base-url="{{ url('/materiel-disponible') }}"
                  data-reouvrir="{{ old('traitement_ticket') === 'affectation' && old('ticket_id') && $errors->any() ? '1' : '0' }}"
                  data-ancien-ticket-id="{{ old('ticket_id') }}"
                  data-ancien-type="{{ old('materiel_type') }}"
                  data-ancien-materiel="{{ old('materiel_numero_serie') }}"
                  data-ancienne-date-debut="{{ old('date_debut') }}"
                  data-ancienne-date-fin="{{ old('date_fin') }}">
                @csrf
                <input type="hidden" name="traitement_ticket" value="affectation">
                <input type="hidden" name="ticket_id" id="ticket-affectation-id" value="{{ old('ticket_id') }}">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-link-45deg me-2 text-primary"></i>Créer l'affectation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div id="ticket-affectation-erreur" class="alert alert-danger d-none" role="alert"></div>
                    @if (old('traitement_ticket') === 'affectation' && $errors->any())
                        <div class="alert alert-danger" role="alert">
                            {{ $errors->first() }}
                        </div>
                    @endif
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Collaborateur</label>
                            <input type="text" id="ticket-affectation-demandeur" class="form-control" readonly>
                            <small class="text-muted">Le collaborateur est déterminé par le demandeur du ticket.</small>
                        </div>
                        <div class="col-md-6">
                            <label for="ticket-affectation-materiel-type" class="form-label fw-semibold">Type de matériel *</label>
                            <select name="materiel_type" id="ticket-affectation-materiel-type" class="form-select" required>
                                <option value="">Choisir...</option>
                                <option value="pc-portable">PC portable</option>
                                <option value="mini-pc">Mini PC</option>
                                <option value="ecran">Écran</option>
                                <option value="clavier">Clavier</option>
                                <option value="souris">Souris</option>
                                <option value="casque">Casque</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="ticket-affectation-materiel" class="form-label fw-semibold">Matériel *</label>
                            <select name="materiel_numero_serie" id="ticket-affectation-materiel" class="form-select" required disabled>
                                <option value="">Choisir d'abord un type</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="ticket-affectation-date-debut" class="form-label fw-semibold">Date de début *</label>
                            <input type="date" name="date_debut" id="ticket-affectation-date-debut" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-6" id="ticket-affectation-bloc-date-fin">
                            <label for="ticket-affectation-date-fin" class="form-label fw-semibold">Date de fin *</label>
                            <input type="date" name="date_fin" id="ticket-affectation-date-fin" class="form-control" min="{{ now()->toDateString() }}">
                            <small class="text-muted">Cette date ne sera pas demandée pour un CDI.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-confirmer-affectation-ticket" disabled>
                        <i class="bi bi-link-45deg me-1"></i> Créer l'affectation
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Formulaire d'emprunt depuis un ticket --}}
    <div class="modal fade" id="modalEmpruntDepuisTicket" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="POST"
                  id="form-emprunt-depuis-ticket"
                  class="modal-content"
                  data-materiel-base-url="{{ url('/materiel-disponible') }}"
                  data-reouvrir="{{ old('traitement_ticket') === 'emprunt' && old('ticket_id') && $errors->any() ? '1' : '0' }}"
                  data-ancien-ticket-id="{{ old('ticket_id') }}"
                  data-ancien-type="{{ old('materiel_type') }}"
                  data-ancien-materiel="{{ old('materiel_numero_serie') }}"
                  data-ancienne-date-debut="{{ old('date_debut') }}"
                  data-ancienne-date-fin="{{ old('date_fin_prevue') }}">
                @csrf
                <input type="hidden" name="traitement_ticket" value="emprunt">
                <input type="hidden" name="ticket_id" id="ticket-emprunt-id" value="{{ old('ticket_id') }}">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-arrow-left-right me-2 text-primary"></i>Créer l'emprunt
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div id="ticket-emprunt-erreur" class="alert alert-danger d-none" role="alert"></div>
                    @if (old('traitement_ticket') === 'emprunt' && $errors->any())
                        <div class="alert alert-danger" role="alert">
                            {{ $errors->first() }}
                        </div>
                    @endif
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Étudiant</label>
                            <input type="text" id="ticket-emprunt-demandeur" class="form-control" readonly>
                            <small class="text-muted">L'étudiant est déterminé par le demandeur du ticket.</small>
                        </div>
                        <div class="col-md-6">
                            <label for="ticket-emprunt-materiel-type" class="form-label fw-semibold">Type de matériel *</label>
                            <input type="text" class="form-control bg-light" value="PC portable" readonly>
                            <input type="hidden"
                                   name="materiel_type"
                                   id="ticket-emprunt-materiel-type"
                                   value="pc-portable">
                        </div>
                        <div class="col-md-6">
                            <label for="ticket-emprunt-materiel" class="form-label fw-semibold">Matériel *</label>
                            <select name="materiel_numero_serie" id="ticket-emprunt-materiel" class="form-select" required disabled>
                                <option value="">Chargement des PC portables...</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="ticket-emprunt-date-debut" class="form-label fw-semibold">Date de début *</label>
                            <input type="date" name="date_debut" id="ticket-emprunt-date-debut" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="ticket-emprunt-date-fin" class="form-label fw-semibold">Date de fin prévue *</label>
                            <input type="date" name="date_fin_prevue" id="ticket-emprunt-date-fin" class="form-control" min="{{ now()->toDateString() }}" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-confirmer-emprunt-ticket" disabled>
                        <i class="bi bi-arrow-left-right me-1"></i> Créer l'emprunt
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @unless ($estAdmin)
    {{-- Modal ajout ticket --}}
    <div class="modal fade" id="modalAjoutTicket" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle me-2"></i>Nouveau ticket
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" action="{{ route('tickets.store') }}" id="form-ajout-ticket">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">

                            {{-- Titre --}}
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Titre *</label>
                                <input type="text" name="titre" class="form-control" placeholder="ex: PC portable ne démarre plus" required>
                            </div>

                            {{-- Type et Priorité --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Type *</label>
                                <select name="type" id="type" class="form-select" required>
                                    <option value="">Choisir...</option>
                                    @if ($peutDeclarerIncident)
                                        <option value="incident" @selected(old('type') === 'incident')>Incident</option>
                                    @endif
                                    @if ($estEtudiant)
                                        <option value="emprunt" @selected(old('type') === 'emprunt')>Demande d'emprunt</option>
                                    @else
                                        <option value="affectation" @selected(old('type') === 'affectation')>Demande d'affectation</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Priorité *</label>
                                <select name="priorite" class="form-select" required>
                                    <option value="normale" selected>Normale</option>
                                    <option value="haute">Haute</option>
                                </select>
                            </div>

                            {{-- Matériel concerné (uniquement pertinent pour un incident) --}}
                            <div class="col-md-6" id="bloc-type-materiel-incident">
                                <label class="form-label fw-semibold">Type de matériel *</label>
                                <select name="type_materiel" id="type_materiel_incident" class="form-select" required>
                                    <option value="">Choisir...</option>
                                    @foreach ($typesMaterielIncident as $codeType => $libelleType)
                                        @php
                                            $materielDuType = $materielsIncident->firstWhere('type', $codeType);
                                        @endphp
                                        <option value="{{ $codeType }}"
                                                data-numero-serie="{{ $materielDuType['numero_serie'] ?? '' }}"
                                                @disabled(! $materielDuType)
                                                @selected(old('type_materiel') === $codeType)>
                                            {{ $libelleType }}{{ $materielDuType ? '' : ' (non affecté)' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6" id="bloc-numero-serie-incident">
                                <label class="form-label fw-semibold">Numéro de série *</label>
                                <input type="text"
                                       name="numero_serie"
                                       id="numero_serie"
                                       class="form-control"
                                       value="{{ old('numero_serie') }}"
                                       placeholder="Sélectionnez un type"
                                       readonly>
                                <small class="text-muted" id="numero-serie-aide">
                                    Le numéro de série est renseigné automatiquement.
                                </small>
                            </div>

                            {{-- Description --}}
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control" rows="4" placeholder="Décrivez le problème ou la demande..."></textarea>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" id="btn-enregistrer-ticket">
                            <i class="bi bi-save me-1"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endunless

@endsection

@section('scripts')
    <script src="{{ asset('js/materiel.js') }}"></script>
    <script src="{{ asset('js/tickets-ajout.js') }}"></script>
@endsection
