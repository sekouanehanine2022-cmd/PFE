<?php

namespace App\Http\Controllers;

use App\Models\Imprimante;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ImprimanteController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->get('search', '');
        $etat      = $request->get('etat', '');

        $imprimantes = Imprimante::when($recherche, function($query) use ($recherche) {
                $query->where('nom', 'like', '%'.$recherche.'%')
                      ->orWhere('marque', 'like', '%'.$recherche.'%')
                      ->orWhere('numero_serie', 'like', '%'.$recherche.'%');
            })
            ->when($etat, function($query) use ($etat) {
                $query->where('etat', $etat);
            })
            ->get();

        $total       = Imprimante::count();
        $disponibles = Imprimante::where('etat', 'disponible')->count();
        $enPanne     = Imprimante::where('etat', 'en_panne')->count();

        return view('materiel.imprimantes', compact(
            'imprimantes', 'total', 'disponibles', 'enPanne'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'reference'             => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/', 'unique:imprimantes,reference'],
            'nom'                   => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9 ._+-]+$/'],
            'marque'                => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9 ._+-]+$/'],
            'numero_serie'          => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9 ._-]+$/', 'unique:imprimantes,numero_serie'],
            'type_impression'       => ['required', Rule::in(['Laser', "Jet d'encre", 'Thermique', 'autre'])],
            'type_impression_autre' => ['nullable', 'required_if:type_impression,autre', 'string', 'max:30', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'couleur'               => ['nullable', 'boolean'],
            'connexion'             => ['required', Rule::in(['Wi-Fi', 'USB', 'Ethernet', 'Bluetooth', 'autre'])],
            'connexion_autre'       => ['nullable', 'required_if:connexion,autre', 'string', 'max:30', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'vitesse'               => ['nullable', 'string', 'max:10', 'regex:/^[0-9]{1,3}\s?ppm$/i'],
            'etat'                  => ['nullable', Rule::in(['disponible', 'en_panne'])],
            'emplacement'           => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'date_achat'            => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        Imprimante::create($this->donneesImprimante($request));

        return redirect()->route('imprimantes.index')
                         ->with('success', __('messages.materiel_ajoute', ['type' => 'Imprimante']));
    }

    public function update(Request $request, Imprimante $imprimante)
    {
        $request->validate([
            'nom'                   => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9 ._+-]+$/'],
            'marque'                => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9 ._+-]+$/'],
            'type_impression'       => ['required', Rule::in(['Laser', "Jet d'encre", 'Thermique', 'autre'])],
            'type_impression_autre' => ['nullable', 'required_if:type_impression,autre', 'string', 'max:30', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'couleur'               => ['nullable', 'boolean'],
            'connexion'             => ['required', Rule::in(['Wi-Fi', 'USB', 'Ethernet', 'Bluetooth', 'autre'])],
            'connexion_autre'       => ['nullable', 'required_if:connexion,autre', 'string', 'max:30', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'vitesse'               => ['nullable', 'string', 'max:10', 'regex:/^[0-9]{1,3}\s?ppm$/i'],
            'etat'                  => ['nullable', Rule::in(['disponible', 'en_panne'])],
            'emplacement'           => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'date_achat'            => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $imprimante->update($this->donneesImprimante($request, false));

        return redirect()->route('imprimantes.index')
                         ->with('success', __('messages.materiel_modifie', ['type' => 'Imprimante']));
    }

    public function destroy(Imprimante $imprimante)
    {
        $imprimante->delete();

        return redirect()->route('imprimantes.index')
                         ->with('success', __('messages.materiel_supprime', ['type' => 'Imprimante']));
    }

    public function signalerPanne(Imprimante $imprimante)
    {
        $imprimante->update(['etat' => 'en_panne']);

        return back()->with('success', __('messages.materiel_signale_panne', ['type' => 'Imprimante']));
    }

    private function donneesImprimante(Request $request, bool $creation = true): array
    {
        $champs = [
            'nom',
            'marque',
            'type_impression',
            'couleur',
            'connexion',
            'vitesse',
            'etat',
            'emplacement',
            'date_achat',
        ];

        if ($creation) {
            array_unshift($champs, 'reference', 'numero_serie');
        }

        $donnees = $request->only($champs);

        if ($request->type_impression === 'autre') {
            $donnees['type_impression'] = $request->type_impression_autre;
        }

        if ($request->connexion === 'autre') {
            $donnees['connexion'] = $request->connexion_autre;
        }

        return $donnees;
    }

    public function marquerRepare(Imprimante $imprimante)
    {
        $imprimante->update(['etat' => 'disponible']);

        return back()->with('success', __('messages.materiel_marque_disponible', ['type' => 'Imprimante']));
    }
}
