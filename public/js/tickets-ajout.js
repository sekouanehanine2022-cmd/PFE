// public/js/tickets-ajout.js

document.addEventListener('DOMContentLoaded', function () {

    // Le champ "Numéro de série" n'a de sens que pour un incident
    // (une demande d'affectation/emprunt ne porte pas encore sur un matériel précis)
    const selectType = document.getElementById('type');
    const selectTypeMateriel = document.getElementById('type_materiel_incident');
    const champNumeroSerie = document.getElementById('numero_serie');
    const aideNumeroSerie = document.getElementById('numero-serie-aide');
    const blocTypeMateriel = document.getElementById('bloc-type-materiel-incident');
    const blocNumeroSerie = document.getElementById('bloc-numero-serie-incident');

    function renseignerNumeroSerie() {
        if (!selectTypeMateriel || !champNumeroSerie) return;

        const option = selectTypeMateriel.options[selectTypeMateriel.selectedIndex];
        champNumeroSerie.value = option?.dataset.numeroSerie || '';
    }

    function mettreAJourChampNumeroSerie() {
        if (!selectType || !selectTypeMateriel || !champNumeroSerie) return;

        const estIncident = selectType.value === 'incident';

        blocTypeMateriel?.classList.toggle('d-none', !estIncident);
        blocNumeroSerie?.classList.toggle('d-none', !estIncident);
        selectTypeMateriel.disabled = !estIncident;
        champNumeroSerie.disabled = !estIncident;

        if (estIncident) {
            renseignerNumeroSerie();
            if (aideNumeroSerie) {
                aideNumeroSerie.textContent = 'Le numéro de série est renseigné automatiquement.';
            }
        } else {
            selectTypeMateriel.value = '';
            champNumeroSerie.value = '';
        }
    }

    if (selectType) {
        selectType.addEventListener('change', mettreAJourChampNumeroSerie);
        // On applique l'état correct dès l'ouverture du modal (au cas où
        // un type serait déjà sélectionné, par exemple après une erreur de validation)
        mettreAJourChampNumeroSerie();
    }

    if (selectTypeMateriel) {
        selectTypeMateriel.addEventListener('change', renseignerNumeroSerie);
    }

    initialiserSoumissionTicket();
    initialiserAffectationDepuisTicket();
    initialiserEmpruntDepuisTicket();
    ouvrirTicketDepuisNotification();

});

function ouvrirTicketDepuisNotification() {
    const ticketId = new URLSearchParams(window.location.search).get('ticket');
    if (!ticketId) return;

    const bouton = document.querySelector(
        `button[data-ticket-id="${CSS.escape(ticketId)}"][onclick="ouvrirDetailTicket(this)"]`
    );

    if (bouton) {
        ouvrirDetailTicket(bouton);
    }
}

function initialiserSoumissionTicket() {
    const formulaire = document.getElementById('form-ajout-ticket');
    const bouton = document.getElementById('btn-enregistrer-ticket');

    if (!formulaire || !bouton) return;

    const contenuInitial = bouton.innerHTML;

    function reinitialiser() {
        formulaire.dataset.soumissionEnCours = '0';
        bouton.disabled = false;
        bouton.innerHTML = contenuInitial;
    }

    formulaire.addEventListener('submit', function (evenement) {
        if (formulaire.dataset.soumissionEnCours === '1') {
            evenement.preventDefault();
            return;
        }

        formulaire.dataset.soumissionEnCours = '1';
        bouton.disabled = true;
        bouton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Enregistrement...';
    });

    window.addEventListener('pageshow', reinitialiser);
}

