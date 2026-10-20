<?php

namespace App\Http\Controllers;

use App\Rules\SansNomPrenom;

use App\Services\TwoFactorAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ParametresController extends Controller
{
    // Affiche le profil et l etat de la double authentification du compte connecte.
    public function index(TwoFactorAuthService $twoFactorAuthService)
    {
        $user = auth()->user()->load(['personnel', 'etudiant']);

        $profil = [
            'nom' => $user->name,
            'email' => $user->email,
            'service' => $this->serviceUtilisateur($user),
            'poste' => $this->posteUtilisateur($user),
            'role' => $this->roleUtilisateur($user),
        ];

        $doubleAuthentification = [
            'active' => $user->doubleAuthentificationActive(),
            'en_attente' => filled($user->two_factor_secret)
                && $user->two_factor_confirmed_at === null,
            'qr_code' => null,
            'secret' => null,
        ];

        if ($doubleAuthentification['en_attente']) {
            $doubleAuthentification['qr_code'] = $twoFactorAuthService->genererQrCode(
                $user,
                $user->two_factor_secret
            );
            $doubleAuthentification['secret'] = $user->two_factor_secret;
        }

        return view('systeme.parametres', compact('profil', 'doubleAuthentification'));
    }

    // Prepare un secret et un QR code sans activer la protection avant confirmation.
    public function preparerDoubleAuthentification(
        Request $request,
        TwoFactorAuthService $twoFactorAuthService
    ) {
        $utilisateur = $request->user();

        if ($utilisateur->doubleAuthentificationActive()) {
            return back()->with('info', __('messages.double_authentification_deja_active'));
        }

        $utilisateur->forceFill([
            'two_factor_secret' => $twoFactorAuthService->genererSecret(),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()
            ->route('parametres.index')
            ->with('success', __('messages.double_authentification_prete'));
    }

    // Verifie le premier code TOTP puis active la protection et les codes de recuperation.
    public function confirmerDoubleAuthentification(
        Request $request,
        TwoFactorAuthService $twoFactorAuthService
    ) {
        $donnees = $request->validate([
            'code_2fa' => ['required', 'digits:6'],
        ], [
            'code_2fa.required' => __('messages.double_authentification_code_obligatoire'),
            'code_2fa.digits' => __('messages.double_authentification_code_format'),
        ]);

        $utilisateur = $request->user();

        if ($utilisateur->doubleAuthentificationActive()) {
            return back()->with('info', __('messages.double_authentification_deja_active'));
        }

        if (blank($utilisateur->two_factor_secret)) {
            return back()->withErrors([
                'code_2fa' => __('messages.double_authentification_preparation_absente'),
            ]);
        }

        if (! $twoFactorAuthService->verifierCode(
            $utilisateur->two_factor_secret,
            $donnees['code_2fa']
        )) {
            return back()->withErrors([
                'code_2fa' => __('messages.double_authentification_code_invalide'),
            ])->withInput();
        }

        $codesRecuperation = $twoFactorAuthService->genererCodesRecuperation();

        $utilisateur->forceFill([
            'two_factor_recovery_codes' => $twoFactorAuthService->hacherCodesRecuperation($codesRecuperation),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return redirect()
            ->route('parametres.index')
            ->with('success', __('messages.double_authentification_activee'))
            ->with('codes_recuperation_2fa', $codesRecuperation);
    }

    // Efface la preparation 2FA non encore confirmee.
    public function annulerPreparationDoubleAuthentification(
        Request $request,
        TwoFactorAuthService $twoFactorAuthService
    ) {
        $utilisateur = $request->user();

        if ($utilisateur->doubleAuthentificationActive()) {
            return back()->with('info', __('messages.double_authentification_deja_active'));
        }

        $twoFactorAuthService->desactiver($utilisateur);

        return redirect()
            ->route('parametres.index')
            ->with('success', __('messages.double_authentification_preparation_annulee'));
    }

    // Verifie le mot de passe puis remplace les anciens codes de recuperation.
    public function regenererCodesDoubleAuthentification(
        Request $request,
        TwoFactorAuthService $twoFactorAuthService
    ) {
        if ($reponse = $this->validerMotDePasseDoubleAuthentification($request, 'regenerer')) {
            return $reponse;
        }

        $utilisateur = $request->user();

        if (! $utilisateur->doubleAuthentificationActive()) {
            return back()->withErrors([
                'mot_de_passe_2fa' => __('messages.double_authentification_inactive'),
            ]);
        }

        $codesRecuperation = $twoFactorAuthService->regenererCodesRecuperation($utilisateur);

        return redirect()
            ->route('parametres.index')
            ->with('success', __('messages.double_authentification_codes_regeneres'))
            ->with('codes_recuperation_2fa', $codesRecuperation);
    }

    // Verifie le mot de passe avant de supprimer les donnees de protection 2FA.
    public function desactiverDoubleAuthentification(
        Request $request,
        TwoFactorAuthService $twoFactorAuthService
    ) {
        if ($reponse = $this->validerMotDePasseDoubleAuthentification($request, 'desactiver')) {
            return $reponse;
        }

        $utilisateur = $request->user();

        if (! $utilisateur->doubleAuthentificationActive()) {
            return back()->withErrors([
                'mot_de_passe_2fa' => __('messages.double_authentification_inactive'),
            ]);
        }

        $twoFactorAuthService->desactiver($utilisateur);

        return redirect()
            ->route('parametres.index')
            ->with('success', __('messages.double_authentification_desactivee'));
    }

    // Controle le mot de passe actuel avant une operation sensible sur la 2FA.
    private function validerMotDePasseDoubleAuthentification(
        Request $request,
        string $modal
    ): ?RedirectResponse {
        $validation = Validator::make([
            'mot_de_passe_2fa' => $request->input('mot_de_passe_2fa'),
        ], [
            'mot_de_passe_2fa' => ['required', 'current_password'],
        ], [
            'mot_de_passe_2fa.required' => __('messages.double_authentification_mot_de_passe_obligatoire'),
            'mot_de_passe_2fa.current_password' => __('messages.mot_de_passe_actuel_incorrect'),
        ]);

        if (! $validation->fails()) {
            return null;
        }

        return back()
            ->withErrors($validation)
            ->with('modal_2fa', $modal);
    }

    // Valide le mot de passe actuel et les regles du nouveau avant de l enregistrer.
    public function updateMotDePasse(Request $request)
    {
        $request->validate([
            'mot_de_passe_actuel' => ['required', 'current_password'],
            'nouveau_mot_de_passe' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'different:mot_de_passe_actuel',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
                new SansNomPrenom($request->user()->name),
            ],
        ], [
            'mot_de_passe_actuel.required' => __('messages.mot_de_passe_actuel_obligatoire'),
            'mot_de_passe_actuel.current_password' => __('messages.mot_de_passe_actuel_incorrect'),
            'nouveau_mot_de_passe.required' => __('messages.nouveau_mot_de_passe_obligatoire'),
            'nouveau_mot_de_passe.min' => __('messages.nouveau_mot_de_passe_min', ['min' => 8]),
            'nouveau_mot_de_passe.confirmed' => __('messages.nouveau_mot_de_passe_confirmation'),
            'nouveau_mot_de_passe.different' => __('messages.nouveau_mot_de_passe_different'),
            'nouveau_mot_de_passe.regex' => __('messages.nouveau_mot_de_passe_complexite'),
        ]);

        $request->user()->forceFill([
            'password' => $request->nouveau_mot_de_passe,
        ])->save();

        return back()->with('success', __('messages.mot_de_passe_mis_a_jour'));
    }

    // Choisit le service a afficher selon le profil personnel ou etudiant.
    private function serviceUtilisateur($user): string
    {
        if ($user->personnel) {
            return $user->personnel->service ?? 'Non renseigne';
        }

        if ($user->etudiant) {
            return 'Etudiant';
        }

        return 'Non renseigne';
    }

    // Choisit le poste ou la promotion a afficher pour le compte.
    private function posteUtilisateur($user): string
    {
        if ($user->personnel) {
            return $user->personnel->poste ?? 'Non renseigne';
        }

        if ($user->etudiant) {
            return $user->etudiant->promotion ?? 'Non renseigne';
        }

        return 'Non renseigne';
    }

    // Determine le role du compte depuis son profil specialise.
    private function roleUtilisateur($user): string
    {
        if ($user->personnel) {
            return $user->personnel->role ?? 'personnel';
        }

        if ($user->etudiant) {
            return 'etudiant';
        }

        return 'utilisateur';
    }

}
