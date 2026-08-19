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
                <button class="btn btn-primary">
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
                    <div class="stat-number">18</div>
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
                    <div class="stat-number">14</div>
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
                    <div class="stat-number">3</div>
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
                    <div class="stat-number">1</div>
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
                    <div class="stat-number">3</div>
                </div>
            </div>
        </div>

    </div>

    {{-- Barre de recherche + Filtres --}}
    <div class="bg-white rounded-3 p-3 mb-4 shadow-sm">
        <div class="d-flex align-items-center gap-3 flex-wrap">

            <div class="input-group" style="max-width: 350px;">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text"
                       class="form-control border-start-0"
                       placeholder="Rechercher un emprunteur, matériel...">
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-filtre active-filtre">Tous les statuts</button>
                <button class="btn btn-filtre"><span class="point-bleu"></span> En cours</button>
                <button class="btn btn-filtre"><span class="point-orange"></span> Échéance proche</button>
                <button class="btn btn-filtre"><span class="point-rouge"></span> En retard</button>
                <button class="btn btn-filtre"><span class="point-vert"></span> Rendu</button>
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

                            <tr class="ligne-retard">
                                <td class="text-muted small">EM-001</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar" style="background:#3b82f6">ML</div>
                                        <div>
                                            <div class="fw-semibold">Martin L.</div>
                                            <div class="text-muted small">martin.l@efel.fr</div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge-type-emprunt externe">Alt. Externe</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-laptop text-muted"></i>
                                        <span>HP EliteBook 840 G9</span>
                                    </div>
                                </td>
                                <td>05/06/2022</td>
                                <td>05/06/2024</td>
                                <td><span class="echeance retard"><i class="bi bi-exclamation-triangle-fill"></i> Retard 2j</span></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-action" onclick="ouvrirDetail()"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-bell"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="text-muted small">EM-002</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar" style="background:#f59e0b">SB</div>
                                        <div>
                                            <div class="fw-semibold">Sophie B.</div>
                                            <div class="text-muted small">sophie.b@efel.fr</div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge-type-emprunt externe">Alt. Externe</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-laptop text-muted"></i>
                                        <span>Lenovo ThinkPad L15</span>
                                    </div>
                                </td>
                                <td>18/09/2023</td>
                                <td>18/06/2024</td>
                                <td><span class="echeance proche">Dans 3 jours</span></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-action" onclick="ouvrirDetail()"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-bell"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="text-muted small">EM-003</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar" style="background:#f97316">AT</div>
                                        <div>
                                            <div class="fw-semibold">Antoine T.</div>
                                            <div class="text-muted small">antoine.t@efel.fr</div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge-type-emprunt externe">Alt. Externe</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-laptop text-muted"></i>
                                        <span>Dell Latitude 5540</span>
                                    </div>
                                </td>
                                <td>02/01/2024</td>
                                <td>02/07/2024</td>
                                <td><span class="echeance proche">Dans 6 jours</span></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-action" onclick="ouvrirDetail()"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-bell"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="text-muted small">EM-004</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar" style="background:#14b8a6">CK</div>
                                        <div>
                                            <div class="fw-semibold">Camille K.</div>
                                            <div class="text-muted small">camille.k@efel.fr</div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge-type-emprunt etudiant">Étud. Initial</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-laptop text-muted"></i>
                                        <span>HP ProBook 450</span>
                                    </div>
                                </td>
                                <td>19/06/2024</td>
                                <td>19/06/2024</td>
                                <td><span class="echeance aujourd-hui">Aujourd'hui</span></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-action" onclick="ouvrirDetail()"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-bell"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="text-muted small">EM-005</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar" style="background:#8b5cf6">LM</div>
                                        <div>
                                            <div class="fw-semibold">Lucas M.</div>
                                            <div class="text-muted small">lucas.m@efel.fr</div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge-type-emprunt etudiant">Étud. Initial</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-laptop text-muted"></i>
                                        <span>Lenovo IdeaPad 3</span>
                                    </div>
                                </td>
                                <td>19/06/2024</td>
                                <td>19/06/2024</td>
                                <td><span class="echeance aujourd-hui">Aujourd'hui</span></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-action" onclick="ouvrirDetail()"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-bell"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="text-muted small">EM-006</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar" style="background:#ec4899">ED</div>
                                        <div>
                                            <div class="fw-semibold">Emma D.</div>
                                            <div class="text-muted small">emma.d@efel.fr</div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge-type-emprunt externe">Alt. Externe</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-laptop text-muted"></i>
                                        <span>Asus VivoBook 15</span>
                                    </div>
                                </td>
                                <td>20/02/2024</td>
                                <td>20/08/2024</td>
                                <td><span class="echeance normal">Dans 21 jours</span></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-action" onclick="ouvrirDetail()"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-bell"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="text-muted small">EM-007</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar" style="background:#6366f1">HR</div>
                                        <div>
                                            <div class="fw-semibold">Hugo R.</div>
                                            <div class="text-muted small">hugo.r@efel.fr</div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge-type-emprunt externe">Alt. Externe</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-laptop text-muted"></i>
                                        <span>Dell Inspiron 15</span>
                                    </div>
                                </td>
                                <td>10/03/2024</td>
                                <td>10/09/2024</td>
                                <td><span class="echeance normal">Dans 2 mois</span></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-action" onclick="ouvrirDetail()"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-bell"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="text-muted small">EM-008</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar" style="background:#0d9488">NP</div>
                                        <div>
                                            <div class="fw-semibold">Nadia P.</div>
                                            <div class="text-muted small">nadia.p@efel.fr</div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge-type-emprunt externe">Alt. Externe</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-laptop text-muted"></i>
                                        <span>HP EliteBook 650</span>
                                    </div>
                                </td>
                                <td>15/04/2024</td>
                                <td>15/10/2024</td>
                                <td><span class="echeance normal">Dans 3 mois</span></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-action" onclick="ouvrirDetail()"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-check"></i></button>
                                        <button class="btn btn-sm btn-action"><i class="bi bi-bell"></i></button>
                                    </div>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <small class="text-muted">Affichage 1-8 sur 18 emprunts</small>
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

@endsection

@section('scripts')
    <script src="{{ asset('js/materiel.js') }}"></script>
@endsection
