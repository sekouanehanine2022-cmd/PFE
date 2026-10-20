<?php

namespace App\Services;

use App\Models\Etudiant;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UtilisateurService
{
    // Cree le compte et son profil personnel ou etudiant, avec un mot de passe a changer.
    public function creer(array $donnees): User
    {
        return DB::transaction(function () use ($donnees): User {
            $utilisateur = User::create([
                'name' => trim($donnees['name']),
                'email' => Str::lower(trim($donnees['email'])),
                'password' => $donnees['password_temporaire'],
            ]);

            $utilisateur->forceFill([
                'mot_de_passe_change' => false,
                'email_verified_at' => null,
            ])->save();

            if ($donnees['type_compte'] === 'personnel') {
                Personnel::create([
                    'user_id' => $utilisateur->id,
                    ...$this->donneesPersonnel($donnees, true),
                ]);
            } else {
                Etudiant::create([
                    'user_id' => $utilisateur->id,
                    ...$this->donneesEtudiant($donnees),
                ]);
            }

            return $utilisateur->load(['personnel', 'etudiant']);
        });
    }

    // Modifie le compte et son profil ensemble. Si la transaction echoue, les changements sont annules.
    public function modifier(User $utilisateur, array $donnees): User
    {
        return DB::transaction(function () use ($utilisateur, $donnees): User {
            $donneesCommunes = array_filter([
                'name' => isset($donnees['name']) ? trim($donnees['name']) : null,
                'email' => isset($donnees['email']) ? Str::lower(trim($donnees['email'])) : null,
            ], fn ($valeur) => $valeur !== null);

            if ($donneesCommunes !== []) {
                $utilisateur->update($donneesCommunes);
            }

            $utilisateur->loadMissing(['personnel', 'etudiant']);

            if ($utilisateur->personnel) {
                $utilisateur->personnel->update($this->donneesPersonnel($donnees));
            } elseif ($utilisateur->etudiant) {
                $utilisateur->etudiant->update($this->donneesEtudiant($donnees));
            }

            return $utilisateur->fresh(['personnel', 'etudiant']);
        });
    }

    // Supprime les acces web et mobile puis le compte. Si la transaction echoue, les changements en base sont annules.
    public function supprimer(User $utilisateur): void
    {
        DB::transaction(function () use ($utilisateur): void {
            DB::table('sessions')
                ->where('user_id', $utilisateur->id)
                ->delete();

            $utilisateur->tokens()->delete();
            $utilisateur->delete();
        });
    }

    // Prepare uniquement les champs propres au profil personnel.
    private function donneesPersonnel(array $donnees, bool $creation = false): array
    {
        $profil = [];

        foreach (['service', 'poste'] as $champ) {
            if (array_key_exists($champ, $donnees)) {
                $profil[$champ] = trim($donnees[$champ]);
            }
        }

        if (array_key_exists('telephone', $donnees)) {
            $profil['telephone'] = filled($donnees['telephone']) ? trim($donnees['telephone']) : null;
        }

        if (array_key_exists('type_contrat', $donnees)) {
            $profil['type_contrat'] = $donnees['type_contrat'];
        }

        if (array_key_exists('role', $donnees)) {
            $profil['role'] = $donnees['role'];
        } elseif ($creation) {
            $profil['role'] = 'personnel';
        }

        return $profil;
    }

    // Prepare uniquement les champs propres au profil etudiant.
    private function donneesEtudiant(array $donnees): array
    {
        $profil = [];

        if (array_key_exists('type_etudiant', $donnees) || array_key_exists('type', $donnees)) {
            $profil['type'] = $donnees['type_etudiant'] ?? $donnees['type'];
        }

        foreach (['promotion', 'etablissement'] as $champ) {
            if (array_key_exists($champ, $donnees)) {
                $profil[$champ] = trim($donnees[$champ]);
            }
        }

        if (array_key_exists('date_fin_formation', $donnees)) {
            $profil['date_fin_formation'] = $donnees['date_fin_formation'];
        }

        return $profil;
    }
}
