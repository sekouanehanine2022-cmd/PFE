<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MobileTwoFactorChallengeService
{
    public const DUREE_SECONDES = 300;

    public function creer(User $utilisateur, string $nomAppareil): string
    {
        $jeton = Str::random(64);

        Cache::put($this->cle($jeton), [
            'user_id' => $utilisateur->getKey(),
            'device_name' => trim($nomAppareil),
        ], now()->addSeconds(self::DUREE_SECONDES));

        return $jeton;
    }

    public function recuperer(string $jeton): ?array
    {
        $defi = Cache::get($this->cle($jeton));

        return is_array($defi) ? $defi : null;
    }

    public function invalider(string $jeton): void
    {
        Cache::forget($this->cle($jeton));
    }

    private function cle(string $jeton): string
    {
        return 'mobile-2fa:'.hash('sha256', $jeton);
    }
}
