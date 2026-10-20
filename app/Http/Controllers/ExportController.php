<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Models\Cable;
use App\Models\Casque;
use App\Models\Clavier;
use App\Models\Ecran;
use App\Models\Emprunt;
use App\Models\Imprimante;
use App\Models\MiniPc;
use App\Models\PcPortable;
use App\Models\Souris;
use App\Models\Ticket;
use App\Services\ExportCsvService;
use App\Services\MaterielService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    // Recoit et garde les objets ou les valeurs utilises par les autres methodes.
    public function __construct(
        private readonly ExportCsvService $exportCsvService,
        private readonly MaterielService $materielService
    ) {}

    // Recupere les pcPortables correspondant aux filtres puis demande au service de creer le fichier CSV.
    public function pcPortables(Request $request): StreamedResponse
    {
        $elements = $this->requeteMateriel(PcPortable::query(), $request)->get();

        return $this->exportCsvService->telecharger('pc-portables', [
            'Numero de serie', 'Nom', 'Marque', 'Adresse MAC', 'CPU', 'RAM',
            'Stockage', 'OS', 'Ecran', 'Etat', 'Emplacement', 'Date achat',
        ], $elements->map(fn ($pc) => [
            $pc->numero_serie, $pc->materiel?->nom, $pc->materiel?->marque,
            $pc->adresse_mac, $pc->cpu, $pc->ram, $pc->stockage, $pc->os,
            $pc->ecran, $pc->materiel?->etat, $pc->materiel?->emplacement,
            $pc->materiel?->date_achat?->format('d/m/Y'),
        ]));
    }

    // Recupere les miniPcs correspondant aux filtres puis demande au service de creer le fichier CSV.
    public function miniPcs(Request $request): StreamedResponse
    {
        $elements = $this->requeteMateriel(MiniPc::query(), $request)->get();

        return $this->exportCsvService->telecharger('mini-pc', [
            'Numero de serie', 'Nom', 'Marque', 'Adresse MAC', 'CPU', 'RAM',
            'Stockage', 'OS', 'Etat', 'Emplacement', 'Date achat',
        ], $elements->map(fn ($miniPc) => [
            $miniPc->numero_serie, $miniPc->materiel?->nom, $miniPc->materiel?->marque,
            $miniPc->adresse_mac, $miniPc->cpu, $miniPc->ram, $miniPc->stockage,
            $miniPc->os, $miniPc->materiel?->etat, $miniPc->materiel?->emplacement,
            $miniPc->materiel?->date_achat?->format('d/m/Y'),
        ]));
    }

    // Recupere les ecrans correspondant aux filtres puis demande au service de creer le fichier CSV.
    public function ecrans(Request $request): StreamedResponse
    {
        $elements = $this->requeteMateriel(Ecran::query(), $request)->get();

        return $this->exportCsvService->telecharger('ecrans', [
            'Numero de serie', 'Nom', 'Marque', 'Taille', 'Resolution', 'Dalle',
            'Taux de rafraichissement', 'Etat', 'Emplacement', 'Date achat',
        ], $elements->map(fn ($ecran) => [
            $ecran->numero_serie, $ecran->materiel?->nom, $ecran->materiel?->marque,
            $ecran->taille, $ecran->resolution, $ecran->dalle,
            $ecran->taux_rafraichissement, $ecran->materiel?->etat,
            $ecran->materiel?->emplacement, $ecran->materiel?->date_achat?->format('d/m/Y'),
        ]));
    }

    // Recupere les imprimantes correspondant aux filtres puis demande au service de creer le fichier CSV.
    public function imprimantes(Request $request): StreamedResponse
    {
        $elements = $this->requeteMateriel(Imprimante::query(), $request)->get();

        return $this->exportCsvService->telecharger('imprimantes', [
            'Numero de serie', 'Nom', 'Marque', 'Type impression', 'Couleur',
            'Connexion', 'Vitesse', 'Etat', 'Emplacement', 'Date achat',
        ], $elements->map(fn ($imprimante) => [
            $imprimante->numero_serie, $imprimante->materiel?->nom,
            $imprimante->materiel?->marque, $imprimante->type_impression,
            $imprimante->couleur ? 'Oui' : 'Non', $imprimante->connexion,
            $imprimante->vitesse, $imprimante->materiel?->etat,
            $imprimante->materiel?->emplacement,
            $imprimante->materiel?->date_achat?->format('d/m/Y'),
        ]));
    }

    // Recupere les claviers correspondant aux filtres puis demande au service de creer le fichier CSV.
    public function claviers(Request $request): StreamedResponse
    {
        $elements = $this->requeteMateriel(Clavier::query(), $request)->get();

        return $this->exportCsvService->telecharger('claviers', [
            'Numero de serie', 'Nom', 'Marque', 'Connexion', 'Disposition',
            'Retro-eclairage', 'Etat', 'Emplacement', 'Date achat',
        ], $elements->map(fn ($clavier) => [
            $clavier->numero_serie, $clavier->materiel?->nom, $clavier->materiel?->marque,
            $clavier->connexion, $clavier->disposition,
            $clavier->retro_eclairage ? 'Oui' : 'Non', $clavier->materiel?->etat,
            $clavier->materiel?->emplacement, $clavier->materiel?->date_achat?->format('d/m/Y'),
        ]));
    }

    // Recupere les souris correspondant aux filtres puis demande au service de creer le fichier CSV.
    public function souris(Request $request): StreamedResponse
    {
        $elements = $this->requeteMateriel(Souris::query(), $request)->get();

        return $this->exportCsvService->telecharger('souris', [
            'Numero de serie', 'Nom', 'Marque', 'Connexion', 'DPI',
            'Nombre de boutons', 'Etat', 'Emplacement', 'Date achat',
        ], $elements->map(fn ($souris) => [
            $souris->numero_serie, $souris->materiel?->nom, $souris->materiel?->marque,
            $souris->connexion, $souris->dpi, $souris->nombre_boutons,
            $souris->materiel?->etat, $souris->materiel?->emplacement,
            $souris->materiel?->date_achat?->format('d/m/Y'),
        ]));
    }

    // Recupere les casques correspondant aux filtres puis demande au service de creer le fichier CSV.
    public function casques(Request $request): StreamedResponse
    {
        $elements = $this->requeteMateriel(Casque::query(), $request)->get();

        return $this->exportCsvService->telecharger('casques', [
            'Numero de serie', 'Nom', 'Marque', 'Connexion', 'Micro',
            'Reduction de bruit', 'Etat', 'Emplacement', 'Date achat',
        ], $elements->map(fn ($casque) => [
            $casque->numero_serie, $casque->materiel?->nom, $casque->materiel?->marque,
            $casque->connexion, $casque->micro ? 'Oui' : 'Non',
            $casque->reduction_bruit ? 'Oui' : 'Non', $casque->materiel?->etat,
            $casque->materiel?->emplacement, $casque->materiel?->date_achat?->format('d/m/Y'),
        ]));
    }

    // Recupere les cables correspondant aux filtres puis demande au service de creer le fichier CSV.
    public function cables(Request $request): StreamedResponse
    {
        $elements = Cable::query()
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $recherche = $request->string('search')->trim()->toString();
                $query->where(function ($q) use ($recherche) {
                    $q->where('type_cable', 'like', '%'.$recherche.'%')
                        ->orWhere('reference', 'like', '%'.$recherche.'%');
                });
            })
            ->get()
            ->filter(fn ($cable) => match ($request->get('statut')) {
                'stock_ok' => $cable->quantite_disponible > $cable->seuil_alerte,
                'stock_bas' => $cable->quantite_disponible > 0
                    && $cable->quantite_disponible <= $cable->seuil_alerte,
                'rupture' => $cable->quantite_disponible === 0,
                default => true,
            });

        return $this->exportCsvService->telecharger('cables', [
            'Reference', 'Type', 'Longueur', 'Quantite totale', 'Quantite disponible',
            'Seuil alerte', 'Couleur', 'Emplacement',
        ], $elements->map(fn ($cable) => [
            $cable->reference, $cable->type_cable, $cable->longueur, $cable->quantite,
            $cable->quantite_disponible, $cable->seuil_alerte, $cable->couleur,
            $cable->emplacement,
        ]));
    }

    // Recupere les affectations correspondant aux filtres puis demande au service de creer le fichier CSV.
    public function affectations(Request $request): StreamedResponse
    {
        $aujourdhui = now()->toDateString();
        $dansSeptJours = now()->addDays(7)->toDateString();
        $elements = Affectation::with(array_merge(
            ['personnel.user'],
            $this->materielService->relationsAffectation()
        ))
            ->when($request->filled('search'), fn ($query) => $query->whereHas(
                'personnel.user',
                fn ($q) => $q->where('name', 'like', '%'.$request->get('search').'%')
            ))
            ->when($request->filled('statut'), function ($query) use ($request, $aujourdhui, $dansSeptJours) {
                match ($request->get('statut')) {
                    'active' => $query->where('statut', 'active'),
                    'en_cours' => $query->where('statut', 'active')->where(fn ($q) => $q
                        ->whereNull('date_fin')->orWhereDate('date_fin', '>', $dansSeptJours)),
                    'echeance_proche' => $query->where('statut', 'active')
                        ->whereBetween('date_fin', [$aujourdhui, $dansSeptJours]),
                    'en_retard' => $query->where('statut', 'active')->whereDate('date_fin', '<', $aujourdhui),
                    'rendu' => $query->where('statut', 'rendu'),
                    default => null,
                };
            })
            ->latest()
            ->get();

        return $this->exportCsvService->telecharger('affectations', [
            'Personnel', 'Email', 'Type de contrat', 'Materiel', 'Type', 'Date debut',
            'Date fin', 'Date retour', 'Statut',
        ], $elements->map(function ($affectation) {
            $specifique = $this->materielService->specifique($affectation->materiel);

            return [
                $affectation->personnel?->user?->name,
                $affectation->personnel?->user?->email,
                $affectation->personnel?->type_contrat,
                $affectation->materiel?->nom.' ('.($specifique?->numero_serie ?? '-').')',
                $this->materielService->libelle($affectation->materiel?->type_materiel),
                $affectation->date_debut?->format('d/m/Y'),
                $affectation->date_fin?->format('d/m/Y'),
                $affectation->date_retour?->format('d/m/Y'),
                $affectation->statut,
            ];
        }));
    }

    // Recupere les emprunts correspondant aux filtres puis demande au service de creer le fichier CSV.
    public function emprunts(Request $request): StreamedResponse
    {
        $aujourdhui = now()->startOfDay();
        $dansSeptJours = now()->copy()->addDays(7)->endOfDay();
        $elements = Emprunt::with(['etudiant.user', 'pcPortable.materiel'])
            ->when($request->filled('search'), fn ($query) => $query->whereHas(
                'etudiant.user',
                fn ($q) => $q->where('name', 'like', '%'.$request->get('search').'%')
            ))
            ->when($request->filled('statut'), function ($query) use ($request, $aujourdhui, $dansSeptJours) {
                match ($request->get('statut')) {
                    'echeance_proche' => $query->where('statut', '!=', 'rendu')
                        ->whereBetween('date_fin_prevue', [$aujourdhui, $dansSeptJours]),
                    'en_retard' => $query->where('statut', '!=', 'rendu')
                        ->whereDate('date_fin_prevue', '<', $aujourdhui),
                    'en_cours' => $query->where('statut', '!=', 'rendu')
                        ->whereDate('date_fin_prevue', '>', $dansSeptJours),
                    'rendu' => $query->where('statut', 'rendu'),
                    default => null,
                };
            })
            ->latest()
            ->get();

        return $this->exportCsvService->telecharger('emprunts', [
            'Etudiant', 'Email', 'Type etudiant', 'PC portable', 'Numero de serie',
            'Date debut', 'Date fin prevue', 'Date retour', 'Statut',
        ], $elements->map(fn ($emprunt) => [
            $emprunt->etudiant?->user?->name, $emprunt->etudiant?->user?->email,
            $emprunt->etudiant?->type, $emprunt->pcPortable?->materiel?->nom,
            $emprunt->pcPortable?->numero_serie, $emprunt->date_debut?->format('d/m/Y'),
            $emprunt->date_fin_prevue?->format('d/m/Y'), $emprunt->date_retour?->format('d/m/Y'),
            $emprunt->statut,
        ]));
    }

    // Recupere les tickets correspondant aux filtres puis demande au service de creer le fichier CSV.
    public function tickets(Request $request): StreamedResponse
    {
        $elements = Ticket::with(['demandeur', 'technicien', 'incident', 'demande', 'materiel'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $recherche = $request->get('search');
                $query->where(fn ($q) => $q->where('titre', 'like', '%'.$recherche.'%')
                    ->orWhereHas('demandeur', fn ($u) => $u->where('name', 'like', '%'.$recherche.'%')));
            })
            ->when($request->filled('statut'), fn ($query) => $query->where('statut', $request->get('statut')))
            ->latest()
            ->get();

        return $this->exportCsvService->telecharger('tickets', [
            'Titre', 'Type', 'Demandeur', 'Email', 'Assigne a', 'Materiel',
            'Priorite', 'Statut', 'Cree le', 'Resolu le',
        ], $elements->map(fn ($ticket) => [
            $ticket->titre,
            $ticket->demande?->type_demande ?? 'incident',
            $ticket->demandeur?->name,
            $ticket->demandeur?->email,
            $ticket->technicien?->name,
            $ticket->materiel?->nom,
            $ticket->priorite,
            $ticket->statut,
            $ticket->created_at?->format('d/m/Y H:i'),
            $ticket->date_resolution?->format('d/m/Y H:i'),
        ]));
    }

    // Applique la recherche et l etat choisi aux materiels avant leur export.
    private function requeteMateriel(Builder $query, Request $request): Builder
    {
        return $query->with('materiel')
            ->when($request->filled('search'), function ($query) use ($request) {
                $recherche = $request->get('search');
                $query->where(function ($q) use ($recherche) {
                    $q->where('numero_serie', 'like', '%'.$recherche.'%')
                        ->orWhereHas('materiel', fn ($materiel) => $materiel
                            ->where('nom', 'like', '%'.$recherche.'%')
                            ->orWhere('marque', 'like', '%'.$recherche.'%'));
                });
            })
            ->when($request->filled('etat'), fn ($query) => $query->whereHas(
                'materiel',
                fn ($materiel) => $materiel->where('etat', $request->get('etat'))
            ));
    }
}