function initialiserAffectationDepuisTicket() {
    const formulaire = document.getElementById('form-affectation-depuis-ticket');
    const selectTypeMateriel = document.getElementById('ticket-affectation-materiel-type');
    const selectMateriel = document.getElementById('ticket-affectation-materiel');
    const boutonConfirmer = document.getElementById('btn-confirmer-affectation-ticket');

    if (!formulaire || !selectTypeMateriel || !selectMateriel || !boutonConfirmer) return;

    selectTypeMateriel.addEventListener('change', async function () {
        selectMateriel.disabled = true;
        boutonConfirmer.disabled = true;

        if (!this.value) {
            remplirSelectMateriel(selectMateriel, 'Choisir d\'abord un type');
            return;
        }

        remplirSelectMateriel(selectMateriel, 'Chargement...');

        try {
            const baseUrl = formulaire.dataset.materielBaseUrl;
            const reponse = await fetch(`${baseUrl}/${encodeURIComponent(this.value)}`, {
                headers: { Accept: 'application/json' },
            });

            if (!reponse.ok) {
                throw new Error('Chargement impossible');
            }

            const materiels = await reponse.json();
            selectMateriel.innerHTML = '';

            if (!materiels.length) {
                remplirSelectMateriel(selectMateriel, 'Aucun materiel disponible');
                return;
            }

            const optionInitiale = document.createElement('option');
            optionInitiale.value = '';
            optionInitiale.textContent = 'Choisir un materiel...';
            selectMateriel.appendChild(optionInitiale);

            materiels.forEach(function (materiel) {
                const option = document.createElement('option');
                option.value = materiel.numero_serie;
                option.textContent = `${materiel.nom || 'Materiel'} - ${materiel.numero_serie}`;
                selectMateriel.appendChild(option);
            });

            selectMateriel.disabled = false;

            if (formulaire.dataset.materielASelectionner) {
                selectMateriel.value = formulaire.dataset.materielASelectionner;
                boutonConfirmer.disabled = !selectMateriel.value;
                delete formulaire.dataset.materielASelectionner;
            }
        } catch (erreur) {
            remplirSelectMateriel(selectMateriel, 'Impossible de charger les materiels');
            afficherErreurAffectationTicket('La liste des materiels disponibles n\'a pas pu etre chargee.');
        }
    });

    selectMateriel.addEventListener('change', function () {
        boutonConfirmer.disabled = !this.value;
    });

    if (formulaire.dataset.reouvrir === '1') {
        const boutonTicket = document.querySelector(
            `[data-ticket-id="${CSS.escape(formulaire.dataset.ancienTicketId || '')}"][data-affectation-url]`
        );

        if (boutonTicket) {
            ouvrirAffectationDepuisTicket(boutonTicket.dataset);

            const dateDebut = document.getElementById('ticket-affectation-date-debut');
            const dateFin = document.getElementById('ticket-affectation-date-fin');
            if (dateDebut && formulaire.dataset.ancienneDateDebut) {
                dateDebut.value = formulaire.dataset.ancienneDateDebut;
            }
            if (dateFin && formulaire.dataset.ancienneDateFin) {
                dateFin.value = formulaire.dataset.ancienneDateFin;
            }
            if (formulaire.dataset.ancienType) {
                formulaire.dataset.materielASelectionner = formulaire.dataset.ancienMateriel || '';
                selectTypeMateriel.value = formulaire.dataset.ancienType;
                selectTypeMateriel.dispatchEvent(new Event('change'));
            }
        }
    }
}

function remplirSelectMateriel(select, libelle) {
    select.innerHTML = '';
    const option = document.createElement('option');
    option.value = '';
    option.textContent = libelle;
    select.appendChild(option);
}

