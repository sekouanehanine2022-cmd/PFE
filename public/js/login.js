// public/js/login.js

// Afficher/cacher le mot de passe
function togglePassword() {
    var input = document.getElementById('password');
    var icon = document.querySelector('.login-icon-right');

    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash login-icon-right';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye login-icon-right';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    var boutonLogin = document.getElementById('btn-login');
    var messageErreur = document.getElementById('login-error-message');
    var secondesRestantes = Number(window.loginLockoutSeconds || 0);

    if (!boutonLogin || secondesRestantes <= 0) {
        return;
    }

    var texteInitial = boutonLogin.innerHTML;

    boutonLogin.disabled = true;
    boutonLogin.classList.add('disabled');

    function actualiserCompteur() {
        boutonLogin.innerHTML = '<i class="bi bi-hourglass-split"></i> Reessayez dans ' + secondesRestantes + 's';

        if (secondesRestantes <= 0) {
            clearInterval(intervalCompteur);
            boutonLogin.disabled = false;
            boutonLogin.classList.remove('disabled');
            boutonLogin.innerHTML = texteInitial;

            if (messageErreur) {
                messageErreur.style.display = 'none';
            }

            return;
        }

        secondesRestantes--;
    }

    actualiserCompteur();
    var intervalCompteur = setInterval(actualiserCompteur, 1000);
});
