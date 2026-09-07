// public/js/emprunts-ajout.js

document.addEventListener('DOMContentLoaded', function () {

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
