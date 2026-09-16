// public/js/materiel-crud.js
// Script générique de Modifier / Supprimer, réutilisable sur toutes les pages
// matériel (PC Portables, Mini PC, Écrans, Imprimantes, Périphériques...).
//
// Convention à respecter dans le blade de chaque page pour que ça fonctionne :
//   - Le bouton œil a un data-id + un data-<champ> pour chaque donnée du matériel
//     (déjà en place, utilisé par ouvrirDetail() dans materiel.js)
//   - Chaque input/select du modal "Ajouter" a un id="champ-<nom-en-kebab-case>"
//     qui correspond au data-<nom> du bouton œil (ex: data-numero-serie -> #champ-numero-serie)
//   - Le formulaire du modal a id="form-modal-materiel" avec data-store-url et data-update-url-base
//   - Les boutons ont id="btn-modifier" / id="btn-supprimer"
//   - Le titre du modal a id="modalAjoutTitre", le bouton d'envoi id="btn-submit-modal"
//   - Le formulaire de suppression caché a id="form-suppression" avec data-delete-url-base
//   - La popup de confirmation a id="modalConfirmSuppression" et id="texte-nom-suppression"

// Convertit une clé de dataset camelCase (ex: "numeroSerie") en id kebab-case ("numero-serie")
function camelVersKebab(texte) {
    return texte.replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase();
}

// Clés du dataset qui ne correspondent pas à un champ du formulaire (à ignorer)
var CLES_IGNOREES = ['typeMateriel', 'id', 'etat', 'etatLabel', 'affecteA', 'historique', 'dateAchat', 'dateAchatIso'];

function gererChampAutre(select) {
    if (!select) return;

    var blocAutre = document.getElementById(select.dataset.autreTarget);
    if (!blocAutre) return;

    var champAutre = blocAutre.querySelector('input');
    var autreSelectionne = select.value === 'autre';

    blocAutre.classList.toggle('d-none', !autreSelectionne);

    if (champAutre) {
        champAutre.required = autreSelectionne;
        if (!autreSelectionne) champAutre.value = '';
    }
}

function definirValeurSelectOuAutre(idSelect, valeur) {
    var select = document.getElementById(idSelect);
    if (!select) return;
    if (!select.options) return;

    var valeurPropre = (valeur === '-' || valeur === undefined) ? '' : valeur;
    valeurPropre = valeurPropre.replace(/\s*pouces?$/i, '');
    var optionExiste = Array.from(select.options).some(function (option) {
        return option.value === valeurPropre;
    });

    if (!valeurPropre || optionExiste) {
        select.value = valeurPropre;
        gererChampAutre(select);
        return;
    }

    select.value = 'autre';
    gererChampAutre(select);

    var blocAutre = document.getElementById(select.dataset.autreTarget);
    var champAutre = blocAutre ? blocAutre.querySelector('input') : null;
    if (champAutre) champAutre.value = valeurPropre;
}

// Remet le modal en mode "Ajouter" propre (appelé au clic sur le bouton "Ajouter ...")
function reinitialiserModalMateriel() {
    var form = document.getElementById('form-modal-materiel');
    if (!form) return;

    form.reset();
    form.action = form.dataset.storeUrl;

    var champMethode = document.getElementById('input-method-materiel');
    if (champMethode) champMethode.remove();

    var titre = document.getElementById('modalAjoutTitre');
    if (titre) titre.innerHTML = titre.dataset.titreAjout || titre.innerHTML;

    var btnSubmit = document.getElementById('btn-submit-modal');
    if (btnSubmit) btnSubmit.innerHTML = '<i class="bi bi-save me-1"></i> Enregistrer';

    // Réactive les champs identifiants (réf/n° série) marqués avec la classe dédiée
    document.querySelectorAll('.champ-identifiant').forEach(function (champ) {
        champ.disabled = false;
    });

    var champEtat = document.getElementById('etat');
    if (champEtat) champEtat.disabled = false;

    var aideEtat = document.getElementById('etat-aide-edition');
    if (aideEtat) aideEtat.classList.add('d-none');

    document.querySelectorAll('.champ-select-autre').forEach(function (select) {
        gererChampAutre(select);
    });
}

