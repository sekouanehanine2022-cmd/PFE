// public/js/parametres.js

document.addEventListener('DOMContentLoaded', function () {
    var boutonsAffichage = document.querySelectorAll('.password-toggle');

    boutonsAffichage.forEach(function (bouton) {
        bouton.addEventListener('click', function () {
            var champ = document.getElementById(bouton.dataset.target);
            var icone = bouton.querySelector('i');

            if (!champ || !icone) {
                return;
            }

            if (champ.type === 'password') {
                champ.type = 'text';
                icone.className = 'bi bi-eye-slash';
            } else {
                champ.type = 'password';
                icone.className = 'bi bi-eye';
            }
        });
    });

    var modals2fa = {
        regenerer: 'modalRegenererCodes2fa',
        desactiver: 'modalDesactiver2fa'
    };
    var modalId = modals2fa[window.parametresModal2fa];
    var modalElement = modalId ? document.getElementById(modalId) : null;

    if (modalElement && window.bootstrap) {
        window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
    }
});
