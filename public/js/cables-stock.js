// public/js/cables-stock.js

document.addEventListener('DOMContentLoaded', function () {

    var cableId = new URLSearchParams(window.location.search).get('cable');
    if (cableId) {
        var boutonCable = document.querySelector('[data-cable-id="' + cableId + '"]');
        if (boutonCable && typeof ouvrirDetail === 'function') ouvrirDetail(boutonCable);
    }

    // Retient quelle action est en cours ('ajouter' ou 'retirer') pendant
    // que la popup de quantité est ouverte
    var actionStockEnCours = null;

    var champQuantite = document.getElementById('champ-quantite-stock');
    var titreModal = document.getElementById('modalQuantiteTitre');
    var modalQuantite = document.getElementById('modalQuantiteStock')
        ? new bootstrap.Modal(document.getElementById('modalQuantiteStock'))
        : null;

    // Le panneau détail reste ouvert derrière : on force le modal (et son
    // fond sombre) à passer au-dessus via leur z-index.
    function afficherModalQuantiteAuPremierPlan() {
        modalQuantite.show();
        setTimeout(function () {
            var backdrops = document.querySelectorAll('.modal-backdrop');
            var derniereBackdrop = backdrops[backdrops.length - 1];
            if (derniereBackdrop) derniereBackdrop.style.zIndex = 1065;
        }, 10);
    }

    var btnAjouter = document.getElementById('btn-ajouter-stock');
    if (btnAjouter) {
        btnAjouter.addEventListener('click', function () {
            if (!window.materielActuel || !modalQuantite) return;
            actionStockEnCours = 'ajouter';
            titreModal.textContent = 'Ajouter au stock';
            champQuantite.value = 1;
            afficherModalQuantiteAuPremierPlan();
        });
    }

    var btnRetirer = document.getElementById('btn-retirer-stock');
    if (btnRetirer) {
        btnRetirer.addEventListener('click', function () {
            if (!window.materielActuel || !modalQuantite) return;
            actionStockEnCours = 'retirer';
            titreModal.textContent = 'Retirer du stock';
            champQuantite.value = 1;
            afficherModalQuantiteAuPremierPlan();
        });
    }

    var btnConfirmer = document.getElementById('btn-confirmer-quantite-stock');
    if (btnConfirmer) {
        btnConfirmer.addEventListener('click', function () {
            var data = window.materielActuel;
            if (!data || !actionStockEnCours) return;

            var quantite = parseInt(champQuantite.value, 10);
            if (!quantite || quantite < 1) quantite = 1;

            var idFormulaire = actionStockEnCours === 'ajouter' ? 'form-ajouter-stock' : 'form-retirer-stock';
            var suffixeUrl = actionStockEnCours === 'ajouter' ? 'ajouter-stock' : 'retirer-stock';
            var form = document.getElementById(idFormulaire);

            form.action = form.dataset.urlBase + '/' + data.id + '/' + suffixeUrl;

            // On ajoute (ou met à jour) un champ caché "quantite" avec la valeur choisie
            var champCache = form.querySelector('input[name="quantite"]');
            if (!champCache) {
                champCache = document.createElement('input');
                champCache.type = 'hidden';
                champCache.name = 'quantite';
                form.appendChild(champCache);
            }
            champCache.value = quantite;

            form.submit();
        });
    }

});