function initialiserEmpruntDepuisTicket() {
    const formulaire = document.getElementById('form-emprunt-depuis-ticket');
    const champTypeMateriel = document.getElementById('ticket-emprunt-materiel-type');
    const selectMateriel = document.getElementById('ticket-emprunt-materiel');
    const boutonConfirmer = document.getElementById('btn-confirmer-emprunt-ticket');

    if (!formulaire || !champTypeMateriel || !selectMateriel || !boutonConfirmer) return;

    champTypeMateriel.addEventListener('change', async function () {
        selectMateriel.disabled = true;
        boutonConfirmer.disabled = true;

        if (!this.value) {
            remplirSelectMateriel(selectMateriel, 'Choisir d\'abord un type');
            return;
        }

        remplirSelectMateriel(selectMateriel, 'Chargement...');

        try {
            const reponse = await fetch(
                `${formulaire.dataset.materielBaseUrl}/${encodeURIComponent(this.value)}`,
                { headers: { Accept: 'application/json' } }
            );

            if (!reponse.ok) throw new Error('Chargement impossible');

            const materiels = await reponse.json();
            selectMateriel.innerHTML = '';

            if (!materiels.length) {
                remplirSelectMateriel(selectMateriel, 'Aucun materiel disponible');
                return;
            }

            const optionInitiale = document.createElement('option');
            optionInitiale.value = '';
            optionInitiale.textContent = 'Choisir un materiel...';
            selectMateriel.appendChild(optionInitiale);

            materiels.forEach(function (materiel) {
                const option = document.createElement('option');
                option.value = materiel.numero_serie;
                option.textContent = `${materiel.nom || 'Materiel'} - ${materiel.numero_serie}`;
                selectMateriel.appendChild(option);
            });

            selectMateriel.disabled = false;

            if (formulaire.dataset.materielASelectionner) {
                selectMateriel.value = formulaire.dataset.materielASelectionner;
                boutonConfirmer.disabled = !selectMateriel.value;
                delete formulaire.dataset.materielASelectionner;
            }
        } catch (erreur) {
            remplirSelectMateriel(selectMateriel, 'Impossible de charger les materiels');
            afficherErreurEmpruntTicket('La liste des materiels disponibles n\'a pas pu etre chargee.');
        }
    });

    selectMateriel.addEventListener('change', function () {
        boutonConfirmer.disabled = !this.value;
    });

    champTypeMateriel.dispatchEvent(new Event('change'));

    if (formulaire.dataset.reouvrir === '1') {
        const boutonTicket = document.querySelector(
            `[data-ticket-id="${CSS.escape(formulaire.dataset.ancienTicketId || '')}"][data-emprunt-url]`
        );

        if (boutonTicket) {
            ouvrirEmpruntDepuisTicket(boutonTicket.dataset);

            const dateDebut = document.getElementById('ticket-emprunt-date-debut');
            const dateFin = document.getElementById('ticket-emprunt-date-fin');
            if (dateDebut && formulaire.dataset.ancienneDateDebut) {
                dateDebut.value = formulaire.dataset.ancienneDateDebut;
            }
            if (dateFin && formulaire.dataset.ancienneDateFin) {
                dateFin.value = formulaire.dataset.ancienneDateFin;
            }
            if (formulaire.dataset.ancienType) {
                formulaire.dataset.materielASelectionner = formulaire.dataset.ancienMateriel || '';
                champTypeMateriel.value = 'pc-portable';
                champTypeMateriel.dispatchEvent(new Event('change'));
            }
        }
    }
}

function afficherErreurEmpruntTicket(message) {
    const alerte = document.getElementById('ticket-emprunt-erreur');
    if (!alerte) return;

    alerte.textContent = message;
    alerte.classList.toggle('d-none', !message);
}

function afficherErreurAffectationTicket(message) {
    const alerte = document.getElementById('ticket-affectation-erreur');
    if (!alerte) return;

    alerte.textContent = message;
    alerte.classList.toggle('d-none', !message);
}

function ecrireDetailTicket(id, valeur) {
    const element = document.getElementById(id);
    if (!element) return;

    element.textContent = valeur || '-';
}

function appliquerClasseTicket(id, classeBase, classeEtat, texte) {
    const element = document.getElementById(id);
    if (!element) return;

    element.className = `${classeBase} ${classeEtat || ''}`.trim();
    element.textContent = texte || '-';
}

