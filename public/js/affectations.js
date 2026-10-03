// public/js/affectations.js

function remplirDetailAffectation(id, valeur) {
    const element = document.getElementById(id);

    if (element) {
        element.textContent = valeur || '-';
    }
}

function ouvrirDetailAffectation(bouton) {
    const data = bouton.dataset;
    window.affectationActuelle = data;
    const panneauDetail = document.getElementById('panneau-detail');
    const panneauBackdrop = document.getElementById('panneau-detail-backdrop');
    const formulaireRetour = document.getElementById('form-valider-retour-affectation');
    const boutonRetour = document.getElementById('btn-valider-retour-affectation');
    const boutonSuppression = document.getElementById('btn-supprimer-affectation');
    const boutonRelancer = document.getElementById('btn-relancer-affectation');
    const boutonProlonger = document.getElementById('btn-prolonger-affectation');
    const echeanceBadge = document.getElementById('detail-affectation-echeance');
    const avatar = document.getElementById('detail-affectation-avatar');
    const alerte = document.getElementById('detail-affectation-alerte');
    const stepAttente = document.getElementById('detail-affectation-step-attente');
    const stepRendu = document.getElementById('detail-affectation-step-rendu');
    const ligneRendu = document.getElementById('detail-affectation-ligne-rendu');

    remplirDetailAffectation('detail-affectation-utilisateur', data.utilisateur);
    remplirDetailAffectation('detail-affectation-poste', data.poste);
    remplirDetailAffectation('detail-affectation-service', data.service);
    remplirDetailAffectation('detail-affectation-type', data.materielTypeLabel);
    remplirDetailAffectation('detail-affectation-materiel', data.materielNom);
    remplirDetailAffectation('detail-affectation-serie', data.numeroSerie);
    remplirDetailAffectation('detail-affectation-date-debut', data.dateDebutLabel);
    remplirDetailAffectation('detail-affectation-date-fin', data.dateFinLabel);
    remplirDetailAffectation('detail-affectation-date-retour', data.dateRetourLabel);
    remplirDetailAffectation('detail-affectation-alerte-titre', data.alerteTitre);
    remplirDetailAffectation('detail-affectation-alerte-texte', data.alerteTexte);
    document.getElementById('detail-affectation-bloc-retour')?.classList.toggle('d-none', data.statut !== 'rendu');

    if (echeanceBadge) {
        const classes = ['retard', 'proche', 'aujourd-hui', 'normal'];
        echeanceBadge.className = 'echeance ' + (classes.includes(data.echeanceClass) ? data.echeanceClass : 'normal');
        echeanceBadge.textContent = data.echeanceLabel || '-';
    }

    if (avatar) {
        avatar.textContent = data.initiales || '-';
        avatar.style.backgroundColor = data.avatarColor || '#3b82f6';
    }

    if (alerte) {
        alerte.classList.toggle('alerte-rendu', data.statut === 'rendu');
        alerte.classList.toggle('alerte-retard', data.echeanceClass === 'retard');
    }

    const rendu = data.statut === 'rendu';
    if (stepAttente) {
        stepAttente.classList.toggle('attente', !rendu);
        stepAttente.classList.toggle('done', rendu);
        stepAttente.innerHTML = rendu ? '<i class="bi bi-check"></i>' : '<i class="bi bi-clock"></i>';
    }
    if (stepRendu) {
        stepRendu.classList.toggle('rendu', !rendu);
        stepRendu.classList.toggle('done', rendu);
    }
    if (ligneRendu) {
        ligneRendu.classList.toggle('done', rendu);
    }

    if (formulaireRetour) {
        formulaireRetour.action = data.retourUrl;
    }

    if (boutonRetour) {
        const dejaRendu = data.statut === 'rendu';
        boutonRetour.dataset.personnelNom = data.utilisateur;
        boutonRetour.disabled = dejaRendu;
        boutonRetour.innerHTML = dejaRendu
            ? '<i class="bi bi-check-circle"></i> Deja rendu'
            : '<i class="bi bi-check-circle"></i> Valider retour';
    }

    const actionDateeDisponible = data.statut !== 'rendu' && Boolean(data.dateFin);
    if (boutonRelancer) {
        boutonRelancer.disabled = !actionDateeDisponible;
        boutonRelancer.title = actionDateeDisponible ? '' : 'Disponible uniquement pour une affectation active avec une date de fin';
    }
    if (boutonProlonger) {
        boutonProlonger.disabled = !actionDateeDisponible;
        boutonProlonger.title = actionDateeDisponible ? '' : 'Disponible uniquement pour une affectation active avec une date de fin';
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

function dateAffectationPlusUnJour(dateIso) {
    if (!dateIso) return '';
    const date = new Date(dateIso + 'T00:00:00');
    if (Number.isNaN(date.getTime())) return '';
    date.setDate(date.getDate() + 1);
    return date.toISOString().slice(0, 10);
}

document.addEventListener('DOMContentLoaded', function () {

    const affectationId = new URLSearchParams(window.location.search).get('affectation');
    if (affectationId) {
        const boutonAffectation = document.querySelector(
            `button[data-affectation-id="${CSS.escape(affectationId)}"][onclick="ouvrirDetailAffectation(this)"]`
        );

        if (boutonAffectation) {
            ouvrirDetailAffectation(boutonAffectation);
        }
    }

    // ---------- 1. Autocomplete Collaborateur ----------
    const champPersonnel = document.getElementById('champ-personnel');
    const personnelIdInput = document.getElementById('personnel-id');
    const suggestionsPersonnel = document.getElementById('suggestions-personnel');
    const boutonNouvelleAffectation = document.getElementById('btn-nouvelle-affectation');
    const formulaireAffectation = document.getElementById('form-affectation');
    const titreModalAffectation = document.getElementById('modal-affectation-titre');
    const boutonSubmitAffectation = document.getElementById('btn-submit-affectation');
    const blocTypeMateriel = document.getElementById('bloc-type-materiel');
    const blocMateriel = document.getElementById('bloc-materiel');
    const dateDebutAffectation = document.getElementById('date-debut-affectation');
    const dateFinAffectation = document.getElementById('date-fin-affectation');
    const blocDateFinAffectation = document.getElementById('bloc-date-fin-affectation');
    const typeContratAffectation = document.getElementById('type-contrat-affectation');
    const formulaireRetourAffectation = document.getElementById('form-valider-retour-affectation');
    const boutonValiderRetourAffectation = document.getElementById('btn-valider-retour-affectation');
    const boutonConfirmerRetour = document.getElementById('btn-confirmer-retour-affectation');
    const texteNomRetour = document.getElementById('texte-nom-retour-affectation');
    const boutonSupprimerAffectation = document.getElementById('btn-supprimer-affectation');
    const formulaireSuppressionAffectation = document.getElementById('form-supprimer-affectation');
    const texteNomSuppression = document.getElementById('texte-nom-suppression');
    const boutonRelancerAffectation = document.getElementById('btn-relancer-affectation');
    const formulaireRelanceAffectation = document.getElementById('form-relancer-affectation');
    const boutonProlongerAffectation = document.getElementById('btn-prolonger-affectation');
    const formulaireProlongationAffectation = document.getElementById('form-prolonger-affectation');
    const selectType = document.getElementById('materiel_type');
    const selectMateriel = document.getElementById('materiel_numero_serie');

    if (dateDebutAffectation && dateFinAffectation) {
        dateDebutAffectation.addEventListener('change', function () {
            dateFinAffectation.min = dateDebutAffectation.value;
        });
    }

    function afficherDateFinSelonContrat(typeContrat) {
        const avecDateFin = typeContrat === 'cdd' || typeContrat === 'alternant_interne';
        const libelles = { cdi: 'CDI', cdd: 'CDD', alternant_interne: 'Alternant interne' };

        if (typeContratAffectation) {
            typeContratAffectation.textContent = libelles[typeContrat] || '';
            typeContratAffectation.classList.toggle('d-none', !libelles[typeContrat]);
        }
        if (blocDateFinAffectation && dateFinAffectation) {
            blocDateFinAffectation.classList.toggle('d-none', !avecDateFin);
            dateFinAffectation.disabled = !avecDateFin;
            dateFinAffectation.required = avecDateFin;
            if (!avecDateFin) dateFinAffectation.value = '';
        }
    }

    function modeCreationAffectation() {
        if (formulaireAffectation) {
            formulaireAffectation.reset();
            formulaireAffectation.action = formulaireAffectation.dataset.storeUrl;
        }
        if (dateFinAffectation) {
            dateFinAffectation.min = '';
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
        afficherDateFinSelonContrat('');
    }

    if (boutonNouvelleAffectation) {
        boutonNouvelleAffectation.addEventListener('click', modeCreationAffectation);
    }

    if (boutonValiderRetourAffectation) {
        boutonValiderRetourAffectation.addEventListener('click', function () {
            if (boutonValiderRetourAffectation.disabled) {
                return;
            }

            if (texteNomRetour) {
                texteNomRetour.textContent = boutonValiderRetourAffectation.dataset.personnelNom || 'ce collaborateur';
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

    if (boutonRelancerAffectation && formulaireRelanceAffectation) {
        boutonRelancerAffectation.addEventListener('click', function () {
            const data = window.affectationActuelle || {};
            if (!data.relanceUrl || data.statut === 'rendu' || !data.dateFin) return;

            formulaireRelanceAffectation.action = data.relanceUrl;
            remplirDetailAffectation('relance-affectation-nom', data.utilisateur);
            remplirDetailAffectation('relance-affectation-materiel', data.materielNom);
            fermerDetailAffectation();

            const modal = document.getElementById('modalConfirmationRelanceAffectation');
            if (modal && window.bootstrap) bootstrap.Modal.getOrCreateInstance(modal).show();
        });
    }

    if (boutonProlongerAffectation && formulaireProlongationAffectation) {
        boutonProlongerAffectation.addEventListener('click', function () {
            const data = window.affectationActuelle || {};
            if (!data.prolongationUrl || data.statut === 'rendu' || !data.dateFin) return;

            const nouvelleDate = document.getElementById('prolongation-affectation-date-fin');
            const dateMin = dateAffectationPlusUnJour(data.dateFin);
            formulaireProlongationAffectation.action = data.prolongationUrl;

            remplirDetailAffectation('prolongation-affectation-materiel', data.materielNom);
            remplirDetailAffectation('prolongation-affectation-date-actuelle', data.dateFinLabel);
            if (nouvelleDate) {
                nouvelleDate.value = dateMin;
                nouvelleDate.min = dateMin;
            }

            fermerDetailAffectation();
            const modal = document.getElementById('modalProlongationAffectation');
            if (modal && window.bootstrap) bootstrap.Modal.getOrCreateInstance(modal).show();
        });
    }

    if (champPersonnel) {
        let timeoutRecherche = null;

        champPersonnel.addEventListener('input', function () {
            const texte = champPersonnel.value.trim();

            // On efface l'id cache tant que l'utilisateur retape.
            personnelIdInput.value = '';
            afficherDateFinSelonContrat('');

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
                    afficherDateFinSelonContrat(personnel.type_contrat);
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
