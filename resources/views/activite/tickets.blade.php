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
                <button class="btn btn-primary">
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
                    <div class="stat-number">32</div>
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
                    <div class="stat-number">7</div>
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
                    <div class="stat-number">4</div>
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
                    <div class="stat-number">18</div>
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
                    <div class="stat-number">3</div>
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
                    <div class="stat-number">5</div>
                </div>
            </div>
        </div>

    </div>

    {{-- Onglets filtres --}}
    <div class="bg-white rounded-3 p-3 mb-4 shadow-sm">
        <div class="d-flex align-items-center gap-3 flex-wrap">

            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text"
                       class="form-control border-start-0"
                       placeholder="Rechercher un ticket, demandeur, matériel...">
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-filtre active-filtre">Tous <span class="badge-count">32</span></button>
                <button class="btn btn-filtre"><span class="point-bleu"></span> Ouverts <span class="badge-count">7</span></button>
                <button class="btn btn-filtre"><span class="point-orange"></span> En cours <span class="badge-count">4</span></button>
                <button class="btn btn-filtre"><span class="point-vert"></span> Résolus <span class="badge-count">18</span></button>
                <button class="btn btn-filtre"><span class="point-rouge"></span> Fermés <span class="badge-count">3</span></button>
            </div>

        </div>
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

                            <tr>
                                <td class="text-muted small">TK-001</td>
                                <td>
                                    <div class="fw-semibold">PC portable ne démarre plus</div>
                                    <div class="text-muted small">18/06/2024 · PC-001</div>
                                </td>
                                <td><span class="badge-ticket-type incident">Incident</span></td>
                                <td><span class="badge-service pedagogie">Pédagogie</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="avatar-sm" style="background:#3b82f6">ML</div>
                                        <span>M. Leclerc</span>
                                    </div>
                                </td>
                                <td><span class="text-muted fst-italic">Non assigné</span></td>
                                <td><span class="badge-ticket-statut ouvert">● Ouvert</span></td>
                                <td><span class="badge-priorite haute">Haute</span></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-action" onclick="ouvrirDetail()"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-person-plus"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="text-muted small">TK-002</td>
                                <td>
                                    <div class="fw-semibold">Besoin d'un écran supplémentaire</div>
                                    <div class="text-muted small">17/06/2024 · Administration</div>
                                </td>
                                <td><span class="badge-ticket-type demande">Demande</span></td>
                                <td><span class="badge-service administration">Administration</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="avatar-sm" style="background:#22c55e">SB</div>
                                        <span>S. Bertrand</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="avatar-sm" style="background:#6366f1">TD</div>
                                        <span>Tech. Dupont</span>
                                    </div>
                                </td>
                                <td><span class="badge-ticket-statut en-cours">● En cours</span></td>
                                <td><span class="badge-priorite normale">Normale</span></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-action" onclick="ouvrirDetail()"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-person-plus"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="text-muted small">TK-003</td>
                                <td>
                                    <div class="fw-semibold">Imprimante salle 102 hors service</div>
                                    <div class="text-muted small">16/06/2024 · IM-002</div>
                                </td>
                                <td><span class="badge-ticket-type incident">Incident</span></td>
                                <td><span class="badge-service direction">Direction</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="avatar-sm" style="background:#f97316">AT</div>
                                        <span>A. Thomas</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="avatar-sm" style="background:#6366f1">TD</div>
                                        <span>Tech. Dupont</span>
                                    </div>
                                </td>
                                <td><span class="badge-ticket-statut ouvert">● Ouvert</span></td>
                                <td><span class="badge-priorite haute">Haute</span></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-action" onclick="ouvrirDetail()"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-person-plus"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="text-muted small">TK-004</td>
                                <td>
                                    <div class="fw-semibold">Installation logiciel comptabilité</div>
                                    <div class="text-muted small">15/06/2024 · PC-007</div>
                                </td>
                                <td><span class="badge-ticket-type demande">Demande</span></td>
                                <td><span class="badge-service administration">Comptabilité</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="avatar-sm" style="background:#8b5cf6">PM</div>
                                        <span>P. Morel</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="avatar-sm" style="background:#14b8a6">TM</div>
                                        <span>Tech. Martin</span>
                                    </div>
                                </td>
                                <td><span class="badge-ticket-statut resolu">● Résolu</span></td>
                                <td><span class="badge-priorite basse">Basse</span></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-action" onclick="ouvrirDetail()"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-person-plus"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="text-muted small">TK-005</td>
                                <td>
                                    <div class="fw-semibold">Connexion VPN impossible</div>
                                    <div class="text-muted small">15/06/2024 · PC-003</div>
                                </td>
                                <td><span class="badge-ticket-type incident">Incident</span></td>
                                <td><span class="badge-service rh">RH</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="avatar-sm" style="background:#14b8a6">CK</div>
                                        <span>C. Klein</span>
                                    </div>
                                </td>
                                <td><span class="text-muted fst-italic">Non assigné</span></td>
                                <td><span class="badge-ticket-statut ouvert">● Ouvert</span></td>
                                <td><span class="badge-priorite haute">Haute</span></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-action" onclick="ouvrirDetail()"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-person-plus"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="text-muted small">TK-006</td>
                                <td>
                                    <div class="fw-semibold">Mise à jour Windows bloquée</div>
                                    <div class="text-muted small">14/06/2024 · PC-006</div>
                                </td>
                                <td><span class="badge-ticket-type incident">Incident</span></td>
                                <td><span class="badge-service pedagogie">Pédagogie</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="avatar-sm" style="background:#ec4899">LR</div>
                                        <span>L. Richard</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="avatar-sm" style="background:#6366f1">TD</div>
                                        <span>Tech. Dupont</span>
                                    </div>
                                </td>
                                <td><span class="badge-ticket-statut en-cours">● En cours</span></td>
                                <td><span class="badge-priorite normale">Normale</span></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-action" onclick="ouvrirDetail()"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-person-plus"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="text-muted small">TK-007</td>
                                <td>
                                    <div class="fw-semibold">Demande nouveau clavier ergonomique</div>
                                    <div class="text-muted small">13/06/2024 · CL-004</div>
                                </td>
                                <td><span class="badge-ticket-type demande">Demande</span></td>
                                <td><span class="badge-service commercial">Commercial</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="avatar-sm" style="background:#f59e0b">MM</div>
                                        <span>M. Morel</span>
                                    </div>
                                </td>
                                <td><span class="text-muted fst-italic">Non assigné</span></td>
                                <td><span class="badge-ticket-statut ouvert">● Ouvert</span></td>
                                <td><span class="badge-priorite basse">Basse</span></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-action" onclick="ouvrirDetail()"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-person-plus"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="text-muted small">TK-008</td>
                                <td>
                                    <div class="fw-semibold">Écran qui scintille — Bureau Dir.</div>
                                    <div class="text-muted small">12/06/2024 · EC-002</div>
                                </td>
                                <td><span class="badge-ticket-type incident">Incident</span></td>
                                <td><span class="badge-service direction">Direction</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="avatar-sm" style="background:#f97316">AT</div>
                                        <span>A. Thomas</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="avatar-sm" style="background:#14b8a6">TM</div>
                                        <span>Tech. Martin</span>
                                    </div>
                                </td>
                                <td><span class="badge-ticket-statut en-cours">● En cours</span></td>
                                <td><span class="badge-priorite haute">Haute</span></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-action" onclick="ouvrirDetail()"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-person-plus"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                                    </div>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <small class="text-muted">Affichage 1-8 sur 32 tickets</small>
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

@endsection

@section('scripts')
    <script src="{{ asset('js/materiel.js') }}"></script>
@endsection