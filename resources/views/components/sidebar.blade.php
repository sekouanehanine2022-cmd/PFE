{{-- resources/views/components/sidebar.blade.php --}}

@php
    $utilisateurSidebar = auth()->user();
    $utilisateurSidebar?->loadMissing(['personnel', 'etudiant']);
    $estAdmin = $utilisateurSidebar?->personnel?->role === 'admin';
@endphp

<div class="sidebar" id="sidebar">

    {{-- Logo / Nom de l'app --}}
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="bi bi-pc-display"></i>
        </div>
        <div class="brand-text">
            <span class="brand-name">IEG</span>
            <span class="brand-sub">Parc Informatique</span>
        </div>
    </div>

    {{-- Navigation principale --}}
    <nav class="sidebar-nav">

        {{-- Dashboard --}}
        <ul class="nav-list">
            @if ($estAdmin)
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}"
                       class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            @endif

            <li class="nav-item">
                <a href="{{ route('mon-materiel.index') }}"
                   class="nav-link {{ request()->routeIs('mon-materiel.*') ? 'active' : '' }}">
                    <i class="bi bi-laptop"></i>
                    <span>Mon materiel</span>
                </a>
            </li>

            @unless ($estAdmin)
                <li class="nav-item">
                    <a href="{{ route('tickets.index') }}"
                       class="nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                        <i class="bi bi-ticket"></i>
                        <span>Mes tickets</span>
                    </a>
                </li>
            @endunless
        </ul>

        @if ($estAdmin)
            {{-- Section Materiel --}}
            <div class="nav-section-title">Materiel</div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="{{ route('pc-portables.index') }}"
                       class="nav-link {{ request()->routeIs('pc-portables.*') ? 'active' : '' }}">
                        <i class="bi bi-laptop"></i>
                        <span>PC Portables</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('mini-pc.index') }}"
                       class="nav-link {{ request()->routeIs('mini-pc.*') ? 'active' : '' }}">
                        <i class="bi bi-pc"></i>
                        <span>Mini PC</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('ecrans.index') }}"
                       class="nav-link {{ request()->routeIs('ecrans.*') ? 'active' : '' }}">
                        <i class="bi bi-display"></i>
                        <span>Ecrans</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('imprimantes.index') }}"
                       class="nav-link {{ request()->routeIs('imprimantes.*') ? 'active' : '' }}">
                        <i class="bi bi-printer"></i>
                        <span>Imprimantes</span>
                    </a>
                </li>
            </ul>

            {{-- Section Connectique --}}
            <div class="nav-section-title">Connectique</div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="{{ route('cables.index') }}"
                       class="nav-link {{ request()->routeIs('cables.*') ? 'active' : '' }}">
                        <i class="bi bi-plug"></i>
                        <span>Cables</span>
                    </a>
                </li>
            </ul>

            {{-- Section Peripheriques --}}
            <div class="nav-section-title">Peripheriques</div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="{{ route('claviers.index') }}"
                       class="nav-link {{ request()->routeIs('claviers.*') ? 'active' : '' }}">
                        <i class="bi bi-keyboard"></i>
                        <span>Claviers</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('souris.index') }}"
                       class="nav-link {{ request()->routeIs('souris.*') ? 'active' : '' }}">
                        <i class="bi bi-mouse"></i>
                        <span>Souris</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('casques.index') }}"
                       class="nav-link {{ request()->routeIs('casques.*') ? 'active' : '' }}">
                        <i class="bi bi-headphones"></i>
                        <span>Casques</span>
                    </a>
                </li>
            </ul>

            {{-- Section Activite --}}
            <div class="nav-section-title">Activite</div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="{{ route('affectations.index') }}"
                       class="nav-link {{ request()->routeIs('affectations.*') ? 'active' : '' }}">
                        <i class="bi bi-link-45deg"></i>
                        <span>Affectations</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('emprunts.index') }}"
                       class="nav-link {{ request()->routeIs('emprunts.*') ? 'active' : '' }}">
                        <i class="bi bi-arrow-left-right"></i>
                        <span>Emprunts</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('tickets.index') }}"
                       class="nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                        <i class="bi bi-ticket"></i>
                        <span>Tickets</span>
                    </a>
                </li>
            </ul>
        @endif

        {{-- Section Systeme --}}
        <div class="nav-section-title">Systeme</div>
        <ul class="nav-list">
            @if ($estAdmin)
                <li class="nav-item">
                    <a href="{{ route('utilisateurs.index') }}"
                       class="nav-link {{ request()->routeIs('utilisateurs.*') ? 'active' : '' }}">
                        <i class="bi bi-people"></i>
                        <span>Comptes utilisateurs</span>
                    </a>
                </li>
            @endif

            <li class="nav-item">
                <a href="{{ route('notifications.index') }}"
                   class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                    <i class="bi bi-bell"></i>
                    <span>Notifications</span>
                    @if (($notificationsNonLues ?? 0) > 0)
                        <span class="ticket-unread-dot"
                              title="{{ $notificationsNonLues }} notification(s) non lue(s)"
                              aria-label="{{ $notificationsNonLues }} notification(s) non lue(s)"></span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('parametres.index') }}"
                   class="nav-link {{ request()->routeIs('parametres.*') ? 'active' : '' }}">
                    <i class="bi bi-gear"></i>
                    <span>Parametres</span>
                </a>
            </li>
        </ul>

    </nav>

    {{-- Deconnexion en bas --}}
    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-link btn-logout w-100">
                <i class="bi bi-box-arrow-left"></i>
                <span>Deconnexion</span>
            </button>
        </form>
    </div>

</div>

{{-- Overlay pour mobile --}}
<div class="sidebar-overlay" id="sidebarOverlay"></div>
