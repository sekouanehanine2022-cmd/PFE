{{-- resources/views/dashboard.blade.php --}}

@extends('layouts.app')

@section('title', 'Dashboard')

@section('styles')
    <link href="{{ asset('css/materiel.css') }}" rel="stylesheet">
@endsection

@section('content')

    {{-- Titre --}}
    <div class="mb-4">
        <h2 class="fw-bold mb-0">Dashboard</h2>
        <small class="text-muted">Vue d'ensemble du parc informatique</small>
    </div>

    {{-- Les 4 cartes statistiques principales --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0f4ff">
                    <i class="bi bi-hdd-stack" style="color:#3b82f6"></i>
                </div>
                <div>
                    <div class="stat-label">Total matériel</div>
                    <div class="stat-number">{{ $totalMateriel }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff7f0">
                    <i class="bi bi-ticket" style="color:#f97316"></i>
                </div>
                <div>
                    <div class="stat-label">Tickets ouverts</div>
                    <div class="stat-number">{{ $ticketsOuverts }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0fff4">
                    <i class="bi bi-arrow-left-right" style="color:#22c55e"></i>
                </div>
                <div>
                    <div class="stat-label">Emprunts en cours</div>
                    <div class="stat-number">{{ $empruntsEnCours }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0f9ff">
                    <i class="bi bi-link-45deg" style="color:#0ea5e9"></i>
                </div>
                <div>
                    <div class="stat-label">Affectations actives</div>
                    <div class="stat-number">{{ $affectationsActives }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">

        {{-- Alertes --}}
        <div class="col-12 col-lg-5">
            <div class="bg-white rounded-3 shadow-sm p-3 h-100">
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-exclamation-triangle text-danger me-1"></i> Alertes
                </h6>

                <div class="d-flex flex-column gap-2">

                    <div class="d-flex align-items-center justify-content-between p-2 rounded-3" style="background:#fff1f2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-circle text-danger"></i>
                            <span class="small">Matériel en panne</span>
                        </div>
                        <span class="badge bg-danger">{{ $materielEnPanne }}</span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between p-2 rounded-3" style="background:#fff7ed">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-box text-warning"></i>
                            <span class="small">Câbles en stock bas / rupture</span>
                        </div>
                        <span class="badge bg-warning text-dark">{{ $cablesEnAlerte }}</span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between p-2 rounded-3" style="background:#fff1f2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-clock-history text-danger"></i>
                            <span class="small">Emprunts en retard</span>
                        </div>
                        <span class="badge bg-danger">{{ $empruntsEnRetard }}</span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between p-2 rounded-3" style="background:#fff7ed">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-fire text-warning"></i>
                            <span class="small">Tickets priorité haute</span>
                        </div>
                        <span class="badge bg-warning text-dark">{{ $ticketsPrioriteHaute }}</span>
                    </div>

                </div>
            </div>
        </div>

        {{-- Activité récente --}}
        <div class="col-12 col-lg-7">
            <div class="bg-white rounded-3 shadow-sm p-3 h-100">
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-clock text-primary me-1"></i> Activité récente
                </h6>

                @forelse ($activiteRecente as $evenement)
                    @php
                        $icone  = match($evenement['type']) {
                            'ticket'      => 'bi-ticket',
                            'emprunt'     => 'bi-arrow-left-right',
                            'affectation' => 'bi-link-45deg',
                            default       => 'bi-circle',
                        };
                        $couleur = match($evenement['type']) {
                            'ticket'      => '#f97316',
                            'emprunt'     => '#22c55e',
                            'affectation' => '#0ea5e9',
                            default       => '#6b7280',
                        };
                    @endphp
                    <a href="{{ $evenement['lien'] }}" class="text-decoration-none text-dark">
                        <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                            <div class="d-flex align-items-center justify-content-center rounded-circle"
                                 style="width:32px;height:32px;background:{{ $couleur }}1a;flex-shrink:0;">
                                <i class="bi {{ $icone }}" style="color:{{ $couleur }};font-size:14px;"></i>
                            </div>
                            <div class="flex-fill">
                                <div class="small fw-semibold">{{ $evenement['titre'] }}</div>
                                <div class="text-muted" style="font-size:12px;">{{ $evenement['personne'] }}</div>
                            </div>
                            <small class="text-muted">{{ $evenement['date']->diffForHumans() }}</small>
                        </div>
                    </a>
                @empty
                    <p class="text-muted small text-center py-4 mb-0">Aucune activité récente</p>
                @endforelse

            </div>
        </div>

    </div>

@endsection