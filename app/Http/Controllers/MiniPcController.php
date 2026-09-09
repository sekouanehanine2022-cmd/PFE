<?php

namespace App\Http\Controllers;

use App\Models\MiniPc;
use App\Services\AffectationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MiniPcController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->get('search', '');
        $etat      = $request->get('etat', '');

        $miniPcs = MiniPc::when($recherche, function($query) use ($recherche) {
                $query->where('nom', 'like', '%'.$recherche.'%')
                      ->orWhere('marque', 'like', '%'.$recherche.'%')
                      ->orWhere('numero_serie', 'like', '%'.$recherche.'%');
            })
            ->when($etat, function($query) use ($etat) {
                $query->where('etat', $etat);
            })
            ->with(['affectations.personnel.user', 'emprunts.etudiant.user'])
            ->get();

        $total       = MiniPc::count();
        $disponibles = MiniPc::where('etat', 'disponible')->count();
        $affectes    = MiniPc::where('etat', 'affecte')->count();
        $enPanne     = MiniPc::where('etat', 'en_panne')->count();

        return view('materiel.mini-pc', compact(
            'miniPcs', 'total', 'disponibles', 'affectes', 'enPanne'
        ));
    }

    public function store(Request $request, AffectationService $affectationService)
    {
        $request->validate([
            'reference'    => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/', 'unique:mini_pcs,reference'],
            'nom'          => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9 ._-]+$/'],
            'marque'       => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9 ._-]+$/'],
            'numero_serie' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:mini_pcs,numero_serie'],
            'adresse_mac'  => ['nullable', 'string', 'max:17', 'regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/'],
            'cpu'          => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9 ._+()\/-]+$/'],
            'ram'          => ['required', 'string', Rule::in(['4 Go DDR3', '8 Go DDR4', '16 Go DDR4', '16 Go DDR5', '32 Go DDR5', '64 Go DDR5', 'autre'])],
            'ram_autre'    => ['nullable', 'required_if:ram,autre', 'string', 'max:30', 'regex:/^[0-9]{1,3} Go DDR[3-5]$/'],
            'stockage'     => ['required', 'string', Rule::in(['128 Go SSD', '256 Go SSD', '512 Go SSD', '1 To SSD', '1 To HDD', '2 To SSD', 'autre'])],
            'stockage_autre' => ['nullable', 'required_if:stockage,autre', 'string', 'max:30', 'regex:/^[0-9]{1,4} (Go|To) (SSD|HDD|NVMe)$/'],
            'os'           => ['required', 'string', 'max:50', Rule::in(['Windows 10', 'Windows 11', 'macOS', 'Linux', 'autre'])],
            'os_autre'     => ['nullable', 'required_if:os,autre', 'string', 'max:50', 'regex:/^[A-Za-z0-9 ._+()-]+$/'],
            'etat'         => ['nullable', Rule::in(['disponible', 'affecte', 'emprunte', 'en_panne'])],
            'emplacement'  => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9 ._()\/-]+$/'],
            'date_achat'   => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $personnel = null;
        $etudiant  = null;

        if ($request->etat === 'affecte') {
            if (! $request->filled('a_qui_id')) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.collaborateur_obligatoire_affectation', ['type' => 'Mini PC'])])
                    ->withInput();
            }

            $personnel = \App\Models\Personnel::where('user_id', $request->a_qui_id)->first();

            if (! $personnel) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.collaborateur_introuvable')])
                    ->withInput();
            }

            if ($affectationService->existeAffectationActivePourType($personnel->id, MiniPc::class)) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.collaborateur_deja_materiel_affecte', ['type' => 'Mini PC'])])
                    ->withInput();
            }
        } elseif ($request->etat === 'emprunte') {
            if (! $request->filled('a_qui_id')) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.etudiant_obligatoire_emprunt', ['type' => 'Mini PC'])])
                    ->withInput();
            }

            $etudiant = \App\Models\Etudiant::where('user_id', $request->a_qui_id)->first();

            if (! $etudiant) {
                return back()
                    ->withErrors(['a_qui_id' => __('messages.etudiant_introuvable')])
                    ->withInput();
            }
        }

        $miniPc = MiniPc::create($this->donneesMiniPc($request));

        if ($personnel) {
            \App\Models\Affectation::create([
                'personnel_id'  => $personnel->id,
                'materiel_type' => MiniPc::class,
                'materiel_id'   => $miniPc->id,
                'date_debut'    => now(),
                'statut'        => 'active',
                'ticket_id'     => null,
            ]);
        } elseif ($etudiant) {
            \App\Models\Emprunt::create([
                'etudiant_id'    => $etudiant->id,
                'materiel_type'  => MiniPc::class,
                'materiel_id'    => $miniPc->id,
                'date_debut'     => now(),
                'date_fin_prevue'=> now()->addMonths(3),
                'statut'         => 'en_cours',
                'ticket_id'      => null,
            ]);
        }

        return redirect()->route('mini-pc.index')
                         ->with('success', __('messages.materiel_ajoute', ['type' => 'Mini PC']));
    }

    public function update(Request $request, MiniPc $miniPc)
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
            'emplacement' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9 ._()\/-]+$/'],
            'date_achat'  => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $miniPc->update($this->donneesMiniPc($request));

        return redirect()->route('mini-pc.index')
                         ->with('success', __('messages.materiel_modifie', ['type' => 'Mini PC']));
    }

    public function destroy(MiniPc $miniPc)
    {
        $miniPc->delete();

        return redirect()->route('mini-pc.index')
                         ->with('success', __('messages.materiel_supprime', ['type' => 'Mini PC']));
    }

    public function signalerPanne(MiniPc $miniPc)
    {
        $miniPc->update(['etat' => 'en_panne']);

        return back()->with('success', __('messages.materiel_signale_panne', ['type' => 'Mini PC']));
    }

    private function donneesMiniPc(Request $request): array
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

    public function marquerRepare(MiniPc $miniPc)
    {
        $miniPc->update(['etat' => 'disponible']);

        return back()->with('success', __('messages.materiel_marque_disponible', ['type' => 'Mini PC']));
    }
}
