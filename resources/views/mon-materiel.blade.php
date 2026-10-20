@extends('layouts.app')

@section('title', 'Mon matériel')

@section('styles')
    <link href="{{ asset('css/mon-materiel.css') }}" rel="stylesheet">
@endsection

@section('content')
    @php
        $statuts = [
            'active' => ['label' => 'Affecté', 'class' => 'actif'],
            'en_cours' => ['label' => 'Emprunté', 'class' => 'actif'],
            'echeance_proche' => ['label' => 'Échéance proche', 'class' => 'proche'],
            'en_retard' => ['label' => 'En retard', 'class' => 'retard'],
            'rendu' => ['label' => 'Rendu', 'class' => 'rendu'],
        ];

        $icones = [
            'PC portable' => 'bi-laptop',
            'Mini PC' => 'bi-pc-display',
            'Ecran' => 'bi-display',
            'Imprimante' => 'bi-printer',
            'Clavier' => 'bi-keyboard',
            'Souris' => 'bi-mouse',
            'Casque' => 'bi-headphones',
        ];
    @endphp

    <header class="mon-materiel-entete">
        <div>
            <h1>Mon matériel</h1>
        </div>
        <div class="compteur-materiel" aria-label="Nombre de matériels actifs">
            <i class="bi bi-box-seam"></i>
            <span>{{ $materielsActifs->count() }} matériel(s)</span>
        </div>
    </header>

    <section class="materiel-actuel">
        @if ($materielsActifs->isEmpty())
            <div class="etat-vide-materiel">
                <i class="bi bi-box-seam"></i>
                <strong>Aucun matériel actuellement associé</strong>
                <span>Les nouveaux équipements apparaîtront ici.</span>
            </div>
        @else
            <div class="grille-materiel">
                @foreach ($materielsActifs as $materiel)
                    @php
                        $statut = $statuts[$materiel['statut']] ?? ['label' => 'En cours', 'class' => 'actif'];
                        $icone = $icones[$materiel['type']] ?? 'bi-box';
                    @endphp
                    <article class="carte-materiel">
                        <div class="carte-materiel-haut">
                            <div class="icone-materiel" aria-hidden="true">
                                <i class="bi {{ $icone }}"></i>
                            </div>
                            <span class="statut-materiel {{ $statut['class'] }}">
                                <span></span>{{ $statut['label'] }}
                            </span>
                        </div>

                        <div class="carte-materiel-identite">
                            <span class="type-materiel">{{ $materiel['type'] }}</span>
                            <h3>{{ $materiel['nom'] }}</h3>
                            <p>{{ $materiel['marque'] }}</p>
                        </div>

                        <dl class="details-materiel">
                            <div>
                                <dt>Numéro de série</dt>
                                <dd>{{ $materiel['numero_serie'] }}</dd>
                            </div>
                            <div>
                                <dt>Emplacement</dt>
                                <dd>{{ $materiel['emplacement'] }}</dd>
                            </div>
                            <div>
                                <dt>Date de début</dt>
                                <dd>{{ $materiel['date_debut']?->format('d/m/Y') ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt>Date de fin</dt>
                                <dd>{{ $materiel['date_fin']?->format('d/m/Y') ?? 'Indéterminée' }}</dd>
                            </div>
                        </dl>

                        <div class="carte-materiel-pied">
                            <i class="bi {{ $materiel['origine'] === 'Emprunt' ? 'bi-arrow-left-right' : 'bi-link-45deg' }}"></i>
                            {{ $materiel['origine'] }} en cours
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="historique-materiel" aria-labelledby="titre-historique">
        <div class="section-entete">
            <div>
                <h2 id="titre-historique">Historique</h2>
                <p>{{ $typeUtilisateur === 'etudiant' ? 'Vos anciens emprunts rendus.' : 'Vos anciennes affectations rendues.' }}</p>
            </div>
            <span class="total-historique">{{ $historique->count() }} élément(s)</span>
        </div>

        <div class="table-historique">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Matériel</th>
                            <th>Type</th>
                            <th>N° de série</th>
                            <th>Début</th>
                            <th>Retour</th>
                            <th>Origine</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($historique as $materiel)
                            @php $icone = $icones[$materiel['type']] ?? 'bi-box'; @endphp
                            <tr>
                                <td>
                                    <div class="materiel-historique-identite">
                                        <span><i class="bi {{ $icone }}"></i></span>
                                        <div>
                                            <strong>{{ $materiel['nom'] }}</strong>
                                            <small>{{ $materiel['marque'] }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $materiel['type'] }}</td>
                                <td><span class="numero-serie">{{ $materiel['numero_serie'] }}</span></td>
                                <td>{{ $materiel['date_debut']?->format('d/m/Y') ?? '-' }}</td>
                                <td>{{ $materiel['date_retour']?->format('d/m/Y') ?? '-' }}</td>
                                <td>{{ $materiel['origine'] }}</td>
                                <td><span class="statut-materiel rendu"><span></span>Rendu</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="historique-vide">Aucun historique disponible</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
