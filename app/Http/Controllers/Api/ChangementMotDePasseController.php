<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChangementMotDePasseController extends Controller
{
    public function update(Request $request): JsonResponse
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

        $request->user()->forceFill([
            'password' => $request->string('nouveau_mot_de_passe')->toString(),
            'mot_de_passe_change' => true,
        ])->save();

        return response()->json([
            'message' => __('messages.mot_de_passe_change'),
            'must_change_password' => false,
        ]);
    }
}
