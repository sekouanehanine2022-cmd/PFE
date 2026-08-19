{{-- resources/views/layouts/app.blade.php --}}
{{-- C'est le squelette de TOUTES tes pages. Chaque page l'utilisera. --}}

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Titre de la page (chaque page peut le personnaliser) --}}
    <title>@yield('title', 'Parc Informatique - EFEL')</title>

    {{-- Bootstrap 5 CSS (CDN, pas besoin d'installer) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Bootstrap Icons (les icônes de la sidebar) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    {{-- Notre CSS personnalisé pour la sidebar --}}
    <link href="{{ asset('css/sidebar.css') }}" rel="stylesheet">

    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    {{-- CSS supplémentaire propre à chaque page (optionnel) --}}
    @yield('styles')
</head>
<body>

    {{-- === SIDEBAR === --}}
    @include('components.sidebar')

    {{-- === CONTENU PRINCIPAL === --}}
    {{-- La classe "main-content" est dans sidebar.css : elle ajoute margin-left sur desktop --}}
    <div class="main-content">

        {{-- Barre du haut sur mobile : bouton pour ouvrir la sidebar --}}
        <div class="d-flex d-md-none align-items-center p-3 bg-white border-bottom">
            <button class="btn btn-sm btn-outline-secondary me-3" id="sidebarToggle">
                <i class="bi bi-list fs-5"></i>
            </button>
            <strong>IEG - Parc Informatique</strong>
        </div>

        {{-- Zone de contenu de chaque page --}}
        <div class="p-4">
            @yield('content')
        </div>

    </div>

    {{-- Bootstrap 5 JS (nécessaire pour les composants Bootstrap) --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Notre JavaScript pour la sidebar mobile --}}
    <script src="{{ asset('js/sidebar.js') }}"></script>

    {{-- JS supplémentaire propre à chaque page (optionnel) --}}
    @yield('scripts')

</body>
</html>