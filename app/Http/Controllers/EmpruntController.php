<?php

namespace App\Http\Controllers;

use App\Models\Emprunt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmpruntController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->get('search', '');
        $statut    = $request->get('statut', '');
        $aujourdhui = now()->startOfDay();
        $dansSeptJours = now()->copy()->addDays(7)->endOfDay();

        $emprunts = Emprunt::with([
                'etudiant.user',
                'materiel'
            ])
            ->when($recherche, function($query) use ($recherche) {
                $query->whereHas('etudiant.user', function($q) use ($recherche) {
                    $q->where('name', 'like', '%'.$recherche.'%');
                });
            })
            ->when($statut, function($query) use ($statut, $aujourdhui, $dansSeptJours) {
                if ($statut === 'echeance_proche') {
                    $query->where('statut', '!=', 'rendu')
                        ->whereBetween('date_fin_prevue', [$aujourdhui, $dansSeptJours]);
                } elseif ($statut === 'en_retard') {
                    $query->where('statut', '!=', 'rendu')
                        ->whereDate('date_fin_prevue', '<', $aujourdhui);
                } elseif ($statut === 'en_cours') {
                    $query->where('statut', '!=', 'rendu')
                        ->whereDate('date_fin_prevue', '>', $dansSeptJours);
                } elseif ($statut === 'rendu') {
                    $query->where('statut', 'rendu');
                }
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $total = Emprunt::count();
        $enCours = Emprunt::where('statut', '!=', 'rendu')->count();
        $echeanceProche = Emprunt::where('statut', '!=', 'rendu')
            ->whereBetween('date_fin_prevue', [$aujourdhui, $dansSeptJours])
            ->count();
        $enRetard = Emprunt::where('statut', '!=', 'rendu')
            ->whereDate('date_fin_prevue', '<', $aujourdhui)
            ->count();
        $rendusCeMois = Emprunt::where('statut', 'rendu')
            ->whereMonth('date_retour', now()->month)
            ->whereYear('date_retour', now()->year)
            ->count();

        return view('activite.emprunts', compact(
            'emprunts', 'total', 'enCours', 'echeanceProche', 'enRetard', 'rendusCeMois'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'etudiant_id'      => 'required|exists:etudiants,id',
            'materiel_type'    => 'required|string',
            'materiel_id'      => 'required|integer',
            'date_debut'       => 'required|date',
            'date_fin_prevue'  => 'required|date|after_or_equal:date_debut',
        ]);

        // Correspondance entre le type choisi dans le select et le vrai model
        $typesMateriel = [
            'pc-portable' => \App\Models\PcPortable::class,
            'mini-pc'     => \App\Models\MiniPc::class,
            'ecran'       => \App\Models\Ecran::class,
            'imprimante'  => \App\Models\Imprimante::class,
            'clavier'     => \App\Models\Peripherique::class,
            'souris'      => \App\Models\Peripherique::class,
            'casque'      => \App\Models\Peripherique::class,
        ];

        $classeMateriel = $typesMateriel[$request->materiel_type] ?? null;

        if (! $classeMateriel) {
            return back()->withErrors(['materiel_type' => __('messages.materiel_type_invalide')]);
        }

        Emprunt::create([
            'etudiant_id'     => $request->etudiant_id,
            'materiel_type'   => $classeMateriel,
            'materiel_id'     => $request->materiel_id,
            'date_debut'      => $request->date_debut,
            'date_fin_prevue' => $request->date_fin_prevue,
            'statut'          => 'en_cours',
        ]);

        // On marque le matériel comme emprunté
        $materiel = $classeMateriel::find($request->materiel_id);
        if ($materiel) {
            $materiel->etat = 'emprunte';
            $materiel->save();
        }

        return redirect()->route('emprunts.index')->with('success', __('messages.emprunt_cree'));
    }

    public function validerRetour(Emprunt $emprunt)
    {
        if ($emprunt->statut === 'rendu') {
            return redirect()->route('emprunts.index')->with('info', __('messages.emprunt_deja_rendu'));
        }

        DB::transaction(function () use ($emprunt) {
            $emprunt->update([
                'date_retour' => now(),
                'statut' => 'rendu',
            ]);

            $materiel = $emprunt->materiel;
            if ($materiel) {
                $materiel->etat = 'disponible';
                $materiel->save();
            }
        });

        return redirect()->route('emprunts.index')->with('success', __('messages.emprunt_retour_valide'));
    }

    public function prolonger(Request $request, Emprunt $emprunt)
    {
        if ($emprunt->statut === 'rendu') {
            return redirect()->route('emprunts.index')->with('info', __('messages.emprunt_deja_rendu_prolongation'));
        }

        $request->validate([
            'date_fin_prevue' => 'required|date',
        ]);

        $ancienneDate = $emprunt->date_fin_prevue
            ? Carbon::parse($emprunt->date_fin_prevue)->startOfDay()
            : null;
        $nouvelleDate = Carbon::parse($request->date_fin_prevue)->startOfDay();

        if ($ancienneDate && $nouvelleDate->lessThanOrEqualTo($ancienneDate)) {
            return back()
                ->withErrors(['date_fin_prevue' => __('messages.emprunt_date_prolongation_invalide')])
                ->withInput();
        }

        $emprunt->update([
            'date_fin_prevue' => $nouvelleDate,
            'statut' => 'en_cours',
        ]);

        return redirect()->route('emprunts.index')->with('success', __('messages.emprunt_prolonge'));
    }

    public function destroy(Emprunt $emprunt)
    {
        DB::transaction(function () use ($emprunt) {
            if ($emprunt->statut !== 'rendu') {
                $materiel = $emprunt->materiel;
                if ($materiel) {
                    $materiel->etat = 'disponible';
                    $materiel->save();
                }
            }

            $emprunt->delete();
        });

        return redirect()->route('emprunts.index')->with('success', __('messages.emprunt_supprime'));
    }

    public function materielDisponible($type)
    {
        $typesMateriel = [
            'pc-portable' => \App\Models\PcPortable::class,
            'mini-pc'     => \App\Models\MiniPc::class,
            'ecran'       => \App\Models\Ecran::class,
            'imprimante'  => \App\Models\Imprimante::class,
            'clavier'     => \App\Models\Peripherique::class,
            'souris'      => \App\Models\Peripherique::class,
            'casque'      => \App\Models\Peripherique::class,
        ];

        $classeMateriel = $typesMateriel[$type] ?? null;

        if (! $classeMateriel) {
            return response()->json([]);
        }

        $query = $classeMateriel::where('etat', 'disponible');

        // Pour les périphériques, on filtre en plus par sous_type
        if (in_array($type, ['clavier', 'souris', 'casque'])) {
            $query->where('sous_type', $type);
        }

        $materiels = $query->get(['id', 'nom']);

        return response()->json($materiels);
    }
}
