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