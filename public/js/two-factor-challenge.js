document.addEventListener('DOMContentLoaded', function () {
    var boutons = document.querySelectorAll('[data-two-factor-tab]');
    var panneaux = document.querySelectorAll('[data-two-factor-panel]');

    function activerMode(mode) {
        boutons.forEach(function (bouton) {
            var actif = bouton.dataset.twoFactorTab === mode;
            bouton.classList.toggle('actif', actif);
            bouton.setAttribute('aria-selected', actif ? 'true' : 'false');
        });

        panneaux.forEach(function (panneau) {
            panneau.hidden = panneau.dataset.twoFactorPanel !== mode;
        });
    }

    boutons.forEach(function (bouton) {
        bouton.addEventListener('click', function () {
            activerMode(bouton.dataset.twoFactorTab);
        });
    });

    activerMode(window.twoFactorMode === 'recuperation' ? 'recuperation' : 'application');
});
