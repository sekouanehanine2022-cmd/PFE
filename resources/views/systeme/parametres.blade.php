{{-- resources/views/systeme/parametres.blade.php --}}

@extends('layouts.app')

@section('title', 'Paramètres')

@section('styles')
    <link href="{{ asset('css/materiel.css') }}" rel="stylesheet">
    <link href="{{ asset('css/parametres.css') }}" rel="stylesheet">
@endsection

@section('content')

    {{-- Fil d'ariane + Titre --}}
    <div class="mb-4">
        <small class="text-muted">Dashboard > Système > Paramètres</small>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <h2 class="fw-bold mb-0">⚙️ Paramètres</h2>
            <button class="btn btn-primary">
                <i class="bi bi-save"></i> Enregistrer les modifications
            </button>
        </div>
    </div>

    <div class="row g-4">

        {{-- Colonne gauche --}}
        <div class="col-12 col-lg-8">

            {{-- Informations générales --}}
            <div class="param-card mb-4">
                <div class="param-card-titre">
                    <i class="bi bi-building"></i> Informations générales
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-12 col-md-6">
                        <label class="param-label">Nom de l'établissement</label>
                        <input type="text" class="form-control" value="EFEL">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="param-label">Type d'établissement</label>
                        <input type="text" class="form-control" value="Centre de formation">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="param-label">Adresse</label>
                        <input type="text" class="form-control" value="12 rue des Formations">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="param-label">Ville</label>
                        <input type="text" class="form-control" value="Paris">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="param-label">Email de contact</label>
                        <input type="email" class="form-control" value="contact@efel.fr">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="param-label">Téléphone</label>
                        <input type="text" class="form-control" value="01 23 45 67 89">
                    </div>
                </div>
            </div>

            {{-- Gestion des utilisateurs --}}
            <div class="param-card mb-4">
                <div class="param-card-titre">
                    <i class="bi bi-people"></i> Gestion des utilisateurs
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-12 col-md-6">
                        <label class="param-label">Rôle par défaut</label>
                        <select class="form-select">
                            <option>Technicien</option>
                            <option>Administrateur</option>
                            <option>Lecteur</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="param-label">Durée de session (minutes)</label>
                        <input type="number" class="form-control" value="60">
                    </div>
                    <div class="col-12">
                        <div class="param-toggle-item">
                            <div>
                                <div class="fw-semibold" style="font-size:13px">Authentification à deux facteurs</div>
                                <div class="text-muted" style="font-size:12px">Sécuriser les connexions avec un code SMS</div>
                            </div>
                            <div class="toggle"></div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="param-toggle-item">
                            <div>
                                <div class="fw-semibold" style="font-size:13px">Inscription libre</div>
                                <div class="text-muted" style="font-size:12px">Permettre aux utilisateurs de créer un compte</div>
                            </div>
                            <div class="toggle"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Paramètres des emprunts --}}
            <div class="param-card mb-4">
                <div class="param-card-titre">
                    <i class="bi bi-arrow-left-right"></i> Paramètres des emprunts
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-12 col-md-6">
                        <label class="param-label">Durée maximale d'emprunt (jours)</label>
                        <input type="number" class="form-control" value="90">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="param-label">Délai de rappel avant échéance (jours)</label>
                        <input type="number" class="form-control" value="7">
                    </div>
                    <div class="col-12">
                        <div class="param-toggle-item">
                            <div>
                                <div class="fw-semibold" style="font-size:13px">Envoi automatique de rappels</div>
                                <div class="text-muted" style="font-size:12px">Envoyer un email avant la date de retour</div>
                            </div>
                            <div class="toggle actif"></div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="param-toggle-item">
                            <div>
                                <div class="fw-semibold" style="font-size:13px">Validation obligatoire du retour</div>
                                <div class="text-muted" style="font-size:12px">Un technicien doit valider chaque retour</div>
                            </div>
                            <div class="toggle actif"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Paramètres des tickets --}}
            <div class="param-card mb-4">
                <div class="param-card-titre">
                    <i class="bi bi-ticket"></i> Paramètres des tickets
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-12 col-md-6">
                        <label class="param-label">Priorité par défaut</label>
                        <select class="form-select">
                            <option>Normale</option>
                            <option>Haute</option>
                            <option>Basse</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="param-label">Délai de résolution (heures)</label>
                        <input type="number" class="form-control" value="48">
                    </div>
                    <div class="col-12">
                        <div class="param-toggle-item">
                            <div>
                                <div class="fw-semibold" style="font-size:13px">Notification à la création</div>
                                <div class="text-muted" style="font-size:12px">Notifier les techniciens à chaque nouveau ticket</div>
                            </div>
                            <div class="toggle actif"></div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="param-toggle-item">
                            <div>
                                <div class="fw-semibold" style="font-size:13px">Assignation automatique</div>
                                <div class="text-muted" style="font-size:12px">Assigner automatiquement selon la charge de travail</div>
                            </div>
                            <div class="toggle"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Colonne droite --}}
        <div class="col-12 col-lg-4">

            {{-- Apparence --}}
            <div class="param-card mb-4">
                <div class="param-card-titre">
                    <i class="bi bi-palette"></i> Apparence
                </div>
                <div class="mt-3">
                    <label class="param-label">Thème</label>
                    <div class="d-flex gap-2 mt-2">
                        <div class="theme-option actif">
                            <div class="theme-preview clair"></div>
                            <small>Clair</small>
                        </div>
                        <div class="theme-option">
                            <div class="theme-preview sombre"></div>
                            <small>Sombre</small>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="param-label">Couleur principale</label>
                    <div class="d-flex gap-2 mt-2 flex-wrap">
                        <div class="couleur-option actif" style="background:#3b82f6"></div>
                        <div class="couleur-option" style="background:#22c55e"></div>
                        <div class="couleur-option" style="background:#f97316"></div>
                        <div class="couleur-option" style="background:#8b5cf6"></div>
                        <div class="couleur-option" style="background:#ef4444"></div>
                    </div>
                </div>
            </div>

            {{-- Sauvegarde --}}
            <div class="param-card mb-4">
                <div class="param-card-titre">
                    <i class="bi bi-database"></i> Sauvegarde
                </div>
                <div class="mt-3">
                    <div class="param-toggle-item mb-3">
                        <div>
                            <div class="fw-semibold" style="font-size:13px">Sauvegarde automatique</div>
                            <div class="text-muted" style="font-size:12px">Chaque jour à minuit</div>
                        </div>
                        <div class="toggle actif"></div>
                    </div>
                    <label class="param-label">Dernière sauvegarde</label>
                    <div class="sauvegarde-info mt-1">
                        <i class="bi bi-check-circle text-success"></i>
                        <span>18/06/2024 à 00h00</span>
                    </div>
                    <button class="btn btn-outline-secondary btn-sm w-100 mt-3">
                        <i class="bi bi-download"></i> Télécharger la sauvegarde
                    </button>
                </div>
            </div>

            {{-- Informations système --}}
            <div class="param-card">
                <div class="param-card-titre">
                    <i class="bi bi-info-circle"></i> Informations système
                </div>
                <div class="mt-3">
                    <div class="info-ligne">
                        <i class="bi bi-code-slash"></i>
                        <span class="info-label">Version</span>
                        <span class="info-value">1.0.0</span>
                    </div>
                    <div class="info-ligne">
                        <i class="bi bi-box"></i>
                        <span class="info-label">Laravel</span>
                        <span class="info-value">12.x</span>
                    </div>
                    <div class="info-ligne">
                        <i class="bi bi-filetype-php"></i>
                        <span class="info-label">PHP</span>
                        <span class="info-value">8.2</span>
                    </div>
                    <div class="info-ligne">
                        <i class="bi bi-database"></i>
                        <span class="info-label">Base de données</span>
                        <span class="info-value">MySQL 8.0</span>
                    </div>
                </div>
            </div>

        </div>

    </div>

@endsection

@section('scripts')
    <script src="{{ asset('js/materiel.js') }}"></script>
@endsection