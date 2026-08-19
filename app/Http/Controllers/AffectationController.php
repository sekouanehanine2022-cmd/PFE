<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use Illuminate\Http\Request;

class AffectationController extends Controller
{
    public function index(Request $request)
    {
        $recherche = $request->get('search', '');
        $statut    = $request->get('statut', '');

        $affectations = Affectation::with([
                'personnel.user',
                'materiel'
            ])
            ->when($recherche, function($query) use ($recherche) {
                $query->whereHas('personnel.user', function($q) use ($recherche) {
                    $q->where('name', 'like', '%'.$recherche.'%');
                });
            })
            ->when($statut, function($query) use ($statut) {
                $query->where('statut', $statut);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $total    = Affectation::count();
        $actives  = Affectation::where('statut', 'active')->count();
        $expirees = Affectation::where('statut', 'expiree')->count();
        $cloturees= Affectation::where('statut', 'cloturee')->count();

        return view('activite.affectations', compact(
            'affectations', 'total', 'actives', 'expirees', 'cloturees'
        ));
    }
}