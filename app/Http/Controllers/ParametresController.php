<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            'mot_de_passe_actuel.required' => 'Le mot de passe actuel est obligatoire.',
            'mot_de_passe_actuel.current_password' => 'Le mot de passe actuel est incorrect.',
            'nouveau_mot_de_passe.required' => 'Le nouveau mot de passe est obligatoire.',
            'nouveau_mot_de_passe.min' => 'Le nouveau mot de passe doit contenir au moins 12 caracteres.',
            'nouveau_mot_de_passe.confirmed' => 'La confirmation du nouveau mot de passe ne correspond pas.',
            'nouveau_mot_de_passe.different' => 'Le nouveau mot de passe doit etre different du mot de passe actuel.',
            'nouveau_mot_de_passe.regex' => 'Le nouveau mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractere special.',
        ]);

        if ($this->motDePasseContientNom($request->user()->name, $request->nouveau_mot_de_passe)) {
            return back()->withErrors([
                'nouveau_mot_de_passe' => 'Le nouveau mot de passe ne doit pas contenir votre nom ou prenom.',
            ]);
        }

        $request->user()->forceFill([
            'password' => Hash::make($request->nouveau_mot_de_passe),
        ])->save();

        return back()->with('success', 'Votre mot de passe a bien ete mis a jour.');
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
