(function () {
    const urlMeta = document.querySelector('meta[name="session-keep-alive-url"]');
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');

    if (!urlMeta || !csrfMeta) return;

    // Maintient la session ouverte et actualise les jetons CSRF des formulaires.
    const renouvelerSession = async function () {
        try {
            const reponse = await fetch(urlMeta.content, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!reponse.ok) return;

            const donnees = await reponse.json();
            if (!donnees.csrf_token) return;

            // Les formulaires ouverts conservent le jeton correspondant a la session courante.
            csrfMeta.content = donnees.csrf_token;
            document.querySelectorAll('input[name="_token"]').forEach(function (champ) {
                champ.value = donnees.csrf_token;
            });
        } catch (erreur) {
            // Une coupure réseau temporaire ne doit pas interrompre l'utilisation de la page.
        }
    };

    window.setInterval(renouvelerSession, 5 * 60 * 1000);
    window.addEventListener('focus', renouvelerSession);
})();
