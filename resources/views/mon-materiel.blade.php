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

        $statuts = [
            'active' => ['label' => 'Active', 'class' => 'disponible'],
            'en_cours' => ['label' => 'En cours', 'class' => 'disponible'],
            'echeance_proche' => ['label' => 'Echeance proche', 'class' => 'emprunte'],
            'rendu' => ['label' => 'Rendu', 'class' => 'affecte'],
            'en_retard' => ['label' => 'En retard', 'class' => 'hors_service'],
        ];
    @endphp

    {{-- Titre --}}
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mt-2">
            <div>
                <h2 class="fw-bold mb-0">Mon materiel</h2>
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
                                        'PC portable' => 'bi-laptop',
                                        'Mini PC' => 'bi-pc',
                                        'Ecran' => 'bi-display',
                                        'Clavier' => 'bi-keyboard',
                                        'Souris' => 'bi-mouse',
                                        'Casque' => 'bi-headphones',
                                        default => 'bi-box',
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
                                            {{ $materiel['type'] }}
                                        </span>
                                    </td>
                                    <td>{{ $materiel['marque'] }}</td>
                                    <td><span class="badge-serie">{{ $materiel['numero_serie'] }}</span></td>
                                    <td>{{ $materiel['date_debut'] ? $materiel['date_debut']->format('d/m/Y') : '-' }}</td>
                                    <td class="text-muted">
                                        {{ $materiel['date_fin'] ? $materiel['date_fin']->format('d/m/Y') : ($typeUtilisateur === 'personnel' ? 'Indéterminée' : '-') }}
                                    </td>
                                    <td>
                                        <span class="badge-etat {{ $statut['class'] }}">
                                            ● {{ $statut['label'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
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
