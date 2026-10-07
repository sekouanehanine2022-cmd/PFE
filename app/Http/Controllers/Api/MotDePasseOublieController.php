<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\CodeReinitialisationMotDePasseMobile;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MotDePasseOublieController extends Controller
{
    public function envoyerCode(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'L adresse e-mail est obligatoire.',
            'email.email' => 'L adresse e-mail n est pas valide.',
        ]);

        $email = Str::lower($donnees['email']);
        $utilisateur = User::query()->where('email', $email)->first();

        if (! $utilisateur) {
            throw ValidationException::withMessages([
                'email' => __('passwords.user'),
            ]);
        }

        $code = (string) random_int(100000, 999999);
        $expiration = (int) config('auth.passwords.users.expire', 2);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($code),
                'created_at' => now(),
            ]
        );

        $utilisateur->notify(new CodeReinitialisationMotDePasseMobile($code, $expiration));

        return response()->json([
            'message' => 'Un code de reinitialisation vient d etre envoye par e-mail.',
            'expires_in_minutes' => $expiration,
        ]);
    }

    public function reinitialiser(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
            ],
        ], [
            'email.required' => 'L adresse e-mail est obligatoire.',
            'email.email' => 'L adresse e-mail n est pas valide.',
            'code.required' => 'Le code de verification est obligatoire.',
            'code.digits' => 'Le code doit contenir exactement 6 chiffres.',
            'password.required' => __('messages.nouveau_mot_de_passe_obligatoire'),
            'password.min' => __('messages.nouveau_mot_de_passe_min', ['min' => 8]),
            'password.confirmed' => __('messages.nouveau_mot_de_passe_confirmation'),
            'password.regex' => __('messages.nouveau_mot_de_passe_complexite'),
        ]);

        $email = Str::lower($donnees['email']);
        $utilisateur = User::query()->where('email', $email)->first();
        $reinitialisation = DB::table('password_reset_tokens')->where('email', $email)->first();
        $expiration = (int) config('auth.passwords.users.expire', 2);

        if (! $utilisateur || ! $reinitialisation ||
            Carbon::parse($reinitialisation->created_at)->addMinutes($expiration)->isPast() ||
            ! Hash::check($donnees['code'], $reinitialisation->token)) {
            throw ValidationException::withMessages([
                'code' => 'Le code est incorrect ou a expire. Demandez un nouveau code.',
            ]);
        }

        if ($this->motDePasseContientNom($utilisateur->name, $donnees['password'])) {
            throw ValidationException::withMessages([
                'password' => __('messages.nouveau_mot_de_passe_nom'),
            ]);
        }

        $utilisateur->forceFill([
            'password' => $donnees['password'],
            'mot_de_passe_change' => true,
            'remember_token' => Str::random(60),
        ])->save();

        $utilisateur->tokens()->delete();
        DB::table('password_reset_tokens')->where('email', $email)->delete();
        event(new PasswordReset($utilisateur));

        return response()->json([
            'message' => 'Votre mot de passe a bien ete reinitialise.',
        ]);
    }

    private function motDePasseContientNom(string $nomComplet, string $motDePasse): bool
    {
        $motDePasseNormalise = Str::lower(Str::ascii($motDePasse));
        $partiesNom = preg_split('/\s+/', Str::lower(Str::ascii(trim($nomComplet)))) ?: [];

        foreach ($partiesNom as $partie) {
            if (mb_strlen($partie) >= 3 && str_contains($motDePasseNormalise, $partie)) {
                return true;
            }
        }

        return false;
    }
}
