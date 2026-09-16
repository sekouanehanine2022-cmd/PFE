<?php

namespace App\Http\Controllers;

use App\Models\Imprimante;
use App\Models\Materiel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ImprimanteController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->get('search', '');
        $etat      = $request->get('etat', '');

        $imprimantes = Imprimante::with('materiel')
            ->when($recherche, function($query) use ($recherche) {
                $query->where(function ($query) use ($recherche) {
                    $query->where('numero_serie', 'like', '%'.$recherche.'%')
                          ->orWhereHas('materiel', function ($query) use ($recherche) {
                              $query->where('nom', 'like', '%'.$recherche.'%')
                                    ->orWhere('marque', 'like', '%'.$recherche.'%');
                          });
                });
            })
            ->when($etat, function($query) use ($etat) {
                $query->whereHas('materiel', function ($query) use ($etat) {
                    $query->where('etat', $etat);
                });
            })
            ->get();

        $total       = Imprimante::count();
        $disponibles = Imprimante::whereHas('materiel', fn ($query) => $query->where('etat', 'disponible'))->count();
        $enPanne     = Imprimante::whereHas('materiel', fn ($query) => $query->where('etat', 'en_panne'))->count();

        return view('materiel.imprimantes', compact(
            'imprimantes', 'total', 'disponibles', 'enPanne'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom'                   => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9 ._+-]+$/'],
            'marque'                => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9 ._+-]+$/'],
            'numero_serie'          => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:imprimantes,numero_serie'],
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

        DB::transaction(function () use ($request) {
            $materiel = Materiel::create($this->donneesMateriel($request, 'imprimante', true));

            $donnees = $this->donneesImprimante($request, true);
            $donnees['materiel_id'] = $materiel->id;

            Imprimante::create($donnees);
        });

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

        DB::transaction(function () use ($request, $imprimante) {
            $imprimante->materiel()->update($this->donneesMateriel($request, 'imprimante', true));
            $imprimante->update($this->donneesImprimante($request, false));
        });

        return redirect()->route('imprimantes.index')
                         ->with('success', __('messages.materiel_modifie', ['type' => 'Imprimante']));
    }

    public function destroy(Imprimante $imprimante)
    {
        DB::transaction(function () use ($imprimante) {
            if ($imprimante->materiel) {
                $imprimante->materiel->delete();
            } else {
                $imprimante->delete();
            }
        });

        return redirect()->route('imprimantes.index')
                         ->with('success', __('messages.materiel_supprime', ['type' => 'Imprimante']));
    }

    public function signalerPanne(Imprimante $imprimante)
    {
        $imprimante->materiel()->update(['etat' => 'en_panne']);

        return back()->with('success', __('messages.materiel_signale_panne', ['type' => 'Imprimante']));
    }

    private function donneesMateriel(Request $request, string $typeMateriel, bool $avecEtat): array
    {
        $donnees = [
            'type_materiel' => $typeMateriel,
            'nom'           => $request->nom,
            'marque'        => $request->marque,
            'emplacement'   => $request->emplacement,
            'date_achat'    => $request->date_achat,
        ];

        if ($avecEtat) {
            $donnees['etat'] = $request->etat ?: 'disponible';
        }

        return $donnees;
    }

    private function donneesImprimante(Request $request, bool $creation = true): array
    {
        $champs = [
            'type_impression',
            'couleur',
            'connexion',
            'vitesse',
        ];

        if ($creation) {
            array_unshift($champs, 'numero_serie');
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
        $imprimante->materiel()->update(['etat' => 'disponible']);

        return back()->with('success', __('messages.materiel_marque_disponible', ['type' => 'Imprimante']));
    }
}
