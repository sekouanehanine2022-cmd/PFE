document.addEventListener('DOMContentLoaded', () => {
    const cleVerification = 'email-verification-reussie';
    const confirmation = document.getElementById('verification-confirmation');

    if (confirmation) {
        try {
            localStorage.setItem(cleVerification, Date.now().toString());
        } catch (erreur) {
            // Le controle automatique de la page d'attente reste disponible.
        }

        if (window.opener && !window.opener.closed) {
            window.opener.postMessage(
                { type: cleVerification },
                window.location.origin
            );
            window.opener.focus();
        }

        window.setTimeout(() => {
            window.close();
        }, 300);

        return;
    }

    const carte = document.getElementById('verification-card');
    const etatAttente = document.getElementById('verification-en-attente');
    const etatReussi = document.getElementById('verification-reussie');

    if (!carte || !etatAttente || !etatReussi) {
        return;
    }

    let verificationTerminee = false;

    const afficherSucces = (urlRedirection) => {
        if (verificationTerminee) {
            return;
        }

        verificationTerminee = true;
        etatAttente.classList.add('d-none');
        etatReussi.classList.remove('d-none');

        window.setTimeout(() => {
            window.location.assign(urlRedirection || carte.dataset.redirectUrl);
        }, 1800);
    };

    const verifierEtat = async () => {
        if (verificationTerminee || document.hidden) {
            return;
        }

        try {
            const reponse = await fetch(carte.dataset.statusUrl, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!reponse.ok) {
                return;
            }

            const resultat = await reponse.json();

            if (resultat.verified) {
                afficherSucces(resultat.redirect);
            }
        } catch (erreur) {
            // Une prochaine verification sera effectuee automatiquement.
        }
    };

    verifierEtat();
    window.setInterval(verifierEtat, 2000);
    document.addEventListener('visibilitychange', verifierEtat);

    window.addEventListener('storage', (evenement) => {
        if (evenement.key === cleVerification && evenement.newValue) {
            afficherSucces(carte.dataset.redirectUrl);
        }
    });

    window.addEventListener('message', (evenement) => {
        if (
            evenement.origin === window.location.origin
            && evenement.data?.type === cleVerification
        ) {
            afficherSucces(carte.dataset.redirectUrl);
        }
    });
});
