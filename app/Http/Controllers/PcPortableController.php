<?php

namespace App\Http\Controllers;

use App\Models\Materiel;
use App\Models\PcPortable;
use App\Services\AffectationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PcPortableController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->get('search', '');
        $etat      = $request->get('etat', '');

        $pcPortables = PcPortable::with(['materiel', 'affectations.personnel.user', 'emprunts.etudiant.user'])
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

        $total       = PcPortable::count();
        $disponibles = PcPortable::whereHas('materiel', fn ($query) => $query->where('etat', 'disponible'))->count();
        $affectes    = PcPortable::whereHas('materiel', fn ($query) => $query->where('etat', 'affecte'))->count();
        $enPanne     = PcPortable::whereHas('materiel', fn ($query) => $query->where('etat', 'en_panne'))->count();

        return view('materiel.pc-portables', compact(
            'pcPortables', 'total', 'disponibles', 'affectes', 'enPanne'
        ));
    }

    public function create()
    {
        return view('materiel.pc-portables-create');
    }

    public function store(Request $request, AffectationService $affectationService)
    {
        $request->validate([
            'nom'          => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9 ._-]+$/'],
            'marque'       => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9 ._-]+$/'],
            'numero_serie' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:pc_portables,numero_serie'],
            'adresse_mac'  => ['nullable', 'string', 'max:17', 'regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/'],
            'cpu'          => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'ram'          => ['required', 'string', Rule::in(['4 Go DDR3', '8 Go DDR4', '16 Go DDR4', '16 Go DDR5', '32 Go DDR5', '64 Go DDR5', 'autre'])],
            'ram_autre'    => ['nullable', 'required_if:ram,autre', 'string', 'max:30', 'regex:/^[0-9]{1,3} Go DDR[3-5]$/'],
            'stockage'     => ['required', 'string', Rule::in(['128 Go SSD', '256 Go SSD', '512 Go SSD', '1 To SSD', '1 To HDD', '2 To SSD', 'autre'])],
            'stockage_autre' => ['nullable', 'required_if:stockage,autre', 'string', 'max:30', 'regex:/^[0-9]{1,4} (Go|To) (SSD|HDD|NVMe)$/'],
            'os'           => ['required', 'string', 'max:50', Rule::in(['Windows 10', 'Windows 11', 'macOS', 'Linux', 'autre'])],
            'os_autre'     => ['nullable', 'required_if:os,autre', 'string', 'max:50', 'regex:/^[A-Za-z0-9 ._+()-]+$/'],
            'ecran'        => ['nullable', 'string', 'max:5', 'regex:/^[0-9]{2}([.,][0-9])?$/'],
            'etat'         => ['nullable', Rule::in(['disponible', 'affecte', 'emprunte', 'en_panne'])],
            'emplacement'  => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9 ._()\/-]+$/'],
            'date_achat'   => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $personnel = null;
        $etudiant  = null;

        if ($request->etat === 'affecte') {
            if (! $request->filled('a_qui_id')) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.collaborateur_obligatoire_affectation', ['type' => 'PC'])])
                    ->withInput();
            }

            $personnel = \App\Models\Personnel::where('user_id', $request->a_qui_id)->first();

            if (! $personnel) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.collaborateur_introuvable')])
                    ->withInput();
            }

            if ($affectationService->existeAffectationActivePourType($personnel->id, PcPortable::class)) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.collaborateur_deja_materiel_affecte', ['type' => 'PC portable'])])
                    ->withInput();
            }
        } elseif ($request->etat === 'emprunte') {
            if (! $request->filled('a_qui_id')) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.etudiant_obligatoire_emprunt', ['type' => 'PC'])])
                    ->withInput();
            }

            $etudiant = \App\Models\Etudiant::where('user_id', $request->a_qui_id)->first();

            if (! $etudiant) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.etudiant_introuvable')])
                    ->withInput();
            }
        }

        $pc = DB::transaction(function () use ($request, $personnel, $etudiant) {
            $materiel = Materiel::create($this->donneesMateriel($request, 'pc_portable', true));

            $donnees = $this->donneesPcPortable($request, true);
            $donnees['materiel_id'] = $materiel->id;

            $pc = PcPortable::create($donnees);

            if ($personnel) {
                \App\Models\Affectation::create([
                    'personnel_id'  => $personnel->id,
                    'materiel_id'   => $materiel->id,
                    'date_debut'    => now(),
                    'statut'        => 'active',
                    'ticket_id'     => null,
                ]);
            } elseif ($etudiant) {
                \App\Models\Emprunt::create([
                    'etudiant_id'    => $etudiant->id,
                    'materiel_id'    => $materiel->id,
                    'date_debut'     => now(),
                    'date_fin_prevue'=> now()->addMonths(3),
                    'statut'         => 'en_cours',
                    'ticket_id'      => null,
                ]);
            }

            return $pc;
        });

        return redirect()->route('pc-portables.index')
                         ->with('success', __('messages.materiel_ajoute', ['type' => 'PC portable']));
    }

    public function update(Request $request, PcPortable $pcPortable)
    {
        $request->validate([
            'nom'         => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9 ._-]+$/'],
            'marque'      => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9 ._-]+$/'],
            'adresse_mac' => ['nullable', 'string', 'max:17', 'regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/'],
            'cpu'         => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'ram'         => ['required', 'string', Rule::in(['4 Go DDR3', '8 Go DDR4', '16 Go DDR4', '16 Go DDR5', '32 Go DDR5', '64 Go DDR5', 'autre'])],
            'ram_autre'   => ['nullable', 'required_if:ram,autre', 'string', 'max:30', 'regex:/^[0-9]{1,3} Go DDR[3-5]$/'],
            'stockage'    => ['required', 'string', Rule::in(['128 Go SSD', '256 Go SSD', '512 Go SSD', '1 To SSD', '1 To HDD', '2 To SSD', 'autre'])],
            'stockage_autre' => ['nullable', 'required_if:stockage,autre', 'string', 'max:30', 'regex:/^[0-9]{1,4} (Go|To) (SSD|HDD|NVMe)$/'],
            'os'          => ['required', 'string', 'max:50', Rule::in(['Windows 10', 'Windows 11', 'macOS', 'Linux', 'autre'])],
            'os_autre'    => ['nullable', 'required_if:os,autre', 'string', 'max:50', 'regex:/^[A-Za-z0-9 ._+()-]+$/'],
            'ecran'       => ['nullable', 'string', 'max:5', 'regex:/^[0-9]{2}([.,][0-9])?$/'],
            'emplacement' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9 ._()\/-]+$/'],
            'date_achat'  => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        DB::transaction(function () use ($request, $pcPortable) {
            $pcPortable->materiel()->update($this->donneesMateriel($request, 'pc_portable', false));
            $pcPortable->update($this->donneesPcPortable($request, false));
        });

        return redirect()->route('pc-portables.index')
                         ->with('success', __('messages.materiel_modifie', ['type' => 'PC portable']));
    }

    public function destroy(PcPortable $pcPortable)
    {
        DB::transaction(function () use ($pcPortable) {
            if ($pcPortable->materiel) {
                $pcPortable->materiel->delete();
            } else {
                $pcPortable->delete();
            }
        });

        return redirect()->route('pc-portables.index')
                         ->with('success', __('messages.materiel_supprime', ['type' => 'PC portable']));
    }

    public function signalerPanne(PcPortable $pcPortable)
    {
        $pcPortable->materiel()->update(['etat' => 'en_panne']);

        return back()->with('success', __('messages.materiel_signale_panne', ['type' => 'PC portable']));
    }

    private function normaliserTailleEcran(?string $taille): ?string
    {
        if (! $taille) {
            return null;
        }

        return str_replace(',', '.', $taille) . ' pouces';
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

    private function donneesPcPortable(Request $request, bool $avecNumeroSerie): array
    {
        $donnees = [
            'adresse_mac' => $request->adresse_mac,
            'cpu'         => $request->cpu,
            'ram'         => $request->ram,
            'stockage'    => $request->stockage,
            'os'          => $request->os,
            'ecran'       => $this->normaliserTailleEcran($request->ecran),
        ];

        if ($avecNumeroSerie) {
            $donnees['numero_serie'] = $request->numero_serie;
        }

        if ($request->ram === 'autre') {
            $donnees['ram'] = $request->ram_autre;
        }

        if ($request->stockage === 'autre') {
            $donnees['stockage'] = $request->stockage_autre;
        }

        if ($request->os === 'autre') {
            $donnees['os'] = $request->os_autre;
        }

        return $donnees;
    }

    public function marquerRepare(PcPortable $pcPortable)
    {
        $pcPortable->materiel()->update(['etat' => 'disponible']);

        return back()->with('success', __('messages.materiel_marque_disponible', ['type' => 'PC portable']));
    }
}
