<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class ParametresController extends Controller
{
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

        if ($this->motDePasseContientNom($request->user()->name, $request->nouveau_mot_de_passe)) {
            return back()->withErrors([
                'nouveau_mot_de_passe' => __('messages.nouveau_mot_de_passe_nom'),
            ]);
        }

        $request->user()->forceFill([
            'password' => $request->nouveau_mot_de_passe,
        ])->save();

        return back()->with('success', __('messages.mot_de_passe_mis_a_jour'));
    }

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

    private function motDePasseContientNom(string $nomComplet, string $motDePasse): bool
    {
        $motDePasse = Str::lower($motDePasse);
        $morceauxNom = preg_split('/\s+/', Str::lower($nomComplet));

        foreach ($morceauxNom as $morceau) {
            if (Str::length($morceau) >= 3 && Str::contains($motDePasse, $morceau)) {
                return true;
            }
        }

        return false;
    }
}
