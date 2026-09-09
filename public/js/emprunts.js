// public/js/emprunts.js

function remplirDetailEmprunt(id, valeur) {
    var element = document.getElementById(id);
    if (element) element.textContent = valeur || '-';
}

function ouvrirDetailEmprunt(bouton) {
    var data = bouton.dataset;
    window.empruntActuel = data;

    remplirDetailEmprunt('detail-reference', data.reference);
    remplirDetailEmprunt('detail-nom', data.nom);
    remplirDetailEmprunt('detail-emprunt-email', data.email);
    remplirDetailEmprunt('detail-emprunt-materiel', data.materielNom);
    remplirDetailEmprunt('detail-emprunt-materiel-info', data.materielInfo);
    remplirDetailEmprunt('detail-emprunt-date-debut', data.dateDebut);
    remplirDetailEmprunt('detail-emprunt-date-fin', data.dateFinPrevue);
    remplirDetailEmprunt('detail-emprunt-alerte-titre', data.alerteTitre);
    remplirDetailEmprunt('detail-emprunt-alerte-texte', data.alerteTexte);

    var avatar = document.getElementById('detail-emprunt-avatar');
    if (avatar) {
        avatar.textContent = data.initiales || '-';
        avatar.style.background = data.avatarColor || '#3b82f6';
    }

    var typeBadge = document.getElementById('detail-emprunt-type');
    if (typeBadge) {
        typeBadge.textContent = data.typeLabel || '-';
        typeBadge.className = 'badge-type-emprunt ' + (data.typeClass || 'etudiant');
    }

    var echeanceBadge = document.getElementById('detail-emprunt-echeance');
    if (echeanceBadge) {
        echeanceBadge.textContent = data.echeanceLabel || '-';
        echeanceBadge.className = 'echeance ' + (data.echeanceClass || 'normal');
    }

    var stepAttente = document.getElementById('detail-emprunt-step-attente');
    var stepRendu = document.getElementById('detail-emprunt-step-rendu');
    var ligneRendu = document.getElementById('detail-emprunt-ligne-rendu');

    if (stepAttente) stepAttente.className = 'timeline-circle ' + (data.statut === 'rendu' ? 'done' : 'attente');
    if (stepRendu) stepRendu.className = 'timeline-circle ' + (data.statut === 'rendu' ? 'done' : 'rendu');
    if (ligneRendu) ligneRendu.className = 'timeline-line' + (data.statut === 'rendu' ? ' done' : '');

    var btnRetour = document.getElementById('btn-valider-retour');
    if (btnRetour) {
        btnRetour.disabled = data.statut === 'rendu';
        btnRetour.classList.toggle('disabled', data.statut === 'rendu');
    }

    var btnProlonger = document.getElementById('btn-prolonger-emprunt');
    if (btnProlonger) {
        btnProlonger.disabled = data.statut === 'rendu';
        btnProlonger.classList.toggle('disabled', data.statut === 'rendu');
    }

    var panneauDetail = document.getElementById('panneau-detail');
    var panneauBackdrop = document.getElementById('panneau-detail-backdrop');

    if (panneauDetail) panneauDetail.classList.remove('d-none');
    if (panneauBackdrop) panneauBackdrop.classList.remove('d-none');
    document.body.classList.add('detail-panel-open');
}

function fermerDetailEmprunt() {
    var panneauDetail = document.getElementById('panneau-detail');
    var panneauBackdrop = document.getElementById('panneau-detail-backdrop');

    if (panneauDetail) panneauDetail.classList.add('d-none');
    if (panneauBackdrop) panneauBackdrop.classList.add('d-none');
    document.body.classList.remove('detail-panel-open');
}

function initialiserRetourEmprunt() {
    var btnRetour = document.getElementById('btn-valider-retour');
    var formRetour = document.getElementById('form-valider-retour');
    var texteRetour = document.getElementById('confirmation-retour-materiel');
    var modalRetour = document.getElementById('modalConfirmationRetour');

    if (!btnRetour || !formRetour || !modalRetour) return;

    btnRetour.addEventListener('click', function() {
        var data = window.empruntActuel || {};

        if (!data.retourUrl || data.statut === 'rendu') return;

        formRetour.action = data.retourUrl;
        if (texteRetour) {
            texteRetour.textContent = data.materielNom || 'ce materiel';
        }

        fermerDetailEmprunt();
        bootstrap.Modal.getOrCreateInstance(modalRetour).show();
    });
}

function dateIsoPlusUnJour(dateIso) {
    if (!dateIso) return '';

    var date = new Date(dateIso + 'T00:00:00');
    if (Number.isNaN(date.getTime())) return '';

    date.setDate(date.getDate() + 1);
    return date.toISOString().slice(0, 10);
}

function initialiserProlongationEmprunt() {
    var btnProlonger = document.getElementById('btn-prolonger-emprunt');
    var formProlongation = document.getElementById('form-prolonger-emprunt');
    var texteMateriel = document.getElementById('prolongation-emprunt-materiel');
    var dateActuelle = document.getElementById('prolongation-date-actuelle');
    var nouvelleDate = document.getElementById('prolongation-date-fin');
    var modalProlongation = document.getElementById('modalProlongationEmprunt');

    if (!btnProlonger || !formProlongation || !modalProlongation || !nouvelleDate) return;

    btnProlonger.addEventListener('click', function() {
        var data = window.empruntActuel || {};

        if (!data.prolongationUrl || data.statut === 'rendu') return;

        var dateMin = dateIsoPlusUnJour(data.dateFinPrevueIso);

        formProlongation.action = data.prolongationUrl;
        nouvelleDate.value = dateMin;
        nouvelleDate.min = dateMin;

        if (texteMateriel) {
            texteMateriel.textContent = data.materielNom || 'ce materiel';
        }

        if (dateActuelle) {
            dateActuelle.value = data.dateFinPrevue || '-';
        }

        fermerDetailEmprunt();
        bootstrap.Modal.getOrCreateInstance(modalProlongation).show();
    });
}

