// public/js/sidebar.js
// Gère l'ouverture/fermeture de la sidebar sur mobile

// On attend que la page soit complètement chargée
document.addEventListener('DOMContentLoaded', function () {

    // On récupère les éléments dont on a besoin
    var sidebar        = document.getElementById('sidebar');
    var overlay        = document.getElementById('sidebarOverlay');
    var toggleButton   = document.getElementById('sidebarToggle');

    // Sécurité : si un élément est absent, on arrête
    if (!sidebar || !overlay) return;

    // ----- Fonction : ouvrir la sidebar -----
    function openSidebar() {
        sidebar.classList.add('show');   // Ajoute la classe CSS "show" → sidebar visible
        overlay.style.display = 'block'; // Affiche le fond sombre
    }

    // ----- Fonction : fermer la sidebar -----
    function closeSidebar() {
        sidebar.classList.remove('show'); // Retire "show" → sidebar cachée
        overlay.style.display = 'none';  // Cache le fond sombre
    }

    // ----- Quand on clique sur le bouton hamburger -----
    if (toggleButton) {
        toggleButton.addEventListener('click', function () {
            // Si la sidebar est déjà ouverte, on la ferme, sinon on l'ouvre
            if (sidebar.classList.contains('show')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    // ----- Quand on clique sur l'overlay, on ferme la sidebar -----
    overlay.addEventListener('click', function () {
        closeSidebar();
    });

    // ----- Quand on clique sur un lien de la sidebar sur mobile, on ferme -----
    var navLinks = sidebar.querySelectorAll('.nav-link');
    navLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            // Seulement sur mobile (largeur < 768px)
            if (window.innerWidth < 768) {
                closeSidebar();
            }
        });
    });

});