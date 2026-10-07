<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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

                    if ($utilisateur && $this->motDePasseContientNom($utilisateur->name, (string) $value)) {
                        $fail(__('messages.nouveau_mot_de_passe_nom'));
                    }
                },
            ],
        ];
    }

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

    protected function resetPassword($user, $password): void
    {
        $user->forceFill([
            'password' => $password,
            'mot_de_passe_change' => true,
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));
    }

    protected function sendResetResponse(Request $request, $response)
    {
        return redirect()
            ->route('login')
            ->with('status', trans($response));
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