function initialiserSuppressionEmprunt() {
    var btnSuppression = document.getElementById('btn-supprimer-emprunt');
    var formSuppression = document.getElementById('form-supprimer-emprunt');
    var texteSuppression = document.getElementById('confirmation-suppression-emprunt');
    var modalSuppression = document.getElementById('modalConfirmationSuppressionEmprunt');

    if (!btnSuppression || !formSuppression || !modalSuppression) return;

    btnSuppression.addEventListener('click', function() {
        var data = window.empruntActuel || {};

        if (!data.suppressionUrl) return;

        formSuppression.action = data.suppressionUrl;
        if (texteSuppression) {
            texteSuppression.textContent = data.materielNom || 'ce materiel';
        }

        fermerDetailEmprunt();
        bootstrap.Modal.getOrCreateInstance(modalSuppression).show();
    });
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') fermerDetailEmprunt();
});

document.addEventListener('DOMContentLoaded', function () {
    initialiserRetourEmprunt();
    initialiserProlongationEmprunt();
    initialiserSuppressionEmprunt();

    // ---------- 1. Autocomplete Étudiant ----------
    const champEtudiant = document.getElementById('champ-etudiant');
    const etudiantIdInput = document.getElementById('etudiant-id');
    const suggestionsEtudiant = document.getElementById('suggestions-etudiant');

    if (champEtudiant) {
        let timeoutRecherche = null;

        champEtudiant.addEventListener('input', function () {
            const texte = champEtudiant.value.trim();

            // On efface l'id caché tant que l'utilisateur retape (il doit re-choisir dans la liste)
            etudiantIdInput.value = '';

            clearTimeout(timeoutRecherche);

            if (texte.length < 2) {
                suggestionsEtudiant.classList.add('d-none');
                suggestionsEtudiant.innerHTML = '';
                return;
            }

            // On attend 300ms après la dernière frappe avant de chercher (évite trop de requêtes)
            timeoutRecherche = setTimeout(function () {
                const url = champEtudiant.dataset.etudiantsUrl + '?q=' + encodeURIComponent(texte);

                fetch(url)
                    .then(function (reponse) { return reponse.json(); })
                    .then(function (resultats) {
                        afficherSuggestionsEtudiant(resultats);
                    })
                    .catch(function () {
                        suggestionsEtudiant.classList.add('d-none');
                    });
            }, 300);
        });

        function afficherSuggestionsEtudiant(resultats) {
            suggestionsEtudiant.innerHTML = '';

            if (!resultats || resultats.length === 0) {
                suggestionsEtudiant.classList.add('d-none');
                return;
            }

            resultats.forEach(function (etudiant) {
                const nom = etudiant.name || etudiant.nom || 'Inconnu';
                const email = etudiant.email || '';

                const item = document.createElement('div');
                item.className = 'suggestion-item';
                item.style.padding = '8px 12px';
                item.style.cursor = 'pointer';
                item.innerHTML = '<div class="fw-semibold">' + nom + '</div>' +
                                  '<div class="text-muted small">' + email + '</div>';

                item.addEventListener('click', function () {
                    champEtudiant.value = nom;
                    etudiantIdInput.value = etudiant.etudiant_id;
                    suggestionsEtudiant.classList.add('d-none');
                    suggestionsEtudiant.innerHTML = '';
                });

                suggestionsEtudiant.appendChild(item);
            });

            suggestionsEtudiant.classList.remove('d-none');
        }

        // On ferme la liste de suggestions si on clique ailleurs sur la page
        document.addEventListener('click', function (e) {
            if (!champEtudiant.contains(e.target) && !suggestionsEtudiant.contains(e.target)) {
                suggestionsEtudiant.classList.add('d-none');
            }
        });
    }

    // ---------- 2. Select Matériel dynamique selon le Type ----------
    const selectType = document.getElementById('materiel_type');
    const selectMateriel = document.getElementById('materiel_id');

    if (selectType) {
        selectType.addEventListener('change', function () {
            const type = selectType.value;

            // Reset du select matériel pendant le chargement
            selectMateriel.innerHTML = '<option value="">Chargement...</option>';
            selectMateriel.disabled = true;

            if (!type) {
                selectMateriel.innerHTML = '<option value="">Choisir d\'abord un type</option>';
                return;
            }

            fetch(selectType.dataset.baseUrl + '/' + type)
                .then(function (reponse) { return reponse.json(); })
                .then(function (materiels) {
                    selectMateriel.innerHTML = '';

                    if (materiels.length === 0) {
                        selectMateriel.innerHTML = '<option value="">Aucun matériel disponible</option>';
                        selectMateriel.disabled = true;
                        return;
                    }

                    const optionVide = document.createElement('option');
                    optionVide.value = '';
                    optionVide.textContent = 'Choisir...';
                    selectMateriel.appendChild(optionVide);

                    materiels.forEach(function (materiel) {
                        const option = document.createElement('option');
                        option.value = materiel.id;
                        option.textContent = materiel.nom;
                        selectMateriel.appendChild(option);
                    });

                    selectMateriel.disabled = false;
                })
                .catch(function () {
                    selectMateriel.innerHTML = '<option value="">Erreur de chargement</option>';
                });
        });
    }

});
