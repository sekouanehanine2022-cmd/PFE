{{-- resources/views/mon-materiel.blade.php --}}

@extends('layouts.app')

@section('title', 'Mon materiel')

@section('styles')
    <link href="{{ asset('css/materiel.css') }}" rel="stylesheet">
    <link href="{{ asset('css/affectations.css') }}" rel="stylesheet">
@endsection

@section('content')

    @php
        $titreListe = $typeUtilisateur === 'etudiant' ? 'Mes emprunts' : 'Mes affectations';
        $descriptionListe = $typeUtilisateur === 'etudiant'
            ? 'Liste du materiel emprunte avec les dates de retour prevues.'
            : 'Liste du materiel affecte a votre compte.';

        $typesMateriel = [
            'PcPortable' => 'PC portable',
            'MiniPc' => 'Mini PC',
            'Ecran' => 'Ecran',
            'Imprimante' => 'Imprimante',
            'Peripherique' => 'Peripherique',
        ];

        $statuts = [
            'active' => ['label' => 'Active', 'class' => 'disponible'],
            'expiree' => ['label' => 'Expiree', 'class' => 'emprunte'],
            'cloturee' => ['label' => 'Cloturee', 'class' => 'hors_service'],
            'en_cours' => ['label' => 'En cours', 'class' => 'disponible'],
            'echeance_proche' => ['label' => 'Echeance proche', 'class' => 'emprunte'],
            'rendu' => ['label' => 'Rendu', 'class' => 'affecte'],
            'en_retard' => ['label' => 'En retard', 'class' => 'hors_service'],
        ];
    @endphp

    {{-- Fil d'ariane + titre --}}
    <div class="mb-4">
        <small class="text-muted">Dashboard &gt; Mon espace &gt; Mon materiel</small>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <div>
                <h2 class="fw-bold mb-0">
                    <i class="bi bi-laptop me-2"></i>Mon materiel
                </h2>
                <small class="text-muted">{{ $descriptionListe }}</small>
            </div>
            <button class="btn btn-outline-secondary" type="button" disabled>
                <i class="bi bi-box-seam"></i> {{ $materiels->count() }} element(s)
            </button>
        </div>
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
                                <th>Materiel</th>
                                <th>Type</th>
                                <th>Reference</th>
                                <th>Marque</th>
                                <th>N° Serie</th>
                                <th>Debut</th>
                                <th>Fin</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($materiels as $materiel)
                                @php
                                    $iconeType = match ($materiel['type']) {
                                        'PcPortable' => 'bi-laptop',
                                        'MiniPc' => 'bi-pc',
                                        'Ecran' => 'bi-display',
                                        'Imprimante' => 'bi-printer',
                                        default => 'bi-keyboard',
                                    };

                                    $statut = $statuts[$materiel['statut']] ?? [
                                        'label' => ucfirst(str_replace('_', ' ', $materiel['statut'])),
                                        'class' => 'maintenance',
                                    ];
                                @endphp
                                <tr>
                                    <td class="text-muted small">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="icone-appareil">
                                                <i class="bi {{ $iconeType }}"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $materiel['nom'] }}</div>
                                                <div class="text-muted small">{{ $materiel['emplacement'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-type">
                                            {{ $typesMateriel[$materiel['type']] ?? $materiel['type'] }}
                                        </span>
                                    </td>
                                    <td><span class="badge-serie">{{ $materiel['reference'] }}</span></td>
                                    <td>{{ $materiel['marque'] }}</td>
                                    <td class="text-muted small">{{ $materiel['numero_serie'] }}</td>
                                    <td>{{ $materiel['date_debut'] ? $materiel['date_debut']->format('d/m/Y') : '-' }}</td>
                                    <td class="text-muted">{{ $materiel['date_fin'] ? $materiel['date_fin']->format('d/m/Y') : '-' }}</td>
                                    <td>
                                        <span class="badge-etat {{ $statut['class'] }}">
                                            ● {{ $statut['label'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        Aucun materiel trouve
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <small class="text-muted">
                        Affichage {{ $materiels->count() }} sur {{ $materiels->count() }} element(s)
                    </small>
                    <small class="text-muted">{{ $titreListe }}</small>
                </div>
            </div>
        </div>
    </div>

@endsection