function ouvrirDetailTicket(bouton) {
    if (!bouton) return;

    const data = bouton.dataset;
    window.ticketActuel = data;
    const panneauDetail = document.getElementById('panneau-detail');
    const panneauBackdrop = document.getElementById('panneau-detail-backdrop');
    const boutonAssigner = document.getElementById('btn-assigner-ticket-detail');
    const boutonResoudre = document.getElementById('btn-resoudre-ticket-detail');
    const boutonRefuser = document.getElementById('btn-refuser-ticket-detail');
    const ticketTermine = ['resolu', 'refuse'].includes(data.statut);

    if (boutonAssigner) boutonAssigner.disabled = ticketTermine || data.estAssigne === '1';
    if (boutonResoudre) {
        boutonResoudre.classList.toggle('d-none', data.type !== 'incident');
        boutonResoudre.disabled = data.peutResoudre !== '1';
    }
    if (boutonRefuser) boutonRefuser.classList.toggle('d-none', data.peutRefuser !== '1');

    ecrireDetailTicket('detail-ticket-titre', data.titre);
    ecrireDetailTicket('detail-ticket-date', data.date);
    ecrireDetailTicket('detail-ticket-date-info', data.date);
    ecrireDetailTicket('detail-ticket-demandeur', data.demandeur);
    ecrireDetailTicket('detail-ticket-technicien', data.technicien);
    ecrireDetailTicket('detail-ticket-service', data.service);
    ecrireDetailTicket('detail-ticket-materiel-nom', data.materielNom);
    ecrireDetailTicket('detail-ticket-description', data.description);

    const sectionReponse = document.getElementById('detail-ticket-reponse-section');
    const reponsePresente = Boolean(data.reponseAdmin);
    if (sectionReponse) sectionReponse.classList.toggle('d-none', !reponsePresente);
    ecrireDetailTicket('detail-ticket-reponse-admin', data.reponseAdmin);
    ecrireDetailTicket(
        'detail-ticket-date-reponse',
        data.dateReponse ? `Mise à jour le ${data.dateReponse}` : ''
    );

    const sectionRefus = document.getElementById('detail-ticket-refus-section');
    if (sectionRefus) sectionRefus.classList.toggle('d-none', data.statut !== 'refuse');
    ecrireDetailTicket('detail-ticket-motif-refus', data.motifRefus);
    ecrireDetailTicket(
        'detail-ticket-date-refus',
        data.dateRefus ? `Refusée le ${data.dateRefus}` : ''
    );

    appliquerClasseTicket('detail-ticket-type-header', 'badge-ticket-type', data.typeClasse, data.typeLabel);
    appliquerClasseTicket('detail-ticket-type', 'badge-ticket-type', data.typeClasse, data.typeLabel);
    appliquerClasseTicket('detail-ticket-priorite', 'badge-priorite', data.priorite, data.prioriteLabel);
    appliquerClasseTicket('detail-ticket-statut', 'badge-ticket-statut', data.statutClasse, `● ${data.statutLabel || '-'}`);

    const boutonTraitement = document.getElementById('btn-traiter-demande-ticket');
    const iconeTraitement = document.getElementById('icone-traiter-demande-ticket');
    const libelleTraitement = document.getElementById('libelle-traiter-demande-ticket');
    const demandeTraitable = data.peutTraiter === '1' && ['affectation', 'emprunt'].includes(data.type);
    const incidentRepondable = data.peutRepondre === '1';
    const actionDisponible = demandeTraitable || incidentRepondable;

    if (boutonTraitement) {
        boutonTraitement.classList.toggle('d-none', !actionDisponible);
    }

    if (actionDisponible && iconeTraitement && libelleTraitement) {
        if (incidentRepondable) {
            iconeTraitement.className = 'bi bi-chat-left-text';
            libelleTraitement.textContent = data.reponseAdmin ? 'Modifier la réponse' : 'Répondre';
        } else {
            const estAffectation = data.type === 'affectation';
            iconeTraitement.className = `bi ${estAffectation ? 'bi-link-45deg' : 'bi-arrow-left-right'}`;
            libelleTraitement.textContent = estAffectation ? 'Affecter' : "Créer l'emprunt";
        }
    }

    const iconMateriel = document.getElementById('detail-ticket-materiel-icon');
    if (iconMateriel) {
        iconMateriel.className = `bi ${data.materielIcon || 'bi-box'}`;
    }

    const infosMateriel = data.materielSerie && data.materielSerie !== '-'
        ? `${data.materielType || 'Matériel'} - N/S : ${data.materielSerie}`
        : (data.materielType || 'Aucun matériel lié');
    ecrireDetailTicket('detail-ticket-materiel-info', infosMateriel);

    if (panneauDetail) {
        panneauDetail.classList.remove('d-none');
        panneauDetail.setAttribute('aria-hidden', 'false');
    }

    if (panneauBackdrop) {
        panneauBackdrop.classList.remove('d-none');
    }

    document.body.classList.add('detail-panel-open');
}

