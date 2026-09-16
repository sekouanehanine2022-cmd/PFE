{{-- resources/views/systeme/notifications.blade.php --}}

@extends('layouts.app')

@section('title', 'Notifications')

@section('styles')
    <link href="{{ asset('css/materiel.css') }}" rel="stylesheet">
    <link href="{{ asset('css/notifications.css') }}" rel="stylesheet">
@endsection

@section('content')

    {{-- Fil d'ariane + Titre --}}
    <div class="mb-4">
        <small class="text-muted">Dashboard > Système > Notifications</small>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <h2 class="fw-bold mb-0">Notifications</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary">
                    <i class="bi bi-check-all"></i> Tout marquer lu
                </button>
                <button class="btn btn-outline-danger">
                    <i class="bi bi-trash"></i> Tout effacer
                </button>
            </div>
        </div>
    </div>

    {{-- Les 6 cartes statistiques --}}
    <div class="row g-3 mb-4">

        <div class="col-12 col-md-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0f4ff">
                    <i class="bi bi-bell" style="color:#3b82f6"></i>
                </div>
                <div>
                    <div class="stat-label">Total</div>
                    <div class="stat-number">24</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon" style="background:#eff6ff">
                    <i class="bi bi-circle" style="color:#3b82f6"></i>
                </div>
                <div>
                    <div class="stat-label">Non lues</div>
                    <div class="stat-number">5</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff0f0">
                    <i class="bi bi-exclamation-triangle" style="color:#ef4444"></i>
                </div>
                <div>
                    <div class="stat-label">Retards emprunt</div>
                    <div class="stat-number">1</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff7e6">
                    <i class="bi bi-clock" style="color:#f59e0b"></i>
                </div>
                <div>
                    <div class="stat-label">Échéances proches</div>
                    <div class="stat-number">3</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0f4ff">
                    <i class="bi bi-ticket" style="color:#3b82f6"></i>
                </div>
                <div>
                    <div class="stat-label">Tickets</div>
                    <div class="stat-number">8</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff7e6">
                    <i class="bi bi-box" style="color:#f59e0b"></i>
                </div>
                <div>
                    <div class="stat-label">Stock bas</div>
                    <div class="stat-number">2</div>
                </div>
            </div>
        </div>

    </div>

    {{-- Barre de recherche --}}
    <div class="bg-white rounded-3 p-3 mb-4 shadow-sm">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="input-group" style="max-width: 400px;">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text"
                       class="form-control border-start-0"
                       placeholder="Rechercher une notification...">
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-filtre active-filtre">Toutes catégories</button>
                <button class="btn btn-filtre"><span class="point-rouge"></span> Emprunts</button>
                <button class="btn btn-filtre"><span class="point-bleu"></span> Tickets</button>
                <button class="btn btn-filtre"><span class="point-orange"></span> Stock</button>
            </div>
        </div>
    </div>

    {{-- Contenu principal --}}
<div class="row g-3">

    {{-- Liste des notifications (pleine largeur) --}}
    <div class="col-12">
        <div class="bg-white rounded-3 shadow-sm p-3">

            {{-- En-tête --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-semibold">Centre de notifications</span>
                    <span class="badge-non-lues">5 non lues</span>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-action"><i class="bi bi-check-all"></i></button>
                    <button class="btn btn-sm btn-action"><i class="bi bi-trash"></i></button>
                </div>
            </div>

            {{-- Groupe AUJOURD'HUI --}}
            <div class="notif-groupe-titre">AUJOURD'HUI</div>

            <div class="notif-item non-lue">
                <div class="notif-point"></div>
                <div class="notif-icone retard">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="notif-contenu">
                    <div class="notif-titre">Emprunt en retard — Martin L.</div>
                    <div class="notif-desc">Le HP EliteBook 840 G9 (PC-002) devait être rendu le 05/06/2024. Retard de <strong>2 jours</strong>.</div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="notif-tag emprunts"><i class="bi bi-arrow-left-right"></i> Emprunts</span>
                        <small class="text-muted">Il y a 2 jours</small>
                    </div>
                </div>
                <div class="notif-actions">
                    <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                    <button class="btn btn-sm btn-action"><i class="bi bi-x"></i></button>
                </div>
            </div>

            <div class="notif-item non-lue">
                <div class="notif-point"></div>
                <div class="notif-icone echeance">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="notif-contenu">
                    <div class="notif-titre">Fin de formation dans 3 jours — Sophie B.</div>
                    <div class="notif-desc">Le Lenovo ThinkPad L15 (PC-003) doit être restitué le 18/06/2024. Rappel J-3.</div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="notif-tag emprunts"><i class="bi bi-arrow-left-right"></i> Emprunts</span>
                        <small class="text-muted">Il y a 3h</small>
                    </div>
                </div>
                <div class="notif-actions">
                    <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                    <button class="btn btn-sm btn-action"><i class="bi bi-x"></i></button>
                </div>
            </div>

            <div class="notif-item non-lue">
                <div class="notif-point"></div>
                <div class="notif-icone ticket">
                    <i class="bi bi-ticket"></i>
                </div>
                <div class="notif-contenu">
                    <div class="notif-titre">Nouveau ticket ouvert — TK-001</div>
                    <div class="notif-desc">M. Leclerc (Pédagogie) a déclaré un incident : <em>PC portable ne démarre plus</em>. Priorité haute.</div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="notif-tag tickets"><i class="bi bi-ticket"></i> Tickets</span>
                        <small class="text-muted">Il y a 1h</small>
                    </div>
                </div>
                <div class="notif-actions">
                    <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                    <button class="btn btn-sm btn-action"><i class="bi bi-x"></i></button>
                </div>
            </div>

            <div class="notif-item non-lue">
                <div class="notif-point"></div>
                <div class="notif-icone echeance">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="notif-contenu">
                    <div class="notif-titre">Fin de formation dans 6 jours — Antoine T.</div>
                    <div class="notif-desc">Le Dell Latitude 5540 (PC-001) doit être restitué le 02/07/2024. Rappel J-7.</div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="notif-tag emprunts"><i class="bi bi-arrow-left-right"></i> Emprunts</span>
                        <small class="text-muted">Il y a 2h</small>
                    </div>
                </div>
                <div class="notif-actions">
                    <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                    <button class="btn btn-sm btn-action"><i class="bi bi-x"></i></button>
                </div>
            </div>

            <div class="notif-item non-lue">
                <div class="notif-point"></div>
                <div class="notif-icone stock">
                    <i class="bi bi-box"></i>
                </div>
                <div class="notif-contenu">
                    <div class="notif-titre">Stock USB-C en dessous du seuil</div>
                    <div class="notif-desc">Seulement <strong>4 câbles USB-C</strong> disponibles sur 35 en stock. Seuil d'alerte : 5.</div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="notif-tag stock"><i class="bi bi-box"></i> Stock</span>
                        <small class="text-muted">Ce matin</small>
                    </div>
                </div>
                <div class="notif-actions">
                    <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                    <button class="btn btn-sm btn-action"><i class="bi bi-x"></i></button>
                </div>
            </div>

            {{-- Groupe HIER --}}
            <div class="notif-groupe-titre mt-3">HIER — 18 JUIN 2026</div>

            <div class="notif-item">
                <div class="notif-point invisible"></div>
                <div class="notif-icone ticket lue">
                    <i class="bi bi-person-check"></i>
                </div>
                <div class="notif-contenu">
                    <div class="notif-titre text-muted">Ticket TK-003 assigné à Tech. Dupont</div>
                    <div class="notif-desc text-muted">L'incident "Imprimante salle 102 hors service" a été assigné à Tech. Dupont (Support IT).</div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="notif-tag tickets"><i class="bi bi-ticket"></i> Tickets</span>
                        <small class="text-muted">Hier 14h32</small>
                    </div>
                </div>
                <div class="notif-actions">
                    <button class="btn btn-sm btn-action"><i class="bi bi-x"></i></button>
                </div>
            </div>

            <div class="notif-item">
                <div class="notif-point invisible"></div>
                <div class="notif-icone ticket lue">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="notif-contenu">
                    <div class="notif-titre text-muted">Ticket TK-004 résolu</div>
                    <div class="notif-desc text-muted">L'installation du logiciel de comptabilité a été réalisée avec succès par Tech. Martin.</div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="notif-tag tickets"><i class="bi bi-ticket"></i> Tickets</span>
                        <small class="text-muted">Hier 10h15</small>
                    </div>
                </div>
                <div class="notif-actions">
                    <button class="btn btn-sm btn-action"><i class="bi bi-x"></i></button>
                </div>
            </div>

        </div>
    </div>

</div>

@endsection

@section('scripts')
    <script src="{{ asset('js/materiel.js') }}"></script>
@endsection