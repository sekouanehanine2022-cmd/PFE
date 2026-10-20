<?php

namespace Tests\Feature;

use App\Models\Materiel;
use App\Models\PcPortable;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportCsvTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_admin_peut_exporter_toutes_les_pages_csv(): void
    {
        $admin = $this->creerUtilisateur('admin');

        foreach ([
            'pc-portables.exporter',
            'mini-pc.exporter',
            'ecrans.exporter',
            'imprimantes.exporter',
            'cables.exporter',
            'claviers.exporter',
            'souris.exporter',
            'casques.exporter',
            'affectations.exporter',
            'emprunts.exporter',
            'tickets.exporter',
        ] as $route) {
            $this->actingAs($admin)
                ->get(route($route))
                ->assertOk()
                ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        }
    }

    public function test_export_pc_respecte_la_recherche_et_le_filtre(): void
    {
        $admin = $this->creerUtilisateur('admin');
        $this->creerPc('PC-EXPORT-001', 'Latitude Export', 'disponible');
        $this->creerPc('PC-MASQUE-002', 'ThinkPad Masque', 'en_panne');

        $reponse = $this->actingAs($admin)->get(route('pc-portables.exporter', [
            'search' => 'Export',
            'etat' => 'disponible',
        ]));

        $contenu = $reponse->streamedContent();

        $reponse->assertOk();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenu);
        $this->assertStringContainsString('PC-EXPORT-001', $contenu);
        $this->assertStringContainsString('Latitude Export', $contenu);
        $this->assertStringNotContainsString('PC-MASQUE-002', $contenu);
    }

    public function test_un_utilisateur_non_admin_ne_peut_pas_exporter(): void
    {
        $personnel = $this->creerUtilisateur('personnel');

        $this->actingAs($personnel)
            ->get(route('pc-portables.exporter'))
            ->assertForbidden();
    }

    private function creerUtilisateur(string $role): User
    {
        $utilisateur = User::factory()->create();

        Personnel::create([
            'user_id' => $utilisateur->id,
            'service' => 'Support IT',
            'poste' => 'Technicien',
            'type_contrat' => 'cdi',
            'role' => $role,
        ]);

        return $utilisateur;
    }

    private function creerPc(string $numeroSerie, string $nom, string $etat): PcPortable
    {
        $materiel = Materiel::create([
            'type_materiel' => 'pc_portable',
            'nom' => $nom,
            'marque' => 'Dell',
            'etat' => $etat,
            'emplacement' => 'Stock',
            'date_achat' => now()->toDateString(),
        ]);

        return PcPortable::create([
            'materiel_id' => $materiel->id,
            'numero_serie' => $numeroSerie,
            'adresse_mac' => null,
            'cpu' => 'Intel Core i5',
            'ram' => '16 Go DDR5',
            'stockage' => '512 Go SSD',
            'os' => 'Windows 11',
            'ecran' => '15.6',
        ]);
    }
}
