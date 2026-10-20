<?php

namespace App\Http\Controllers;

use App\Rules\SansNomPrenom;

use Illuminate\Http\Request;

class ChangementMotDePasseController extends Controller
{
    // Affiche le changement obligatoire du mot de passe temporaire si celui-ci reste a faire.
    public function edit()
    {
        if (auth()->user()->mot_de_passe_change) {
            return redirect()->route($this->routeAccueil(auth()->user()));
        }

        return response()
            ->view('auth.changer-mot-de-passe')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    // Valide le nouveau mot de passe et leve l obligation de changement du compte.
    public function update(Request $request)
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
            'mot_de_passe_change' => true,
        ])->save();

        return redirect()->route($this->routeAccueil($request->user()))
            ->with('success', __('messages.mot_de_passe_change'));
    }

    // Oriente l administrateur vers le Dashboard et les autres profils vers Mon materiel.
    private function routeAccueil($utilisateur): string
    {
        $utilisateur->loadMissing('personnel');

        return $utilisateur->personnel?->role === 'admin'
            ? 'dashboard'
            : 'mon-materiel.index';
    }
}
