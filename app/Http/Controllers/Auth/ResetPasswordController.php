<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\SansNomPrenom;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/login';

    // Definit les regles de validation du formulaire de reinitialisation du mot de passe.
    protected function rules(): array
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $utilisateur = User::query()
                        ->where('email', request()->string('email')->lower()->toString())
                        ->first();

                    if ($utilisateur) {
                        (new SansNomPrenom($utilisateur->name))->validate($attribute, $value, $fail);
                    }
                },
            ],
        ];
    }

    // Associe les erreurs de validation aux messages francais du formulaire.
    protected function validationErrorMessages(): array
    {
        return [
            'email.required' => 'L adresse e-mail est obligatoire.',
            'email.email' => 'L adresse e-mail n est pas valide.',
            'password.required' => __('messages.nouveau_mot_de_passe_obligatoire'),
            'password.min' => __('messages.nouveau_mot_de_passe_min', ['min' => 8]),
            'password.confirmed' => __('messages.nouveau_mot_de_passe_confirmation'),
            'password.regex' => __('messages.nouveau_mot_de_passe_complexite'),
        ];
    }

    // Enregistre le nouveau mot de passe via le modele et confirme la fin du changement temporaire.
    protected function resetPassword($user, $password): void
    {
        $user->forceFill([
            'password' => $password,
            'mot_de_passe_change' => true,
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));
    }

    // Redirige vers la connexion apres une reinitialisation reussie.
    protected function sendResetResponse(Request $request, $response)
    {
        return redirect()
            ->route('login')
            ->with('status', trans($response));
    }

}
