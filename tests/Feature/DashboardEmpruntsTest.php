<?php

namespace Tests\Feature;

use App\Models\Emprunt;
use App\Models\Etudiant;
use App\Models\Materiel;
use App\Models\PcPortable;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardEmpruntsTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_dashboard_compte_les_retards_selon_la_date_comme_la_page_emprunts(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-14 12:00:00'));
        $admin = User::factory()->create(['mot_de_passe_change' => true]);
        Personnel::create(['user_id' => $admin->id, 'role' => 'admin', 'type_contrat' => 'cdi']);
        $etudiant = User::factory()->create();
        Etudiant::create(['user_id' => $etudiant->id, 'type' => 'alt_externe']);
        $materiel = Materiel::create(['nom' => 'Dell', 'marque' => 'Dell', 'type_materiel' => 'pc_portable', 'etat' => 'disponible']);
        PcPortable::create(['materiel_id' => $materiel->id, 'numero_serie' => 'DASH-TEST', 'cpu' => 'i5', 'ram' => '16 Go', 'stockage' => '512 Go', 'os' => 'Windows 11']);

        foreach ([['en_cours', -1], ['en_retard', -2], ['rendu', -3], ['en_cours', 0], ['en_cours', 1], ['en_retard', 2]] as [$statut, $jours]) {
            Emprunt::create([
                'etudiant_id' => $etudiant->id,
                'materiel_id' => $materiel->id,
                'date_debut' => today()->subDays(10),
                'date_fin_prevue' => today()->addDays($jours),
                'statut' => $statut,
                'date_retour' => $statut === 'rendu' ? today() : null,
            ]);
        }

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()->assertViewHas('empruntsEnRetard', 2);
        $this->get(route('emprunts.index'))
            ->assertOk()->assertViewHas('enRetard', 2);
    }
}
