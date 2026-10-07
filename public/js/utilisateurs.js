document.addEventListener('DOMContentLoaded', () => {
    const modalElement = document.getElementById('modalNouvelUtilisateur');
    const formulaire = document.getElementById('form-utilisateur');
    const typeCompte = document.getElementById('type-compte');
    const champsPersonnel = document.getElementById('champs-personnel');
    const champsEtudiant = document.getElementById('champs-etudiant');
    const boutonEnregistrer = document.getElementById('btn-enregistrer-utilisateur');

    if (!modalElement || !formulaire || !typeCompte || !champsPersonnel || !champsEtudiant) {
        return;
    }

    const champsRequisPersonnel = [
        document.getElementById('service-utilisateur'),
        document.getElementById('poste-utilisateur'),
        document.getElementById('type-contrat-utilisateur'),
    ].filter(Boolean);

    const champsRequisEtudiant = [
        document.getElementById('type-etudiant'),
        document.getElementById('promotion-utilisateur'),
        document.getElementById('etablissement-utilisateur'),
    ].filter(Boolean);

    const configurerSection = (section, active, champsRequis) => {
        section.classList.toggle('d-none', !active);

        section.querySelectorAll('input, select, textarea').forEach((champ) => {
            champ.disabled = !active;
            champ.required = active && champsRequis.includes(champ);
        });
    };

    const actualiserTypeCompte = () => {
        const estPersonnel = typeCompte.value === 'personnel';

        configurerSection(champsPersonnel, estPersonnel, champsRequisPersonnel);
        configurerSection(champsEtudiant, !estPersonnel, champsRequisEtudiant);
    };

    typeCompte.addEventListener('change', actualiserTypeCompte);
    actualiserTypeCompte();

    if (modalElement.dataset.reouvrir === '1' && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    }

    let soumissionEnCours = false;

    formulaire.addEventListener('submit', (event) => {
        if (soumissionEnCours) {
            event.preventDefault();
            return;
        }

        soumissionEnCours = true;

        if (boutonEnregistrer) {
            boutonEnregistrer.disabled = true;
            boutonEnregistrer.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Creation en cours...';
        }
    });

    const modalReinitialisation = document.getElementById('modalReinitialisationMotDePasse');
    const formReinitialisation = document.getElementById('form-reinitialisation-mot-de-passe');
    const nomReinitialisation = document.getElementById('nom-reinitialisation-utilisateur');
    const identifiantReinitialisation = document.getElementById('reinitialisation-utilisateur-id');
    const forcerChangement = document.getElementById('forcer-changement-mot-de-passe');
    const boutonConfirmerReinitialisation = document.getElementById('btn-confirmer-reinitialisation-mot-de-passe');

    const preparerReinitialisation = (bouton, conserverValeurs = false) => {
        if (!modalReinitialisation || !formReinitialisation || !window.bootstrap) {
            return;
        }

        if (!conserverValeurs) {
            formReinitialisation.reset();
            if (forcerChangement) {
                forcerChangement.checked = true;
            }
        }

        formReinitialisation.action = bouton.dataset.url;
        identifiantReinitialisation.value = bouton.dataset.utilisateurId;
        nomReinitialisation.textContent = bouton.dataset.nom || 'cet utilisateur';
        bootstrap.Modal.getOrCreateInstance(modalReinitialisation).show();
    };

    document.querySelectorAll('.btn-reinitialisation-utilisateur:not(:disabled)').forEach((bouton) => {
        bouton.addEventListener('click', () => preparerReinitialisation(bouton));
    });

    if (modalReinitialisation?.dataset.reouvrir === '1') {
        const utilisateurId = modalReinitialisation.dataset.utilisateurId;
        const bouton = document.querySelector(`.btn-reinitialisation-utilisateur[data-utilisateur-id="${utilisateurId}"]`);

        if (bouton) {
            preparerReinitialisation(bouton, true);
        }
    }

    let reinitialisationEnCours = false;

    formReinitialisation?.addEventListener('submit', (event) => {
        if (reinitialisationEnCours) {
            event.preventDefault();
            return;
        }

        reinitialisationEnCours = true;

        if (boutonConfirmerReinitialisation) {
            boutonConfirmerReinitialisation.disabled = true;
            boutonConfirmerReinitialisation.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Reinitialisation...';
        }
    });

    const modalBlocage = document.getElementById('modalBlocageUtilisateur');
    const formBlocage = document.getElementById('form-blocage-utilisateur');
    const titreBlocage = document.getElementById('titre-blocage-utilisateur');
    const texteBlocage = document.getElementById('texte-blocage-utilisateur');
    const nomBlocage = document.getElementById('nom-blocage-utilisateur');
    const consequenceBlocage = document.getElementById('consequence-blocage-utilisateur');
    const boutonConfirmerBlocage = document.getElementById('btn-confirmer-blocage-utilisateur');

    document.querySelectorAll('.btn-blocage-utilisateur:not(:disabled)').forEach((bouton) => {
        bouton.addEventListener('click', () => {
            if (!modalBlocage || !formBlocage || !window.bootstrap) {
                return;
            }

            const estBloque = bouton.dataset.accesBloque === '1';
            const nom = bouton.dataset.nom || 'cet utilisateur';

            formBlocage.action = bouton.dataset.url;
            nomBlocage.textContent = nom;

            if (estBloque) {
                titreBlocage.innerHTML = '<i class="bi bi-unlock me-2"></i>Debloquer l\'acces';
                texteBlocage.firstChild.textContent = 'Voulez-vous retablir l\'acces de ';
                consequenceBlocage.textContent = 'L\'utilisateur pourra de nouveau se connecter a l\'application.';
                boutonConfirmerBlocage.className = 'btn btn-success';
                boutonConfirmerBlocage.innerHTML = '<i class="bi bi-unlock me-1"></i> Debloquer l\'acces';
            } else {
                titreBlocage.innerHTML = '<i class="bi bi-lock me-2"></i>Bloquer l\'acces';
                texteBlocage.firstChild.textContent = 'Voulez-vous bloquer l\'acces de ';
                consequenceBlocage.textContent = 'Toutes ses sessions ouvertes seront fermees.';
                boutonConfirmerBlocage.className = 'btn btn-warning';
                boutonConfirmerBlocage.innerHTML = '<i class="bi bi-lock me-1"></i> Bloquer l\'acces';
            }

            bootstrap.Modal.getOrCreateInstance(modalBlocage).show();
        });
    });

    const modalSuppression = document.getElementById('modalSuppressionUtilisateur');
    const formSuppression = document.getElementById('form-suppression-utilisateur');
    const nomSuppression = document.getElementById('nom-suppression-utilisateur');

    document.querySelectorAll('.btn-suppression-utilisateur:not(:disabled)').forEach((bouton) => {
        bouton.addEventListener('click', () => {
            if (!modalSuppression || !formSuppression || !window.bootstrap) {
                return;
            }

            formSuppression.action = bouton.dataset.url;
            nomSuppression.textContent = bouton.dataset.nom || 'cet utilisateur';
            bootstrap.Modal.getOrCreateInstance(modalSuppression).show();
        });
    });
});
