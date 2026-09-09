// public/js/materiel.js

// ---- Popup détail ----
function ouvrirDetail(bouton) {
    var data = bouton.dataset;
    var type = data.typeMateriel;

    window.materielActuel = data; // Garde les données du matériel affiché, pour le bouton Modifier

    function remplir(id, valeur) {
        var el = document.getElementById(id);
        if (el) el.textContent = valeur || '-';
    }

    // Champs communs à tous les matériels
    remplir('detail-nom',        data.nom);
    remplir('detail-reference',  data.reference);
    remplir('detail-serie',      data.numeroSerie);
    remplir('detail-serie-info', data.numeroSerie);
    remplir('detail-date',       data.dateAchat);
    remplir('detail-marque',     data.marque);
    remplir('detail-emplacement',data.emplacement);

    // Champs spécifiques selon le type
    if (type === 'pc' || type === 'mini-pc') {
        remplir('detail-cpu',       data.cpu);
        remplir('detail-ram',       data.ram);
        remplir('detail-stockage',  data.stockage);
        remplir('detail-os',        data.os);
        remplir('detail-ecran',     data.ecran);
        remplir('detail-affecte-a', data.affecteA);
        remplir('detail-adresse-mac', data.adresseMac);

        // Historique
        var historiqueEl = document.getElementById('detail-historique');
        if (historiqueEl) {
            var historique = data.historique ? JSON.parse(data.historique) : [];
            if (historique.length === 0) {
                historiqueEl.innerHTML = '<p class="text-muted small">Aucun historique</p>';
            } else {
                var html = '';
                historique.forEach(function(h) {
                    var couleur = h.type === 'affectation' ? '#3b82f6' : '#f97316';
                    var label   = h.type === 'affectation' ? 'Affecté à ' : 'Emprunté par ';
                    html += '<div class="info-ligne">';
                    html += '<i class="bi bi-circle-fill" style="color:' + couleur + '; font-size:8px"></i>';
                    html += '<span class="info-label">' + label + h.nom + '</span>';
                    html += '<span class="info-value">' + h.date + '</span>';
                    html += '</div>';
                });
                historiqueEl.innerHTML = html;
            }
        }
    }

    if (type === 'ecran') {
        remplir('detail-taille',     data.taille);
        remplir('detail-resolution', data.resolution);
        remplir('detail-dalle',      data.dalle);
        remplir('detail-refresh',    data.refresh);
    }

    if (type === 'imprimante') {
        remplir('detail-type',      data.typeImpression);
        remplir('detail-couleur',   data.couleur);
        remplir('detail-connexion', data.connexion);
        remplir('detail-vitesse',   data.vitesse);
    }

    if (type === 'clavier' || type === 'souris' || type === 'casque') {
        remplir('detail-connexion-periph', data.connexion);
        remplir('detail-disposition',      data.disposition);
        remplir('detail-retro',            data.retro);
    }

    if (type === 'cable') {
        remplir('detail-longueur-header', data.longueur);
        remplir('detail-quantite',        data.quantite);
        remplir('detail-disponible',      data.disponible);
        remplir('detail-utilisation',     data.utilisation);
        remplir('detail-seuil',           data.seuil);
        remplir('detail-longueur-info',   data.longueur);
        remplir('detail-emplacement',     data.emplacement);
    }

    // Badge état
    var etatBadge = document.getElementById('detail-etat');
    if (etatBadge) {
        etatBadge.className = 'badge-etat ' + (data.etat || 'disponible');
        etatBadge.textContent = '● ' + (data.etatLabel || 'Disponible');
    }

    // Bouton Affecter grisé si déjà affecté ou emprunté
    var btnAffecter = document.getElementById('btn-affecter');
    if (btnAffecter) {
        if (data.etat === 'affecte' || data.etat === 'emprunte') {
            btnAffecter.disabled = true;
            btnAffecter.style.opacity = '0.5';
            btnAffecter.style.cursor = 'not-allowed';
        } else {
            btnAffecter.disabled = false;
            btnAffecter.style.opacity = '1';
            btnAffecter.style.cursor = 'pointer';
        }
    }

    // Bouton Incident : affiche "Signaler une panne" ou "Marquer comme réparé" selon l'état actuel
    var texteIncident = document.getElementById('texte-btn-incident');
    if (texteIncident) {
        var enPanneOuHorsService = (data.etat === 'en_panne' || data.etat === 'hors_service');
        texteIncident.textContent = enPanneOuHorsService ? 'Marquer comme réparé' : 'Signaler une panne';
    }

    var panneauDetail = document.getElementById('panneau-detail');
    var panneauBackdrop = document.getElementById('panneau-detail-backdrop');

    if (panneauDetail) panneauDetail.classList.remove('d-none');
    if (panneauBackdrop) panneauBackdrop.classList.remove('d-none');
    document.body.classList.add('detail-panel-open');
}

