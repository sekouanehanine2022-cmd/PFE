<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MobileTwoFactorChallengeService;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthentificationController extends Controller
{
    public function login(
        Request $request,
        MobileTwoFactorChallengeService $challengeService
    ): JsonResponse
    {
        $donnees = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $utilisateur = User::query()
            ->with(['personnel', 'etudiant'])
            ->where('email', Str::lower(trim($donnees['email'])))
            ->first();

        if (! $utilisateur || ! Hash::check($donnees['password'], $utilisateur->password)) {
            throw ValidationException::withMessages([
                'email' => [__('messages.connexion_identifiants_invalides')],
            ]);
        }

        if ($utilisateur->acces_bloque) {
            return response()->json([
                'message' => __('messages.compte_acces_bloque'),
            ], Response::HTTP_FORBIDDEN);
        }

        if (! $this->peutUtiliserApplicationMobile($utilisateur)) {
            return response()->json([
                'message' => __('messages.application_mobile_acces_admin_interdit'),
            ], Response::HTTP_FORBIDDEN);
        }

        if ($utilisateur->doubleAuthentificationActive()) {
            return response()->json([
                'message' => __('messages.double_authentification_mobile_requise'),
                'two_factor_required' => true,
                'challenge_token' => $challengeService->creer(
                    $utilisateur,
                    $donnees['device_name']
                ),
                'expires_in' => MobileTwoFactorChallengeService::DUREE_SECONDES,
            ], Response::HTTP_ACCEPTED);
        }

        return $this->reponseConnexion($utilisateur, $donnees['device_name']);
    }

    public function verifierDoubleAuthentification(
        Request $request,
        MobileTwoFactorChallengeService $challengeService,
        TwoFactorAuthService $twoFactorAuthService
    ): JsonResponse {
        $donnees = $request->validate([
            'challenge_token' => ['required', 'string', 'size:64'],
            'mode' => ['required', 'in:application,recuperation'],
            'code' => ['required_if:mode,application', 'nullable', 'digits:6'],
            'recovery_code' => [
                'required_if:mode,recuperation',
                'nullable',
                'regex:/^[A-Za-z0-9]{5}-[A-Za-z0-9]{5}$/',
            ],
        ], [
            'code.required_if' => __('messages.double_authentification_code_obligatoire'),
            'code.digits' => __('messages.double_authentification_code_format'),
            'recovery_code.required_if' => __('messages.double_authentification_recuperation_obligatoire'),
            'recovery_code.regex' => __('messages.double_authentification_recuperation_format'),
        ]);

        $defi = $challengeService->recuperer($donnees['challenge_token']);

        if (! $defi) {
            return response()->json([
                'message' => __('messages.double_authentification_mobile_expiree'),
            ], Response::HTTP_GONE);
        }

        $utilisateur = User::query()
            ->with(['personnel', 'etudiant'])
            ->find($defi['user_id']);

        if (! $utilisateur
            || $utilisateur->acces_bloque
            || ! $utilisateur->doubleAuthentificationActive()
            || ! $this->peutUtiliserApplicationMobile($utilisateur)) {
            $challengeService->invalider($donnees['challenge_token']);

            return response()->json([
                'message' => __('messages.double_authentification_mobile_expiree'),
            ], Response::HTTP_GONE);
        }

        $valide = $donnees['mode'] === 'recuperation'
            ? $twoFactorAuthService->utiliserCodeRecuperation(
                $utilisateur,
                (string) $donnees['recovery_code']
            )
            : $twoFactorAuthService->verifierCode(
                $utilisateur->two_factor_secret,
                (string) $donnees['code']
            );

        if (! $valide) {
            throw ValidationException::withMessages([
                $donnees['mode'] === 'recuperation' ? 'recovery_code' : 'code' => [
                    $donnees['mode'] === 'recuperation'
                        ? __('messages.double_authentification_recuperation_invalide')
                        : __('messages.double_authentification_code_invalide'),
                ],
            ]);
        }

        $challengeService->invalider($donnees['challenge_token']);

        return $this->reponseConnexion($utilisateur, $defi['device_name']);
    }

    private function reponseConnexion(User $utilisateur, string $nomAppareil): JsonResponse
    {
        $jeton = $utilisateur->createToken(trim($nomAppareil))->plainTextToken;

        return response()->json([
            'message' => __('messages.connexion_mobile_reussie'),
            'token' => $jeton,
            'token_type' => 'Bearer',
            'user' => $this->donneesUtilisateur($utilisateur),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $utilisateur = $request->user()->loadMissing(['personnel', 'etudiant']);

        if (! $this->peutUtiliserApplicationMobile($utilisateur)) {
            $utilisateur->currentAccessToken()?->delete();

            return response()->json([
                'message' => __('messages.application_mobile_acces_admin_interdit'),
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'user' => $this->donneesUtilisateur($utilisateur),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => __('messages.deconnexion_mobile_reussie'),
        ]);
    }

    private function donneesUtilisateur(User $utilisateur): array
    {
        $role = $utilisateur->personnel?->role
            ?? ($utilisateur->etudiant ? 'etudiant' : null);

        return [
            'id' => $utilisateur->id,
            'name' => $utilisateur->name,
            'email' => $utilisateur->email,
            'role' => $role,
            'email_verified' => $utilisateur->hasVerifiedEmail(),
            'must_change_password' => ! $utilisateur->mot_de_passe_change,
        ];
    }

    private function peutUtiliserApplicationMobile(User $utilisateur): bool
    {
        if ($utilisateur->etudiant) {
            return true;
        }

        return $utilisateur->personnel
            && ! in_array(Str::lower((string) $utilisateur->personnel->role), ['admin', 'technicien'], true);
    }
}
