<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MobileTwoFactorChallengeService
{
    public const DUREE_SECONDES = 300;

    // Cree un defi temporaire apres le mot de passe, sans fournir encore de jeton d acces.
    public function creer(User $utilisateur, string $nomAppareil): string
    {
        $jeton = Str::random(64);

        Cache::put($this->cle($jeton), [
            'user_id' => $utilisateur->getKey(),
            'device_name' => trim($nomAppareil),
        ], now()->addSeconds(self::DUREE_SECONDES));

        return $jeton;
    }

    // Retrouve le defi 2FA mobile encore valide a partir de son jeton.
    public function recuperer(string $jeton): ?array
    {
        $defi = Cache::get($this->cle($jeton));

        return is_array($defi) ? $defi : null;
    }

    // Supprime le defi mobile pour empecher sa reutilisation.
    public function invalider(string $jeton): void
    {
        Cache::forget($this->cle($jeton));
    }

    // Construit la cle de cache du defi sans y exposer le jeton en clair.
    private function cle(string $jeton): string
    {
        return 'mobile-2fa:'.hash('sha256', $jeton);
    }
}