function fermerDetailTicket() {
    const panneauDetail = document.getElementById('panneau-detail');
    const panneauBackdrop = document.getElementById('panneau-detail-backdrop');

    if (panneauDetail) {
        panneauDetail.classList.add('d-none');
        panneauDetail.setAttribute('aria-hidden', 'true');
    }

    if (panneauBackdrop) {
        panneauBackdrop.classList.add('d-none');
    }

    document.body.classList.remove('detail-panel-open');
}

function ouvrirTraitementDemandeTicket() {
    const ticket = window.ticketActuel;
    if (!ticket) return;

    fermerDetailTicket();

    if (ticket.type === 'incident' && ticket.peutRepondre === '1') {
        ouvrirReponseIncident(ticket);
        return;
    }

    if (ticket.peutTraiter !== '1') return;

    if (ticket.type === 'affectation') {
        ouvrirAffectationDepuisTicket(ticket);
        return;
    }

    if (ticket.type === 'emprunt') {
        ouvrirEmpruntDepuisTicket(ticket);
    }
}

function ouvrirReponseIncident(ticket) {
    const modalElement = document.getElementById('modalReponseIncident');
    const formulaire = document.getElementById('form-reponse-incident');
    const titreModal = document.getElementById('titre-modal-reponse-incident');
    const titreTicket = document.getElementById('reponse-incident-titre-ticket');
    const champReponse = document.getElementById('reponse-admin-ticket');
    const libelleEnvoi = document.getElementById('libelle-envoi-reponse-incident');

    if (!modalElement || !formulaire || !ticket.reponseUrl) return;

    const estModification = Boolean(ticket.reponseAdmin);
    formulaire.action = ticket.reponseUrl;
    if (titreModal) titreModal.textContent = estModification ? 'Modifier la réponse' : "Répondre à l'incident";
    if (titreTicket) titreTicket.textContent = ticket.titre || 'l\'incident sélectionné';
    if (champReponse) champReponse.value = ticket.reponseAdmin || '';
    if (libelleEnvoi) {
        libelleEnvoi.textContent = estModification ? 'Enregistrer la modification' : 'Envoyer la réponse';
    }

    bootstrap.Modal.getOrCreateInstance(modalElement).show();
}

function ouvrirAffectationDepuisTicket(ticket) {
    const modalElement = document.getElementById('modalAffectationDepuisTicket');
    const formulaire = document.getElementById('form-affectation-depuis-ticket');
    const demandeur = document.getElementById('ticket-affectation-demandeur');
    const ticketId = document.getElementById('ticket-affectation-id');
    const selectType = document.getElementById('ticket-affectation-materiel-type');
    const selectMateriel = document.getElementById('ticket-affectation-materiel');
    const boutonConfirmer = document.getElementById('btn-confirmer-affectation-ticket');
    const blocDateFin = document.getElementById('ticket-affectation-bloc-date-fin');
    const dateFin = document.getElementById('ticket-affectation-date-fin');

    if (!modalElement || !formulaire || !ticket.affectationUrl) return;

    formulaire.action = ticket.affectationUrl;
    formulaire.reset();
    afficherErreurAffectationTicket('');

    if (demandeur) demandeur.value = ticket.demandeur || '';
    if (ticketId) ticketId.value = ticket.ticketId || '';
    if (selectType) selectType.value = '';
    if (selectMateriel) {
        remplirSelectMateriel(selectMateriel, 'Choisir d\'abord un type');
        selectMateriel.disabled = true;
    }
    if (boutonConfirmer) boutonConfirmer.disabled = true;

    const estCdi = ticket.typeContrat === 'cdi';
    if (blocDateFin) blocDateFin.classList.toggle('d-none', estCdi);
    if (dateFin) {
        dateFin.required = !estCdi;
        dateFin.disabled = estCdi;
        if (estCdi) dateFin.value = '';
    }

    bootstrap.Modal.getOrCreateInstance(modalElement).show();
}

