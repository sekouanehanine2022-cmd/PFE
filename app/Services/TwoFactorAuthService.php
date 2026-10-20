<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FALaravel\Google2FA;

class TwoFactorAuthService
{
    public const NOMBRE_CODES_RECUPERATION = 8;

    // Recoit et garde les objets ou les valeurs utilises par les autres methodes.
    public function __construct(private readonly Google2FA $google2fa) {}

    // Cree la cle secrete utilisee par l'application d'authentification pour calculer les codes a six chiffres.
    public function genererSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    // Cree le QR code a scanner avec l'application d'authentification.
    public function genererQrCode(User $utilisateur, string $secret): string
    {
        return $this->google2fa->getQRCodeInline(
            config('app.name', 'IEG Parc Info'),
            $utilisateur->email,
            $secret,
            220
        );
    }

    // Verifie le code a six chiffres avec la bibliotheque 2FA, en acceptant un petit decalage d'heure.
    public function verifierCode(string $secret, string $code): bool
    {
        $code = trim($code);

        if (! preg_match('/^[0-9]{6}$/', $code)) {
            return false;
        }

        return (bool) $this->google2fa->verifyKey($secret, $code, 1);
    }

    // Genere des codes de secours distincts a remettre une seule fois a l utilisateur.
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

    // Transforme chaque code de recuperation en hash avant de le stocker.
    public function hacherCodesRecuperation(array $codes): array
    {
        return array_map(
            static fn (string $code): string => Hash::make(Str::upper(trim($code))),
            $codes
        );
    }

    // Remplace les anciens codes de recuperation par de nouveaux codes stockes sous forme hachee.
    public function regenererCodesRecuperation(User $utilisateur): array
    {
        $codes = $this->genererCodesRecuperation();

        $utilisateur->forceFill([
            'two_factor_recovery_codes' => $this->hacherCodesRecuperation($codes),
        ])->save();

        return $codes;
    }

    // Verifie le code de recuperation et le retire des codes disponibles s'il est correct.
    public function utiliserCodeRecuperation(User $utilisateur, string $code): bool
    {
        $code = Str::upper(trim($code));
        $codesHaches = $utilisateur->two_factor_recovery_codes ?? [];

        foreach ($codesHaches as $index => $codeHache) {
            if (! Hash::check($code, $codeHache)) {
                continue;
            }

            // Un code de secours valide est retire pour ne plus pouvoir servir une seconde fois.
            unset($codesHaches[$index]);
            $utilisateur->forceFill([
                'two_factor_recovery_codes' => array_values($codesHaches),
            ])->save();

            return true;
        }

        return false;
    }

    // Efface le secret, les codes de secours et la confirmation 2FA du compte.
    public function desactiver(User $utilisateur): void
    {
        $utilisateur->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }
}
