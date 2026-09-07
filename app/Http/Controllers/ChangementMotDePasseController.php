<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ChangementMotDePasseController extends Controller
{
    public function edit()
    {
        if (auth()->user()->mot_de_passe_change) {
            return redirect()->route('dashboard');
        }

        return response()
            ->view('auth.changer-mot-de-passe')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

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
            ],
        ], [
            'mot_de_passe_actuel.required' => 'Le mot de passe actuel est obligatoire.',
            'mot_de_passe_actuel.current_password' => 'Le mot de passe actuel est incorrect.',
            'nouveau_mot_de_passe.required' => 'Le nouveau mot de passe est obligatoire.',
            'nouveau_mot_de_passe.min' => 'Le nouveau mot de passe doit contenir au moins 8 caracteres.',
            'nouveau_mot_de_passe.confirmed' => 'La confirmation du nouveau mot de passe ne correspond pas.',
            'nouveau_mot_de_passe.different' => 'Le nouveau mot de passe doit etre different du mot de passe actuel.',
            'nouveau_mot_de_passe.regex' => 'Le nouveau mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractere special.',
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($request->nouveau_mot_de_passe),
            'mot_de_passe_change' => true,
        ])->save();

        return redirect()->route('dashboard')
            ->with('success', 'Votre mot de passe a bien ete change.');
    }
}
