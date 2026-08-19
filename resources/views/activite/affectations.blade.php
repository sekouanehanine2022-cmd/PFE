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
            <h2 class="fw-bold mb-0">🔗 Affectations</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary">
                    <i class="bi bi-download"></i> Exporter
                </button>
            </div>
        </div>
    </div>

    {{-- Les 4 cartes statistiques --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
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
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0fff4">
                    <i class="bi bi-check-circle" style="color:#22c55e"></i>
                </div>
                <div>
                    <div class="stat-label">Actives</div>
                    <div class="stat-number">{{ $actives }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff7e6">
                    <i class="bi bi-clock" style="color:#f59e0b"></i>
                </div>
                <div>
                    <div class="stat-label">Expirées</div>
                    <div class="stat-number">{{ $expirees }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff0f0">
                    <i class="bi bi-x-circle" style="color:#ef4444"></i>
                </div>
                <div>
                    <div class="stat-label">Clôturées</div>
                    <div class="stat-number">{{ $cloturees }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Barre de recherche + Filtres --}}
    <div class="bg-white rounded-3 p-3 mb-4 shadow-sm">
        <form method="GET" action="{{ route('affectations.index') }}">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="input-group" style="max-width: 350px;">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" name="search"
                           class="form-control border-start-0"
                           placeholder="Rechercher un collaborateur..."
                           value="{{ request('search') }}">
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('affectations.index') }}"
                       class="btn btn-filtre {{ !request('statut') ? 'active-filtre' : '' }}">Tous</a>
                    <a href="{{ route('affectations.index', ['statut' => 'active']) }}"
                       class="btn btn-filtre {{ request('statut') == 'active' ? 'active-filtre' : '' }}">
                       <span class="point-vert"></span> Active</a>
                    <a href="{{ route('affectations.index', ['statut' => 'expiree']) }}"
                       class="btn btn-filtre {{ request('statut') == 'expiree' ? 'active-filtre' : '' }}">
                       <span class="point-orange"></span> Expirée</a>
                    <a href="{{ route('affectations.index', ['statut' => 'cloturee']) }}"
                       class="btn btn-filtre {{ request('statut') == 'cloturee' ? 'active-filtre' : '' }}">
                       <span class="point-rouge"></span> Clôturée</a>
                </div>
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
                                <th>RÉF.</th>
                                <th>DEPUIS</th>
                                <th>FIN</th>
                                <th>STATUT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($affectations as $affectation)
                            @php
                                $materiel     = $affectation->materiel;
                                $typeMateriel = class_basename($affectation->materiel_type);
                                $nom          = $affectation->personnel->user->name ?? '-';
                                $initiales    = collect(explode(' ', $nom))->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->join('');
                                $couleurs     = ['#3b82f6','#22c55e','#f97316','#7c3aed','#ef4444'];
                                $couleur      = $couleurs[crc32($nom) % count($couleurs)];
                            @endphp
                            <tr>
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
                                <td><span class="badge-serie">{{ $materiel->reference ?? '-' }}</span></td>
                                <td>{{ $affectation->date_debut ? \Carbon\Carbon::parse($affectation->date_debut)->format('d/m/Y') : '-' }}</td>
                                <td class="text-muted">{{ $affectation->date_fin ? \Carbon\Carbon::parse($affectation->date_fin)->format('d/m/Y') : '—' }}</td>
                                <td>
                                    @if($affectation->statut === 'active')
                                        <span class="badge-etat disponible">● Active</span>
                                    @elseif($affectation->statut === 'expiree')
                                        <span class="badge-etat emprunte">● Expirée</span>
                                    @else
                                        <span class="badge-etat hors_service">● Clôturée</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
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

@endsection

@section('scripts')
    <script src="{{ asset('js/materiel.js') }}"></script>
@endsection