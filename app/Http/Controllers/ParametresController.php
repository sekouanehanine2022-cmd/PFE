<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ParametresController extends Controller
{
    public function index()
    {
        $user = auth()->user()->load(['personnel', 'etudiant']);

        $profil = [
            'nom' => $user->name,
            'email' => $user->email,
            'service' => $this->serviceUtilisateur($user),
            'poste' => $this->posteUtilisateur($user),
            'role' => $this->roleUtilisateur($user),
        ];

        return view('systeme.parametres', compact('profil'));
    }

    public function updateMotDePasse(Request $request)
    {
        $request->validate([
            'mot_de_passe_actuel' => ['required', 'current_password'],
            'nouveau_mot_de_passe' => [
                'required',
                'string',
                'min:12',
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
            'nouveau_mot_de_passe.min' => __('messages.nouveau_mot_de_passe_min', ['min' => 12]),
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
