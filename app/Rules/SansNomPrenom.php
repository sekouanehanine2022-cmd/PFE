<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class SansNomPrenom implements ValidationRule
{
    // Recoit et garde les objets ou les valeurs utilises par les autres methodes.
    public function __construct(private readonly string $nomComplet) {}

    // Refuse un mot de passe contenant un element du nom d au moins trois caracteres, sans tenir compte des accents ou de la casse.
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $motDePasse = Str::lower(Str::ascii($value));
        $partiesNom = preg_split('/[^a-z0-9]+/', Str::lower(Str::ascii($this->nomComplet)), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($partiesNom as $partie) {
            if (strlen($partie) >= 3 && str_contains($motDePasse, $partie)) {
                $fail(__('messages.nouveau_mot_de_passe_nom'));
                return;
            }
        }
    }
}
