<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\VerifierAdresseEmailMobile;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationEmailController extends Controller
{
    // Renvoie l etat reel de verification de l adresse e-mail du compte.
    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'email_verified' => $request->user()->fresh()->hasVerifiedEmail(),
        ]);
    }

    // Renvoie un lien de verification si le compte n est pas encore verifie.
    public function resend(Request $request): JsonResponse
    {
        $utilisateur = $request->user()->fresh();

        if ($utilisateur->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Votre adresse e-mail est deja verifiee.',
                'email_verified' => true,
            ]);
        }

        $utilisateur->notify(new VerifierAdresseEmailMobile());

        return response()->json([
            'message' => 'Un nouveau lien de verification vient d etre envoye.',
            'email_verified' => false,
        ]);
    }

    // Controle le lien signe et l identite avant de marquer l adresse e-mail comme verifiee.
    public function verify(Request $request, int $id, string $hash): View
    {
        $utilisateur = User::query()->findOrFail($id);

        abort_unless(
            hash_equals(sha1($utilisateur->getEmailForVerification()), $hash),
            403
        );

        if (! $utilisateur->hasVerifiedEmail()) {
            $utilisateur->markEmailAsVerified();
            event(new Verified($utilisateur));
        }

        return view('auth.verification-reussie');
    }
}
