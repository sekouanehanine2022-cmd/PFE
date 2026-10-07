<?php

namespace Tests\Feature\Api;

use App\Models\Affectation;
use App\Models\Emprunt;
use App\Models\Etudiant;
use App\Models\Materiel;
use App\Models\PcPortable;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonMaterielApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_personnel_recupere_uniquement_ses_affectations_actives(): void
    {
        $utilisateur = User::factory()->create();
        $personnel = $this->creerPersonnel($utilisateur);
        [$materiel, $pc] = $this->creerPc('PC-PERS-001', 'Latitude 5540');

        Affectation::create([
            'personnel_id' => $personnel->id,
            'materiel_id' => $materiel->id,
            'date_debut' => '2026-10-01',
            'statut' => 'active',
        ]);

        [$materielRendu] = $this->creerPc('PC-PERS-002', 'Latitude rendu');
        Affectation::create([
            'personnel_id' => $personnel->id,
            'materiel_id' => $materielRendu->id,
            'date_debut' => '2026-09-01',
            'date_retour' => '2026-09-30',
            'statut' => 'rendu',
        ]);

        $this->withToken($utilisateur->createToken('Telephone personnel')->plainTextToken)
            ->getJson('/api/mon-materiel')
            ->assertOk()
            ->assertJsonPath('type_utilisateur', 'personnel')
            ->assertJsonPath('total', 1)
            ->assertJsonPath('materiels.0.type', 'PC portable')
            ->assertJsonPath('materiels.0.nom', 'Latitude 5540')
            ->assertJsonPath('materiels.0.numero_serie', $pc->numero_serie)
            ->assertJsonMissing(['numero_serie' => 'PC-PERS-002']);
    }

    public function test_un_etudiant_recupere_uniquement_son_pc_emprunte_non_rendu(): void
    {
        $utilisateur = User::factory()->create();
        $etudiant = Etudiant::create([
            'user_id' => $utilisateur->id,
            'type' => 'etud_initial',
        ]);
        [, $pc] = $this->creerPc('PC-ETU-001', 'ThinkPad L15');

        Emprunt::create([
            'etudiant_id' => $etudiant->id,
            'pc_numero_serie' => $pc->numero_serie,
            'date_debut' => '2026-10-01',
            'date_fin_prevue' => '2026-10-20',
            'statut' => 'en_cours',
        ]);

        $this->withToken($utilisateur->createToken('Telephone etudiant')->plainTextToken)
            ->getJson('/api/mon-materiel')
            ->assertOk()
            ->assertJsonPath('type_utilisateur', 'etudiant')
            ->assertJsonPath('total', 1)
            ->assertJsonPath('materiels.0.origine', 'emprunt')
            ->assertJsonPath('materiels.0.nom', 'ThinkPad L15')
            ->assertJsonPath('materiels.0.numero_serie', 'PC-ETU-001');
    }

    public function test_la_route_exige_une_authentification(): void
    {
        $this->getJson('/api/mon-materiel')->assertUnauthorized();
    }

    public function test_un_administrateur_ne_peut_pas_consulter_la_route_mobile(): void
    {
        $utilisateur = User::factory()->create();
        $this->creerPersonnel($utilisateur, 'admin');

        $this->withToken($utilisateur->createToken('Telephone admin')->plainTextToken)
            ->getJson('/api/mon-materiel')
            ->assertForbidden();
    }

    private function creerPersonnel(User $utilisateur, string $role = 'personnel'): Personnel
    {
        return Personnel::create([
            'user_id' => $utilisateur->id,
            'service' => 'Support IT',
            'poste' => 'Technicien',
            'type_contrat' => 'cdi',
            'role' => $role,
        ]);
    }

    private function creerPc(string $numeroSerie, string $nom): array
    {
        $materiel = Materiel::create([
            'type_materiel' => 'pc_portable',
            'nom' => $nom,
            'marque' => 'Dell',
            'etat' => 'affecte',
            'emplacement' => 'Paris',
        ]);

        $pc = PcPortable::create([
            'numero_serie' => $numeroSerie,
            'materiel_id' => $materiel->id,
            'cpu' => 'Intel Core i5',
            'ram' => '16 Go DDR5',
            'stockage' => '512 Go SSD',
            'os' => 'Windows 11',
        ]);

        return [$materiel, $pc];
    }
}
