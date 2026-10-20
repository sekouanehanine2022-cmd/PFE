<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\Casque;
use App\Models\Clavier;
use App\Models\Ecran;
use App\Models\MiniPc;
use App\Models\Materiel;
use App\Models\PcPortable;
use App\Models\Personnel;
use App\Models\Souris;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AffectationService
{
    // Refuse la suppression tant que le materiel est affecte ou emprunte et non rendu.
    public function verifierSuppressionAutorisee(Materiel $materiel, string $libelleType): void
    {
        if ($materiel->affectations()->where('statut', 'active')->exists()) {
            throw ValidationException::withMessages([
                'suppression' => __('messages.materiel_affecte_suppression_interdite', ['type' => $libelleType]),
            ]);
        }

        if ($this->pcPortableEmprunte($materiel)) {
            throw ValidationException::withMessages([
                'suppression' => __('messages.materiel_emprunte_suppression_interdite', ['type' => $libelleType]),
            ]);
        }
    }

    // Empeche de declarer en panne un materiel affecte ou emprunte qui n'est pas rendu.
    public function verifierMiseEnPanneAutorisee(Materiel $materiel, string $libelleType): void
    {
        if ($materiel->affectations()->where('statut', 'active')->exists()) {
            throw ValidationException::withMessages([
                'etat' => __('messages.materiel_affecte_panne_interdite', ['type' => $libelleType]),
            ]);
        }

        if ($this->pcPortableEmprunte($materiel)) {
            throw ValidationException::withMessages([
                'etat' => __('messages.materiel_emprunte_panne_interdite', ['type' => $libelleType]),
            ]);
        }
    }

    // Exige une date de fin pour un CDD ou alternant, et permet une affectation CDI sans fin.
    public function validerDateFinCreationMateriel(Request $request, Personnel $personnel): ?string
    {
        $request->validate([
            'date_fin' => [
                Rule::requiredIf($personnel->type_contrat !== 'cdi'),
                'nullable',
                'date',
                'after_or_equal:today',
            ],
        ]);

        return $personnel->type_contrat === 'cdi' ? null : $request->input('date_fin');
    }

    // Verifie si ce PC est encore emprunte.
    private function pcPortableEmprunte(Materiel $materiel): bool
    {
        $pcPortable = $materiel->pcPortable()->first();

        return $pcPortable
            ? $pcPortable->emprunts()->where('statut', '!=', 'rendu')->exists()
            : false;
    }

    // Verifie si cette personne a deja un materiel du meme type non rendu.
    public function existeAffectationActivePourType(
        int $personnelId,
        string $materielType,
        ?string $sousType = null,
        ?int $affectationIgnoreeId = null
    ): bool {
        $typeMateriel = $this->typeMaterielCentral($materielType);

        if (! $typeMateriel) {
            return false;
        }

        return Affectation::query()
            ->where('personnel_id', $personnelId)
            ->where('statut', 'active')
            ->whereHas('materiel', function ($query) use ($typeMateriel) {
                $query->where('type_materiel', $typeMateriel);
            })
            ->when($affectationIgnoreeId, function ($query) use ($affectationIgnoreeId) {
                $query->where('id', '!=', $affectationIgnoreeId);
            })
            ->exists();
    }

    // Choisit le modele correspondant au type du formulaire, par exemple PcPortable pour pc-portable.
    public function typeDepuisSlug(string $slug): ?string
    {
        return [
            'pc-portable' => PcPortable::class,
            'mini-pc' => MiniPc::class,
            'ecran' => Ecran::class,
            'clavier' => Clavier::class,
            'souris' => Souris::class,
            'casque' => Casque::class,
        ][$slug] ?? null;
    }

    // Garde cette ancienne methode pour les appels existants. Elle renvoie null car le sous-type n'est plus utilise.
    public function sousTypeDepuisSlug(string $slug): ?string
    {
        return null;
    }

    // Transforme le nom utilise dans le code en texte a afficher.
    public function libelleType(string $materielType, ?string $sousType = null): string
    {
        return [
            PcPortable::class => 'PC portable',
            MiniPc::class => 'Mini PC',
            Ecran::class => 'ecran',
            Clavier::class => 'clavier',
            Souris::class => 'souris',
            Casque::class => 'casque',
            'pc_portable' => 'PC portable',
            'mini_pc' => 'Mini PC',
            'ecran' => 'ecran',
            'clavier' => 'clavier',
            'souris' => 'souris',
            'casque' => 'casque',
        ][$materielType] ?? 'materiel';
    }

    // Retrouve la valeur type_materiel correspondant au modele choisi.
    private function typeMaterielCentral(string $materielType): ?string
    {
        return [
            PcPortable::class => 'pc_portable',
            MiniPc::class => 'mini_pc',
            Ecran::class => 'ecran',
            Clavier::class => 'clavier',
            Souris::class => 'souris',
            Casque::class => 'casque',
            'pc-portable' => 'pc_portable',
            'mini-pc' => 'mini_pc',
            'ecran' => 'ecran',
            'clavier' => 'clavier',
            'souris' => 'souris',
            'casque' => 'casque',
            'pc_portable' => 'pc_portable',
            'mini_pc' => 'mini_pc',
        ][$materielType] ?? null;
    }
}