function fermerDetail() {
    var panneauDetail = document.getElementById('panneau-detail');
    var panneauBackdrop = document.getElementById('panneau-detail-backdrop');

    if (panneauDetail) panneauDetail.classList.add('d-none');
    if (panneauBackdrop) panneauBackdrop.classList.add('d-none');
    document.body.classList.remove('detail-panel-open');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') fermerDetail();
});

// ---- Champ "À qui" ----
var etat     = document.getElementById('etat');
var champ    = document.getElementById('champ-a-qui');
var idCacher = document.getElementById('a-qui-id');
var sugg     = document.getElementById('suggestions');
var aide     = document.getElementById('a-qui-aide');

function cacherSuggestions() {
    if (sugg) sugg.classList.add('d-none');
}

// Affiche un message d'erreur rouge sous le champ "À qui" (nom tapé mais pas choisi dans la liste)
function afficherErreurAQui(message) {
    if (champ) champ.classList.add('is-invalid');
    if (aide) {
        aide.textContent = message;
        aide.classList.remove('d-none', 'text-muted');
        aide.classList.add('text-danger');
    }
}

// Efface l'éventuel message d'erreur (appelé dès que l'utilisateur retape ou choisit une suggestion)
function effacerErreurAQui() {
    if (champ) champ.classList.remove('is-invalid');
    if (aide) {
        aide.classList.remove('text-danger');
        aide.classList.add('text-muted');
    }
}

function changerTypePersonne() {
    if (!etat || !champ) return;

    effacerErreurAQui();

    if (this.value === 'affecte') {
        champ.disabled = false;
        champ.required = true;
        champ.placeholder = 'Rechercher un collaborateur...';
        champ.dataset.url = champ.dataset.personnelsUrl || 'search/personnels';
        if (aide) aide.classList.add('d-none');
    } else if (this.value === 'emprunte') {
        champ.disabled = false;
        champ.required = true;
        champ.placeholder = 'Rechercher un étudiant...';
        champ.dataset.url = champ.dataset.etudiantsUrl || 'search/etudiants';
        if (aide) aide.classList.add('d-none');
    } else {
        champ.disabled = true;
        champ.required = false;
        champ.value = '';
        champ.placeholder = 'Choisir un état d\'abord';
        delete champ.dataset.url;
        if (aide) {
            aide.textContent = 'Choisissez l\'état "Affecté" ou "Emprunté" pour renseigner qui reçoit ce matériel';
            aide.classList.remove('d-none');
        }
    }

    if (idCacher) idCacher.value = '';
    cacherSuggestions();
}

if (etat) {
    etat.addEventListener('change', changerTypePersonne);
    changerTypePersonne.call(etat);
}

if (champ && sugg) {
    champ.addEventListener('input', function() {
        var recherche = this.value.trim();
        if (idCacher) idCacher.value = '';
        effacerErreurAQui();

        if (recherche.length < 1 || !this.dataset.url) {
            cacherSuggestions();
            return;
        }

        fetch(this.dataset.url + '?q=' + encodeURIComponent(recherche))
            .then(r => r.json())
            .then(data => {
                sugg.innerHTML = '';
                if (data.length === 0) {
                    sugg.innerHTML = '<div class="suggestion-item text-muted">Aucun résultat</div>';
                } else {
                    data.forEach(p => {
                        var item = document.createElement('div');
                        item.className = 'suggestion-item';
                        item.textContent = p.name;
                        item.onclick = function() {
                            champ.value = p.name;
                            if (idCacher) idCacher.value = p.id;
                            effacerErreurAQui();
                            cacherSuggestions();
                        };
                        sugg.appendChild(item);
                    });
                }
                sugg.classList.remove('d-none');
            })
            .catch(() => cacherSuggestions());
    });

    document.addEventListener('click', e => {
        if (e.target !== champ && !sugg.contains(e.target)) cacherSuggestions();
    });
}

// ---- Empêche l'envoi du formulaire si un nom a été tapé sans être choisi dans la liste ----
var formMateriel = document.getElementById('form-modal-materiel');
if (formMateriel && champ && idCacher) {
    formMateriel.addEventListener('submit', function(e) {
        var nomTape = champ.value.trim();

        if (!champ.disabled && nomTape !== '' && !idCacher.value) {
            e.preventDefault();
            afficherErreurAQui('Aucune personne trouvée avec ce nom. Veuillez choisir un nom dans la liste de suggestions.');
            champ.focus();
        }
    });
}
