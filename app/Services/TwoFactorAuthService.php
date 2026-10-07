<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FALaravel\Google2FA;

class TwoFactorAuthService
{
    public const NOMBRE_CODES_RECUPERATION = 8;

    public function __construct(private readonly Google2FA $google2fa) {}

    public function genererSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function genererQrCode(User $utilisateur, string $secret): string
    {
        return $this->google2fa->getQRCodeInline(
            config('app.name', 'IEG Parc Info'),
            $utilisateur->email,
            $secret,
            220
        );
    }

    public function verifierCode(string $secret, string $code): bool
    {
        $code = trim($code);

        if (! preg_match('/^[0-9]{6}$/', $code)) {
            return false;
        }

        return (bool) $this->google2fa->verifyKey($secret, $code, 1);
    }

    public function genererCodesRecuperation(): array
    {
        $codes = [];

        while (count($codes) < self::NOMBRE_CODES_RECUPERATION) {
            $valeur = Str::upper(Str::random(10));
            $code = substr($valeur, 0, 5).'-'.substr($valeur, 5);
            $codes[$code] = true;
        }

        return array_keys($codes);
    }

    public function hacherCodesRecuperation(array $codes): array
    {
        return array_map(
            static fn (string $code): string => Hash::make(Str::upper(trim($code))),
            $codes
        );
    }

    public function regenererCodesRecuperation(User $utilisateur): array
    {
        $codes = $this->genererCodesRecuperation();

        $utilisateur->forceFill([
            'two_factor_recovery_codes' => $this->hacherCodesRecuperation($codes),
        ])->save();

        return $codes;
    }

    public function utiliserCodeRecuperation(User $utilisateur, string $code): bool
    {
        $code = Str::upper(trim($code));
        $codesHaches = $utilisateur->two_factor_recovery_codes ?? [];

        foreach ($codesHaches as $index => $codeHache) {
            if (! Hash::check($code, $codeHache)) {
                continue;
            }

            unset($codesHaches[$index]);
            $utilisateur->forceFill([
                'two_factor_recovery_codes' => array_values($codesHaches),
            ])->save();

            return true;
        }

        return false;
    }

    public function desactiver(User $utilisateur): void
    {
        $utilisateur->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }
}
