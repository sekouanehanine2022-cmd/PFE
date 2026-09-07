<?php

namespace App\Http\Controllers;

use App\Models\Emprunt;
use Illuminate\Http\Request;

class EmpruntController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->get('search', '');
        $statut    = $request->get('statut', '');

        $emprunts = Emprunt::with([
                'etudiant.user',
                'materiel'
            ])
            ->when($recherche, function($query) use ($recherche) {
                $query->whereHas('etudiant.user', function($q) use ($recherche) {
                    $q->where('name', 'like', '%'.$recherche.'%');
                });
            })
            ->when($statut, function($query) use ($statut) {
                $query->where('statut', $statut);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $total          = Emprunt::count();
        $enCours        = Emprunt::where('statut', 'en_cours')->count();
        $echeanceProche = Emprunt::where('statut', 'echeance_proche')->count();
        $enRetard       = Emprunt::where('statut', 'en_retard')->count();
        $rendusCeMois   = Emprunt::where('statut', 'rendu')
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
            return back()->withErrors(['materiel_type' => 'Type de matériel invalide.']);
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

        return redirect()->route('emprunts.index')->with('success', 'Emprunt créé avec succès.');
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