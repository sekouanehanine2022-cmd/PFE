// public/js/materiel.js

// ---- Popup détail ----
function ouvrirDetail(bouton) {
    var data = bouton.dataset;
    var type = data.typeMateriel;

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

    document.getElementById('panneau-detail').classList.remove('d-none');
    document.getElementById('panneau-detail-backdrop').classList.remove('d-none');
    document.body.classList.add('detail-panel-open');
}

function fermerDetail() {
    document.getElementById('panneau-detail').classList.add('d-none');
    document.getElementById('panneau-detail-backdrop').classList.add('d-none');
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

function changerTypePersonne() {
    if (!etat || !champ) return;

    if (this.value === 'affecte') {
        champ.disabled = false;
        champ.placeholder = 'Rechercher un collaborateur...';
        champ.dataset.url = champ.dataset.personnelsUrl || 'search/personnels';
        if (aide) aide.classList.add('d-none');
    } else if (this.value === 'emprunte') {
        champ.disabled = false;
        champ.placeholder = 'Rechercher un étudiant...';
        champ.dataset.url = champ.dataset.etudiantsUrl || 'search/etudiants';
        if (aide) aide.classList.add('d-none');
    } else {
        champ.disabled = true;
        champ.value = '';
        champ.placeholder = 'Choisir un état d\'abord';
        delete champ.dataset.url;
        if (aide) {
            aide.textContent = 'Sélectionnez d\'abord un état';
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