// Bascule le modal en mode "Modifier" et le pré-remplit avec le matériel actuellement affiché
function ouvrirModalModification() {
    var data = window.materielActuel;
    if (!data) return;

    var form = document.getElementById('form-modal-materiel');
    form.action = form.dataset.updateUrlBase + '/' + encodeURIComponent(data.id || '');

    // Laravel a besoin d'un champ _method=PUT pour simuler une requête PUT depuis un <form>
    var champMethode = document.getElementById('input-method-materiel');
    if (!champMethode) {
        champMethode = document.createElement('input');
        champMethode.type = 'hidden';
        champMethode.name = '_method';
        champMethode.id = 'input-method-materiel';
        champMethode.value = 'PUT';
        form.prepend(champMethode);
    }

    // Pré-remplissage automatique : pour chaque data-xxx du bouton œil, on
    // cherche le champ #champ-xxx correspondant dans le formulaire et on le remplit.
    Object.keys(data).forEach(function (cle) {
        if (CLES_IGNOREES.indexOf(cle) !== -1) return;

        var idChamp = 'champ-' + camelVersKebab(cle);
        var champ = document.getElementById(idChamp);
        if (champ) {
            var valeur = data[cle];
            champ.value = (valeur === '-' || valeur === undefined) ? '' : valeur;
        }
    });

    // Cas particulier : la date d'achat a besoin du format ISO (YYYY-MM-DD) pour un input type="date"
    var champDate = document.getElementById('champ-date-achat');
    if (champDate) {
        champDate.value = data.dateAchatIso || '';
    }

    var champEcran = document.getElementById('champ-ecran');
    if (champEcran && champEcran.value) {
        champEcran.value = champEcran.value.replace(/\s*pouces?$/i, '');
    }

    var champRefresh = document.getElementById('champ-refresh');
    if (champRefresh && champRefresh.value) {
        champRefresh.value = champRefresh.value.replace(/\s*Hz$/i, '');
    }

    definirValeurSelectOuAutre('champ-ram', data.ram);
    definirValeurSelectOuAutre('champ-stockage', data.stockage);
    definirValeurSelectOuAutre('champ-os', data.os);
    definirValeurSelectOuAutre('champ-taille', data.taille);
    definirValeurSelectOuAutre('champ-resolution', data.resolution);
    definirValeurSelectOuAutre('champ-dalle', data.dalle);
    definirValeurSelectOuAutre('champ-connexion', data.connexion);
    definirValeurSelectOuAutre('champ-disposition', data.disposition);
    definirValeurSelectOuAutre('champ-type-impression', data.typeImpression);
    definirValeurSelectOuAutre('champ-nom', data.nom);

    // Champs identifiants : non modifiables en édition (disabled = grisé automatique + non envoyé au serveur)
    document.querySelectorAll('.champ-identifiant').forEach(function (champ) {
        champ.disabled = true;
    });

    // État / À qui : le changement d'affectation passe par les pages Affectations/Emprunts, pas par ce formulaire
    var champEtat = document.getElementById('etat');
    if (champEtat) {
        champEtat.value = data.etat || champEtat.value;
        champEtat.disabled = true;
    }
    var aideEtat = document.getElementById('etat-aide-edition');
    if (aideEtat) aideEtat.classList.remove('d-none');

    var champAQui = document.getElementById('champ-a-qui');
    if (champAQui) {
        champAQui.disabled = true;
        champAQui.placeholder = 'Non modifiable ici';
    }

    var titre = document.getElementById('modalAjoutTitre');
    if (titre) {
        titre.dataset.titreAjout = titre.dataset.titreAjout || titre.innerHTML;
        titre.innerHTML = '<i class="bi bi-pencil me-2"></i>' + (titre.dataset.titreModifier || 'Modifier');
    }

    var btnSubmit = document.getElementById('btn-submit-modal');
    if (btnSubmit) btnSubmit.innerHTML = '<i class="bi bi-save me-1"></i> Enregistrer les modifications';

    fermerDetail();
    var modal = new bootstrap.Modal(document.getElementById('modalAjout'));
    modal.show();
}

// Ouvre la popup de confirmation de suppression, pré-remplie avec le nom du matériel
function ouvrirConfirmationSuppression() {
    var data = window.materielActuel;
    if (!data) return;

    var texteNom = document.getElementById('texte-nom-suppression');
    if (texteNom) texteNom.textContent = data.nom || 'ce matériel';

    var formSuppression = document.getElementById('form-suppression');
    formSuppression.action = formSuppression.dataset.deleteUrlBase + '/' + encodeURIComponent(data.id || '');

    fermerDetail();
    var modal = new bootstrap.Modal(document.getElementById('modalConfirmSuppression'));
    modal.show();
}

// Bascule l'état du matériel entre "en panne" (ou "hors_service") et "disponible",
// selon son état actuel (déduit du texte déjà affiché sur le bouton par materiel.js)
function toggleEtatPanne() {
    var data = window.materielActuel;
    if (!data) return;

    var enPanneOuHorsService = (data.etat === 'en_panne' || data.etat === 'hors_service');
    var action = enPanneOuHorsService ? 'reparer' : 'panne';

    var formPanne = document.getElementById('form-panne');
    if (!formPanne) return;

    formPanne.action = formPanne.dataset.panneUrlBase + '/' + encodeURIComponent(data.id || '') + '/' + action;

    fermerDetail();
    formPanne.submit();
}

document.addEventListener('DOMContentLoaded', function () {
    var btnModifier = document.getElementById('btn-modifier');
    if (btnModifier) btnModifier.addEventListener('click', ouvrirModalModification);

    var btnSupprimer = document.getElementById('btn-supprimer');
    if (btnSupprimer) btnSupprimer.addEventListener('click', ouvrirConfirmationSuppression);

    var btnConfirmerSuppression = document.getElementById('btn-confirmer-suppression');
    if (btnConfirmerSuppression) {
        btnConfirmerSuppression.addEventListener('click', function () {
            document.getElementById('form-suppression').submit();
        });
    }

    var btnIncident = document.getElementById('btn-incident');
    if (btnIncident) btnIncident.addEventListener('click', toggleEtatPanne);

    document.querySelectorAll('.champ-select-autre').forEach(function (select) {
        select.addEventListener('change', function () {
            gererChampAutre(select);
        });

        gererChampAutre(select);
    });
});
