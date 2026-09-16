// public/js/tickets-ajout.js

document.addEventListener('DOMContentLoaded', function () {

    // Le champ "Numéro de série" n'a de sens que pour un incident
    // (une demande d'affectation/emprunt ne porte pas encore sur un matériel précis)
    const selectType = document.getElementById('type');
    const champNumeroSerie = document.getElementById('numero_serie');
    const aideNumeroSerie = document.getElementById('numero-serie-aide');

    function mettreAJourChampNumeroSerie() {
        if (!selectType || !champNumeroSerie) return;

        if (selectType.value === 'incident') {
            champNumeroSerie.disabled = false;
            if (aideNumeroSerie) {
                aideNumeroSerie.textContent = 'Renseignez le numéro de série du matériel concerné par l\'incident.';
            }
        } else {
            champNumeroSerie.disabled = true;
            champNumeroSerie.value = '';
            if (aideNumeroSerie) {
                aideNumeroSerie.textContent = selectType.value
                    ? 'Le numéro de série n\'est pas demandé pour une demande d\'affectation ou d\'emprunt.'
                    : 'Choisissez d\'abord un type de ticket.';
            }
        }
    }

    if (selectType) {
        selectType.addEventListener('change', mettreAJourChampNumeroSerie);
        // On applique l'état correct dès l'ouverture du modal (au cas où
        // un type serait déjà sélectionné, par exemple après une erreur de validation)
        mettreAJourChampNumeroSerie();
    }

});

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
    const panneauDetail = document.getElementById('panneau-detail');
    const colonneTableau = document.getElementById('colonne-tableau');

    ecrireDetailTicket('detail-ticket-code', data.code);
    ecrireDetailTicket('detail-ticket-titre', data.titre);
    ecrireDetailTicket('detail-ticket-date', data.date);
    ecrireDetailTicket('detail-ticket-date-info', data.date);
    ecrireDetailTicket('detail-ticket-demandeur', data.demandeur);
    ecrireDetailTicket('detail-ticket-technicien', data.technicien);
    ecrireDetailTicket('detail-ticket-service', data.service);
    ecrireDetailTicket('detail-ticket-materiel-nom', data.materielNom);
    ecrireDetailTicket('detail-ticket-description', data.description);

    appliquerClasseTicket('detail-ticket-type-header', 'badge-ticket-type', data.typeClasse, data.typeLabel);
    appliquerClasseTicket('detail-ticket-type', 'badge-ticket-type', data.typeClasse, data.typeLabel);
    appliquerClasseTicket('detail-ticket-priorite', 'badge-priorite', data.priorite, data.prioriteLabel);
    appliquerClasseTicket('detail-ticket-statut', 'badge-ticket-statut', data.statutClasse, `● ${data.statutLabel || '-'}`);

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
    }

    if (colonneTableau) {
        colonneTableau.classList.remove('col-12');
        colonneTableau.classList.add('col-12', 'col-lg-8');
    }
}

function fermerDetailTicket() {
    const panneauDetail = document.getElementById('panneau-detail');
    const colonneTableau = document.getElementById('colonne-tableau');

    if (panneauDetail) {
        panneauDetail.classList.add('d-none');
    }

    if (colonneTableau) {
        colonneTableau.classList.remove('col-lg-8');
        colonneTableau.classList.add('col-12');
    }
}

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        fermerDetailTicket();
    }
});
