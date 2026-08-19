// public/js/login.js

// Afficher/cacher le mot de passe
function togglePassword() {
    var input = document.getElementById('password');
    var icon = document.querySelector('.login-input-icon-right');

    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash login-input-icon-right';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye login-input-icon-right';
    }
}