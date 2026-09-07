{{-- resources/views/activite/tickets.blade.php --}}

@extends('layouts.app')

@section('title', 'Tickets')

@section('styles')
    <link href="{{ asset('css/materiel.css') }}" rel="stylesheet">
    <link href="{{ asset('css/affectations.css') }}" rel="stylesheet">
    <link href="{{ asset('css/tickets.css') }}" rel="stylesheet">
@endsection

@section('content')

    {{-- Fil d'ariane + Titre --}}
    <div class="mb-4">
        <small class="text-muted">Dashboard > Activité > Tickets</small>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <h2 class="fw-bold mb-0">🎫 Tickets</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary">
                    <i class="bi bi-download"></i> Exporter
                </button>
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalAjoutTicket">
                    <i class="bi bi-plus"></i> Nouveau ticket
                </button>
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

        <div class="col-12 col-md-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f3f4f6">
                    <i class="bi bi-lock" style="color:#6b7280"></i>
                </div>
                <div>
                    <div class="stat-label">Fermés</div>
                    <div class="stat-number">{{ $fermes }}</div>
                </div>
            </div>
        </div>

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
                <button type="submit" name="statut" value="ferme" class="btn btn-filtre {{ request('statut') == 'ferme' ? 'active-filtre' : '' }}"><span class="point-rouge"></span> Fermés <span class="badge-count">{{ $fermes }}</span></button>
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

                                    $technicien = $ticket->technicien;

                                    // Le badge "type" n'a que 2 styles CSS : incident / demande
                                    $typeClasse = $ticket->type === 'incident' ? 'incident' : 'demande';
                                    $typeLabel  = $ticket->type === 'incident' ? 'Incident' : 'Demande';

                                    // La classe CSS du statut utilise un tiret, pas un underscore
                                    $statutClasse = str_replace('_', '-', $ticket->statut);
                                    $statutLabels = [
                                        'ouvert'   => 'Ouvert',
                                        'en_cours' => 'En cours',
                                        'resolu'   => 'Résolu',
                                        'ferme'    => 'Fermé',
                                    ];

                                    $materielNom = $ticket->materiel->nom ?? null;
                                @endphp
                                <tr>
                                    <td class="text-muted small">TK-{{ str_pad($ticket->id, 3, '0', STR_PAD_LEFT) }}</td>
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
                                            <button class="btn btn-sm btn-action" onclick="ouvrirDetail(this)"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-action"><i class="bi bi-person-plus"></i></button>
                                            <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
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
        <div class="col-12 col-lg-4 d-none" id="panneau-detail">
            <div class="panneau-container">

                {{-- PARTIE FIXE HAUT --}}
                <div class="panneau-header">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge-ticket-type incident">Incident</span>
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted">TK-001</small>
                            <button class="btn btn-sm btn-action" onclick="fermerDetail()">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mb-2">
                        <span class="badge-ticket-type incident">Incident</span>
                        <span class="badge-priorite haute">Haute priorité</span>
                    </div>
                    <h6 class="fw-bold mb-1">PC portable ne démarre plus</h6>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge-ticket-statut ouvert">● Ouvert</span>
                        <small class="text-muted">18 juin 2024 · 09h14</small>
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
                                <span class="info-value">M. Leclerc</span>
                            </div>
                            <div class="info-ligne">
                                <i class="bi bi-building"></i>
                                <span class="info-label">Service</span>
                                <span class="info-value">
                                    <span class="badge-service pedagogie">Pédagogie</span>
                                </span>
                            </div>
                            <div class="info-ligne">
                                <i class="bi bi-person-badge"></i>
                                <span class="info-label">Assigné à</span>
                                <span class="info-value text-muted fst-italic">Non assigné</span>
                            </div>
                            <div class="info-ligne">
                                <i class="bi bi-calendar"></i>
                                <span class="info-label">Créé le</span>
                                <span class="info-value">18/06/2024</span>
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
                                    <i class="bi bi-laptop"></i>
                                </div>
                                <div class="flex-fill">
                                    <div class="fw-semibold">Dell Latitude 5540</div>
                                    <div class="text-muted small">PC-001 · LT2023-5540-001</div>
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
                        <div class="description-ticket mt-2">
                            Le PC portable assigné à mon bureau ne s'allume plus depuis ce matin. L'écran reste noir même après plusieurs tentatives de redémarrage.
                        </div>
                    </div>

                </div>

                {{-- PARTIE FIXE BAS --}}
                <div class="panneau-footer">
                    <div class="d-flex gap-2 mb-2">
                        <button class="btn btn-primary btn-sm flex-fill">
                            <i class="bi bi-person-plus"></i> Assigner
                        </button>
                        <button class="btn btn-outline-secondary btn-sm flex-fill">
                            <i class="bi bi-pencil"></i> Modifier
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-success btn-sm flex-fill">
                            <i class="bi bi-check"></i> Résoudre
                        </button>
                        <button class="btn btn-outline-danger btn-sm flex-fill">
                            <i class="bi bi-x"></i> Fermer
                        </button>
                    </div>
                </div>

            </div>
        </div>

    </div> {{-- fin row --}}

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
                <form method="POST" action="{{ route('tickets.store') }}">
                    @csrf
                    <div class="modal-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}
                            </div>
                        @endif
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
                                    <option value="incident">Incident</option>
                                    <option value="affectation">Demande d'affectation</option>
                                    <option value="emprunt">Demande d'emprunt</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Priorité *</label>
                                <select name="priorite" class="form-select" required>
                                    <option value="normale" selected>Normale</option>
                                    <option value="haute">Haute</option>
                                    <option value="basse">Basse</option>
                                </select>
                            </div>

                            {{-- Numéro de série (uniquement pertinent pour un incident) --}}
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Numéro de série *</label>
                                <input type="text"
                                       name="numero_serie"
                                       id="numero_serie"
                                       class="form-control"
                                       placeholder="ex: LT2023-5540-001"
                                       required>
                                <small class="text-muted" id="numero-serie-aide">
                                    Renseignez le numéro de série du matériel concerné par l'incident.
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
    <script src="{{ asset('js/tickets-ajout.js') }}"></script>
@endsection