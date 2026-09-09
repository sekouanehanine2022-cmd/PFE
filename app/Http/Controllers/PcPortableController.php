<?php

namespace App\Http\Controllers;

use App\Models\PcPortable;
use App\Services\AffectationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PcPortableController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->get('search', '');
        $etat      = $request->get('etat', '');

        $pcPortables = PcPortable::when($recherche, function($query) use ($recherche) {
                $query->where('nom', 'like', '%'.$recherche.'%')
                      ->orWhere('marque', 'like', '%'.$recherche.'%')
                      ->orWhere('numero_serie', 'like', '%'.$recherche.'%');
            })
            ->when($etat, function($query) use ($etat) {
                $query->where('etat', $etat);
            })
            ->with(['affectations.personnel.user', 'emprunts.etudiant.user'])
            ->get();

        $total       = PcPortable::count();
        $disponibles = PcPortable::where('etat', 'disponible')->count();
        $affectes    = PcPortable::where('etat', 'affecte')->count();
        $enPanne     = PcPortable::where('etat', 'en_panne')->count();

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
            'reference'    => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/', 'unique:pc_portables,reference'],
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

        $donnees = $this->donneesPcPortable($request);
        $donnees['ecran'] = $this->normaliserTailleEcran($request->ecran);

        $pc = PcPortable::create($donnees);

        if ($personnel) {
            \App\Models\Affectation::create([
                'personnel_id'  => $personnel->id,
                'materiel_type' => PcPortable::class,
                'materiel_id'   => $pc->id,
                'date_debut'    => now(),
                'statut'        => 'active',
                'ticket_id'     => null,
            ]);
        } elseif ($etudiant) {
            \App\Models\Emprunt::create([
                'etudiant_id'    => $etudiant->id,
                'materiel_type'  => PcPortable::class,
                'materiel_id'    => $pc->id,
                'date_debut'     => now(),
                'date_fin_prevue'=> now()->addMonths(3),
                'statut'         => 'en_cours',
                'ticket_id'      => null,
            ]);
        }

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

        $donnees = $this->donneesPcPortable($request);
        $donnees['ecran'] = $this->normaliserTailleEcran($request->ecran);

        $pcPortable->update($donnees);

        return redirect()->route('pc-portables.index')
                         ->with('success', __('messages.materiel_modifie', ['type' => 'PC portable']));
    }

    public function destroy(PcPortable $pcPortable)
    {
        $pcPortable->delete();

        return redirect()->route('pc-portables.index')
                         ->with('success', __('messages.materiel_supprime', ['type' => 'PC portable']));
    }

    public function signalerPanne(PcPortable $pcPortable)
    {
        $pcPortable->update(['etat' => 'en_panne']);

        return back()->with('success', __('messages.materiel_signale_panne', ['type' => 'PC portable']));
    }

    private function normaliserTailleEcran(?string $taille): ?string
    {
        if (! $taille) {
            return null;
        }

        return str_replace(',', '.', $taille) . ' pouces';
    }

    private function donneesPcPortable(Request $request): array
    {
        $donnees = $request->except(['ram_autre', 'stockage_autre', 'os_autre']);

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
        $pcPortable->update(['etat' => 'disponible']);

        return back()->with('success', __('messages.materiel_marque_disponible', ['type' => 'PC portable']));
    }
}
