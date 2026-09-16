// public/js/affectations.js

function remplirDetailAffectation(id, valeur) {
    const element = document.getElementById(id);

    if (element) {
        element.textContent = valeur || '-';
    }
}

function ouvrirDetailAffectation(bouton) {
    const data = bouton.dataset;
    const panneauDetail = document.getElementById('panneau-detail');
    const panneauBackdrop = document.getElementById('panneau-detail-backdrop');
    const formulaireRetour = document.getElementById('form-valider-retour-affectation');
    const boutonRetour = document.getElementById('btn-valider-retour-affectation');
    const boutonModifier = document.getElementById('btn-modifier-affectation');
    const boutonSuppression = document.getElementById('btn-supprimer-affectation');
    const statutBadge = document.getElementById('detail-affectation-statut');

    remplirDetailAffectation('detail-affectation-id', data.id);
    remplirDetailAffectation('detail-affectation-utilisateur', data.utilisateur);
    remplirDetailAffectation('detail-affectation-nom', data.utilisateur);
    remplirDetailAffectation('detail-affectation-poste', data.poste);
    remplirDetailAffectation('detail-affectation-service', data.service);
    remplirDetailAffectation('detail-affectation-poste-info', data.poste);
    remplirDetailAffectation('detail-affectation-service-info', data.service);
    remplirDetailAffectation('detail-affectation-type', data.materielTypeLabel);
    remplirDetailAffectation('detail-affectation-materiel', data.materielNom);
    remplirDetailAffectation('detail-affectation-serie', data.numeroSerie);
    remplirDetailAffectation('detail-affectation-date-debut', data.dateDebutLabel);
    remplirDetailAffectation('detail-affectation-date-fin', data.dateFinLabel);

    if (statutBadge) {
        const statuts = {
            active: { label: 'Active', classe: 'disponible' },
            rendu: { label: 'Rendu', classe: 'affecte' }
        };
        const statut = statuts[data.statut] || { label: data.statut, classe: 'maintenance' };

        statutBadge.className = 'badge-etat ' + statut.classe;
        statutBadge.textContent = '● ' + statut.label;
    }

    if (formulaireRetour) {
        formulaireRetour.action = data.retourUrl;
    }

    if (boutonRetour) {
        const dejaRendu = data.statut === 'rendu';
        boutonRetour.disabled = dejaRendu;
        boutonRetour.innerHTML = dejaRendu
            ? '<i class="bi bi-check-circle"></i> Deja rendu'
            : '<i class="bi bi-check-circle"></i> Valider retour';
    }

    if (boutonModifier) {
        boutonModifier.dataset.updateUrl = data.updateUrl;
        boutonModifier.dataset.affectationId = data.id;
        boutonModifier.dataset.personnelId = data.personnelId;
        boutonModifier.dataset.personnelNom = data.utilisateur;
        boutonModifier.dataset.materielType = data.materielType;
        boutonModifier.dataset.materielNumeroSerie = data.materielNumeroSerie;
        boutonModifier.dataset.materielNom = data.materielNom;
        boutonModifier.dataset.dateDebut = data.dateDebut;
    }

    if (boutonSuppression) {
        boutonSuppression.dataset.suppressionUrl = data.suppressionUrl;
        boutonSuppression.dataset.personnelNom = data.utilisateur;
    }

    if (panneauDetail) {
        panneauDetail.classList.remove('d-none');
        panneauDetail.setAttribute('aria-hidden', 'false');
    }

    if (panneauBackdrop) {
        panneauBackdrop.classList.remove('d-none');
    }

    document.body.classList.add('detail-panel-open');
}