function ouvrirEmpruntDepuisTicket(ticket) {
    const modalElement = document.getElementById('modalEmpruntDepuisTicket');
    const formulaire = document.getElementById('form-emprunt-depuis-ticket');
    const demandeur = document.getElementById('ticket-emprunt-demandeur');
    const ticketId = document.getElementById('ticket-emprunt-id');
    const selectType = document.getElementById('ticket-emprunt-materiel-type');
    const selectMateriel = document.getElementById('ticket-emprunt-materiel');
    const boutonConfirmer = document.getElementById('btn-confirmer-emprunt-ticket');

    if (!modalElement || !formulaire || !ticket.empruntUrl) return;

    formulaire.action = ticket.empruntUrl;
    formulaire.reset();
    afficherErreurEmpruntTicket('');

    if (demandeur) demandeur.value = ticket.demandeur || '';
    if (ticketId) ticketId.value = ticket.ticketId || '';
    if (selectType) {
        selectType.value = 'pc-portable';
        selectType.dispatchEvent(new Event('change'));
    }
    if (selectMateriel) {
        remplirSelectMateriel(selectMateriel, 'Chargement des PC portables...');
        selectMateriel.disabled = true;
    }
    if (boutonConfirmer) boutonConfirmer.disabled = true;

    bootstrap.Modal.getOrCreateInstance(modalElement).show();
}

function ouvrirConfirmationAssignationTicket(bouton) {
    if (!bouton) return;

    ouvrirModalAssignationTicket(bouton.dataset.url, bouton.dataset.titre);
}

function ouvrirConfirmationAssignationDepuisDetail() {
    const ticket = window.ticketActuel;
    if (!ticket) return;

    fermerDetailTicket();
    ouvrirModalAssignationTicket(ticket.assignUrl, ticket.titre);
}

function ouvrirModalAssignationTicket(url, titreTicket) {
    if (!url) return;

    const formulaire = document.getElementById('form-assigner-ticket');
    const titre = document.getElementById('confirmation-assignation-ticket');
    const modalElement = document.getElementById('modalConfirmationAssignationTicket');

    if (!formulaire || !modalElement) return;

    formulaire.action = url;

    if (titre) {
        titre.textContent = titreTicket || 'sélectionné';
    }

    bootstrap.Modal.getOrCreateInstance(modalElement).show();
}

function ouvrirConfirmationResolutionTicket(bouton) {
    if (!bouton) return;

    ouvrirModalResolutionTicket(bouton.dataset.url, bouton.dataset.titre);
}

function ouvrirConfirmationResolutionDepuisDetail() {
    const ticket = window.ticketActuel;
    if (!ticket) return;

    fermerDetailTicket();
    ouvrirModalResolutionTicket(ticket.resolveUrl, ticket.titre);
}

function ouvrirModalResolutionTicket(url, titreTicket) {
    if (!url) return;

    const formulaire = document.getElementById('form-resoudre-ticket');
    const titre = document.getElementById('confirmation-resolution-ticket');
    const modalElement = document.getElementById('modalConfirmationResolutionTicket');

    if (!formulaire || !modalElement) return;

    formulaire.action = url;

    if (titre) {
        titre.textContent = titreTicket || 'sélectionné';
    }

    bootstrap.Modal.getOrCreateInstance(modalElement).show();
}

function ouvrirRefusDepuisDetail() {
    const ticket = window.ticketActuel;
    if (!ticket || ticket.peutRefuser !== '1' || !ticket.refuseUrl) return;

    const formulaire = document.getElementById('form-refuser-ticket');
    const titre = document.getElementById('refus-ticket-titre');
    const motif = document.getElementById('motif-refus-ticket');
    const modalElement = document.getElementById('modalRefusTicket');

    if (!formulaire || !modalElement) return;

    formulaire.action = ticket.refuseUrl;
    if (titre) titre.textContent = ticket.titre || 'sélectionnée';
    if (motif) motif.value = '';

    fermerDetailTicket();
    bootstrap.Modal.getOrCreateInstance(modalElement).show();
}

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        fermerDetailTicket();
    }
});
