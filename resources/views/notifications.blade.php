@extends('layouts.app')

@section('title', 'Notifications')

@section('styles')
    <link href="{{ asset('css/notifications.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="notifications-header">
        <div>
            <h2 class="fw-bold mb-1 mt-2">Notifications</h2>
            <p class="text-muted mb-0">{{ $nombreNonLues }} notification(s) non lue(s)</p>
        </div>

        @if ($nombreNonLues > 0)
            <form method="POST" action="{{ route('notifications.tout-lire') }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-check2-all me-1"></i> Tout marquer comme lu
                </button>
            </form>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <section class="notifications-list" aria-label="Liste des notifications">
        @forelse ($notifications as $notification)
            @php
                $estNonLue = is_null($notification->read_at);
                $icone = match ($notification->type) {
                    'nouveau_ticket' => 'bi-ticket-perforated',
                    'ticket_assigne' => 'bi-person-check',
                    'affectation_acceptee' => 'bi-box-arrow-in-down',
                    'emprunt_accepte' => 'bi-arrow-left-right',
                    'ticket_refuse' => 'bi-x-circle',
                    'reponse_incident' => 'bi-chat-left-text',
                    'reponse_incident_modifiee' => 'bi-pencil-square',
                    'ticket_resolu' => 'bi-check-circle',
                    'echeance_affectation_7_jours' => 'bi-calendar-event',
                    'echeance_emprunt_7_jours' => 'bi-alarm',
                    'stock_cable_bas' => 'bi-exclamation-triangle',
                    default => 'bi-bell',
                };
                $libelleAction = match (true) {
                    (bool) $notification->affectation_id => 'Voir l affectation',
                    (bool) $notification->emprunt_id => 'Voir l emprunt',
                    (bool) $notification->cable_id => 'Voir le cable',
                    default => 'Voir le ticket',
                };
            @endphp

            <article class="notification-item {{ $estNonLue ? 'is-unread' : '' }}">
                <div class="notification-icon" aria-hidden="true">
                    <i class="bi {{ $icone }}"></i>
                </div>

                <div class="notification-content">
                    <div class="notification-meta">
                        @if ($estNonLue)
                            <span class="notification-status">Non lue</span>
                        @else
                            <span class="notification-status is-read">Lue</span>
                        @endif
                        <time datetime="{{ $notification->created_at->toIso8601String() }}">
                            {{ $notification->created_at->format('d/m/Y H:i') }}
                        </time>
                    </div>
                    <p class="notification-message">{{ $notification->message }}</p>
                    @if ($notification->ticket)
                        <span class="notification-ticket">{{ $notification->ticket->titre }}</span>
                    @elseif ($notification->affectation)
                        <span class="notification-ticket">
                            Affectation de {{ $notification->affectation->materiel?->nom ?? 'materiel' }}
                            a {{ $notification->affectation->personnel?->user?->name ?? 'un collaborateur' }}
                        </span>
                    @elseif ($notification->emprunt)
                        <span class="notification-ticket">
                            Emprunt de {{ $notification->emprunt->pcPortable?->materiel?->nom ?? 'PC portable' }}
                            par {{ $notification->emprunt->etudiant?->user?->name ?? 'un etudiant' }}
                        </span>
                    @elseif ($notification->cable)
                        <span class="notification-ticket">
                            Cable {{ $notification->cable->type_cable }} - {{ $notification->cable->reference }}
                        </span>
                    @endif
                </div>

                <form method="POST" action="{{ route('notifications.lire', $notification) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-outline-secondary notification-action">
                        <i class="bi bi-eye me-1"></i> {{ $libelleAction }}
                    </button>
                </form>
            </article>
        @empty
            <div class="notifications-empty">
                <i class="bi bi-bell"></i>
                <h3>Aucune notification</h3>
                <p>Les mises à jour de vos tickets apparaîtront ici.</p>
            </div>
        @endforelse
    </section>

    @if ($notifications->hasPages())
        <div class="mt-3">
            {{ $notifications->links() }}
        </div>
    @endif
@endsection