function fermerDetailAffectation() {
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

function ouvrirModalAffectation() {
    const modalElement = document.getElementById('modalAjoutAffectation');

    if (modalElement && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    }
}

document.addEventListener('DOMContentLoaded', function () {

    // ---------- 1. Autocomplete Collaborateur ----------
    const champPersonnel = document.getElementById('champ-personnel');
    const personnelIdInput = document.getElementById('personnel-id');
    const suggestionsPersonnel = document.getElementById('suggestions-personnel');
    const boutonNouvelleAffectation = document.getElementById('btn-nouvelle-affectation');
    const boutonModifierAffectation = document.getElementById('btn-modifier-affectation');
    const formulaireAffectation = document.getElementById('form-affectation');
    const methodeFormulaireAffectation = document.getElementById('form-affectation-method');
    const titreModalAffectation = document.getElementById('modal-affectation-titre');
    const boutonSubmitAffectation = document.getElementById('btn-submit-affectation');
    const blocTypeMateriel = document.getElementById('bloc-type-materiel');
    const blocMateriel = document.getElementById('bloc-materiel');
    const blocMaterielLecture = document.getElementById('bloc-materiel-lecture');
    const materielLectureSeule = document.getElementById('materiel-lecture-seule');
    const dateDebutAffectation = document.getElementById('date-debut-affectation');
    const formulaireRetourAffectation = document.getElementById('form-valider-retour-affectation');
    const boutonValiderRetourAffectation = document.getElementById('btn-valider-retour-affectation');
    const boutonConfirmerRetour = document.getElementById('btn-confirmer-retour-affectation');
    const texteNomRetour = document.getElementById('texte-nom-retour-affectation');
    const boutonSupprimerAffectation = document.getElementById('btn-supprimer-affectation');
    const formulaireSuppressionAffectation = document.getElementById('form-supprimer-affectation');
    const texteNomSuppression = document.getElementById('texte-nom-suppression');
    const selectType = document.getElementById('materiel_type');
    const selectMateriel = document.getElementById('materiel_numero_serie');

    function modeCreationAffectation() {
        if (formulaireAffectation) {
            formulaireAffectation.reset();
            formulaireAffectation.action = formulaireAffectation.dataset.storeUrl;
        }

        if (methodeFormulaireAffectation) {
            methodeFormulaireAffectation.disabled = true;
        }

        if (titreModalAffectation) {
            titreModalAffectation.innerHTML = '<i class="bi bi-plus-circle me-2"></i>Nouvelle affectation';
        }

        if (boutonSubmitAffectation) {
            boutonSubmitAffectation.innerHTML = '<i class="bi bi-save me-1"></i> Enregistrer';
        }

        if (blocTypeMateriel) {
            blocTypeMateriel.classList.remove('d-none');
        }

        if (blocMateriel) {
            blocMateriel.classList.remove('d-none');
        }

        if (blocMaterielLecture) {
            blocMaterielLecture.classList.add('d-none');
        }

        if (selectType) {
            selectType.disabled = false;
            selectType.required = true;
        }

        if (selectMateriel) {
            selectMateriel.disabled = true;
            selectMateriel.required = true;
            selectMateriel.innerHTML = '<option value="">Choisir d\'abord un type</option>';
        }

        if (personnelIdInput) {
            personnelIdInput.value = '';
        }
    }

    function modeModificationAffectation(data) {
        if (formulaireAffectation) {
            formulaireAffectation.reset();
            formulaireAffectation.action = data.updateUrl;
        }

        if (methodeFormulaireAffectation) {
            methodeFormulaireAffectation.disabled = false;
        }

        if (titreModalAffectation) {
            titreModalAffectation.innerHTML = '<i class="bi bi-pencil me-2"></i>Modifier l\'affectation';
        }

        if (boutonSubmitAffectation) {
            boutonSubmitAffectation.innerHTML = '<i class="bi bi-save me-1"></i> Enregistrer les modifications';
        }

        if (champPersonnel) {
            champPersonnel.value = data.personnelNom || '';
        }

        if (personnelIdInput) {
            personnelIdInput.value = data.personnelId || '';
        }

        if (dateDebutAffectation) {
            dateDebutAffectation.value = data.dateDebut || '';
        }

        if (blocTypeMateriel) {
            blocTypeMateriel.classList.add('d-none');
        }

        if (blocMateriel) {
            blocMateriel.classList.add('d-none');
        }

        if (blocMaterielLecture) {
            blocMaterielLecture.classList.remove('d-none');
        }

        if (materielLectureSeule) {
            materielLectureSeule.textContent = data.materielNom || '-';
        }

        if (selectType) {
            selectType.disabled = true;
            selectType.required = false;
        }

        if (selectMateriel) {
            selectMateriel.disabled = true;
            selectMateriel.required = false;
        }

        fermerDetailAffectation();
        ouvrirModalAffectation();
    }

    if (boutonNouvelleAffectation) {
        boutonNouvelleAffectation.addEventListener('click', modeCreationAffectation);
    }

    if (boutonModifierAffectation) {
        boutonModifierAffectation.addEventListener('click', function () {
            modeModificationAffectation(boutonModifierAffectation.dataset);
        });
    }

    if (boutonValiderRetourAffectation) {
        boutonValiderRetourAffectation.addEventListener('click', function () {
            if (boutonValiderRetourAffectation.disabled) {
                return;
            }

            if (texteNomRetour && boutonModifierAffectation) {
                texteNomRetour.textContent = boutonModifierAffectation.dataset.personnelNom || 'ce collaborateur';
            }

            fermerDetailAffectation();

            const modalRetour = document.getElementById('modalConfirmRetourAffectation');

            if (modalRetour && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(modalRetour).show();
            }
        });
    }

    if (boutonConfirmerRetour && formulaireRetourAffectation) {
        boutonConfirmerRetour.addEventListener('click', function () {
            formulaireRetourAffectation.submit();
        });
    }

    if (boutonSupprimerAffectation && formulaireSuppressionAffectation) {
        boutonSupprimerAffectation.addEventListener('click', function () {
            if (!boutonSupprimerAffectation.dataset.suppressionUrl) {
                return;
            }

            formulaireSuppressionAffectation.action = boutonSupprimerAffectation.dataset.suppressionUrl;

            if (texteNomSuppression) {
                texteNomSuppression.textContent = boutonSupprimerAffectation.dataset.personnelNom || 'ce collaborateur';
            }

            fermerDetailAffectation();

            const modalSuppression = document.getElementById('modalConfirmSuppressionAffectation');

            if (modalSuppression && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(modalSuppression).show();
            }
        });
    }

    if (champPersonnel) {
        let timeoutRecherche = null;

        champPersonnel.addEventListener('input', function () {
            const texte = champPersonnel.value.trim();

            // On efface l'id cache tant que l'utilisateur retape.
            personnelIdInput.value = '';

            clearTimeout(timeoutRecherche);

            if (texte.length < 2) {
                suggestionsPersonnel.classList.add('d-none');
                suggestionsPersonnel.innerHTML = '';
                return;
            }

            timeoutRecherche = setTimeout(function () {
                const url = champPersonnel.dataset.personnelsUrl + '?q=' + encodeURIComponent(texte);

                fetch(url)
                    .then(function (reponse) { return reponse.json(); })
                    .then(function (resultats) {
                        afficherSuggestionsPersonnel(resultats);
                    })
                    .catch(function () {
                        suggestionsPersonnel.classList.add('d-none');
                    });
            }, 300);
        });

        function afficherSuggestionsPersonnel(resultats) {
            suggestionsPersonnel.innerHTML = '';

            if (!resultats || resultats.length === 0) {
                suggestionsPersonnel.classList.add('d-none');
                return;
            }

            resultats.forEach(function (personnel) {
                const nom = personnel.name || personnel.nom || 'Inconnu';
                const email = personnel.email || '';

                const item = document.createElement('div');
                item.className = 'suggestion-item';
                item.style.padding = '8px 12px';
                item.style.cursor = 'pointer';
                item.innerHTML = '<div class="fw-semibold">' + nom + '</div>' +
                                  '<div class="text-muted small">' + email + '</div>';

                item.addEventListener('click', function () {
                    champPersonnel.value = nom;
                    personnelIdInput.value = personnel.personnel_id;
                    suggestionsPersonnel.classList.add('d-none');
                    suggestionsPersonnel.innerHTML = '';
                });

                suggestionsPersonnel.appendChild(item);
            });

            suggestionsPersonnel.classList.remove('d-none');
        }

        document.addEventListener('click', function (e) {
            if (!champPersonnel.contains(e.target) && !suggestionsPersonnel.contains(e.target)) {
                suggestionsPersonnel.classList.add('d-none');
            }
        });
    }

    // ---------- 2. Select Materiel dynamique selon le Type ----------
    if (selectType) {
        selectType.addEventListener('change', function () {
            const type = selectType.value;

            selectMateriel.innerHTML = '<option value="">Chargement...</option>';
            selectMateriel.disabled = true;

            if (!type) {
                selectMateriel.innerHTML = '<option value="">Choisir d\'abord un type</option>';
                return;
            }

            fetch(selectMateriel.dataset.baseUrl + '/' + type)
                .then(function (reponse) { return reponse.json(); })
                .then(function (materiels) {
                    selectMateriel.innerHTML = '';

                    if (materiels.length === 0) {
                        selectMateriel.innerHTML = '<option value="">Aucun materiel disponible</option>';
                        selectMateriel.disabled = true;
                        return;
                    }

                    const optionVide = document.createElement('option');
                    optionVide.value = '';
                    optionVide.textContent = 'Choisir...';
                    selectMateriel.appendChild(optionVide);

                    materiels.forEach(function (materiel) {
                        const option = document.createElement('option');
                        option.value = materiel.numero_serie;
                        option.textContent = materiel.nom + ' - ' + materiel.numero_serie;
                        selectMateriel.appendChild(option);
                    });

                    selectMateriel.disabled = false;
                })
                .catch(function () {
                    selectMateriel.innerHTML = '<option value="">Erreur de chargement</option>';
                });
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            fermerDetailAffectation();
        }
    });

});
