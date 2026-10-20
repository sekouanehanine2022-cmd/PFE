<?php

namespace Tests\Feature;

use App\Models\Affectation;
use App\Models\Emprunt;
use App\Models\Etudiant;
use App\Models\Materiel;
use App\Models\PcPortable;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonMaterielTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_utilisateur_connecte_peut_maintenir_sa_session_active(): void
    {
        $utilisateur = User::factory()->create();
        $this->creerPersonnel($utilisateur);

        $this->actingAs($utilisateur)
            ->getJson(route('session.keep-alive'))
            ->assertOk()
            ->assertJsonStructure(['csrf_token']);
    }

    public function test_un_visiteur_ne_peut_pas_maintenir_une_session(): void
    {
        $this->get(route('session.keep-alive'))
            ->assertRedirect(route('login'));
    }

    public function test_un_personnel_voit_ses_affectations_actives_en_cartes_et_les_rendues_dans_historique(): void
    {
        $utilisateur = User::factory()->create();
        $personnel = $this->creerPersonnel($utilisateur);
        $autrePersonnel = $this->creerPersonnel(User::factory()->create());

        [$materielActif] = $this->creerPc('WEB-PERS-ACTIF', 'Latitude actif');
        [$materielRendu] = $this->creerPc('WEB-PERS-RENDU', 'Latitude rendu');
        [$materielAutre] = $this->creerPc('WEB-PERS-AUTRE', 'PC autre personne');

        Affectation::create([
            'personnel_id' => $personnel->user_id,
            'materiel_id' => $materielActif->id,
            'date_debut' => '2026-10-01',
            'statut' => 'active',
        ]);
        Affectation::create([
            'personnel_id' => $personnel->user_id,
            'materiel_id' => $materielRendu->id,
            'date_debut' => '2026-09-01',
            'date_retour' => '2026-09-30',
            'statut' => 'rendu',
        ]);
        Affectation::create([
            'personnel_id' => $autrePersonnel->user_id,
            'materiel_id' => $materielAutre->id,
            'date_debut' => '2026-10-01',
            'statut' => 'active',
        ]);

        $this->actingAs($utilisateur)
            ->get(route('mon-materiel.index'))
            ->assertOk()
            ->assertViewHas('materielsActifs', fn ($materiels) => $materiels->pluck('numero_serie')->all() === ['WEB-PERS-ACTIF'])
            ->assertViewHas('historique', fn ($materiels) => $materiels->pluck('numero_serie')->all() === ['WEB-PERS-RENDU'])
            ->assertSee('Latitude actif')
            ->assertSee('Latitude rendu')
            ->assertDontSee('PC autre personne');
    }

    public function test_un_etudiant_voit_son_emprunt_actif_en_carte_et_son_ancien_emprunt_dans_historique(): void
    {
        $utilisateur = User::factory()->create();
        $etudiant = Etudiant::create([
            'user_id' => $utilisateur->id,
            'type' => 'alt_externe',
        ]);

        [, $pcActif] = $this->creerPc('WEB-ETU-ACTIF', 'ThinkPad actif');
        [, $pcRendu] = $this->creerPc('WEB-ETU-RENDU', 'ThinkPad rendu');

        Emprunt::create([
            'etudiant_id' => $etudiant->user_id,
            'materiel_id' => $pcActif->materiel_id,
            'date_debut' => '2026-10-01',
            'date_fin_prevue' => '2026-10-20',
            'statut' => 'en_cours',
        ]);
        Emprunt::create([
            'etudiant_id' => $etudiant->user_id,
            'materiel_id' => $pcRendu->materiel_id,
            'date_debut' => '2026-09-01',
            'date_fin_prevue' => '2026-09-20',
            'date_retour' => '2026-09-18',
            'statut' => 'rendu',
        ]);

        $this->actingAs($utilisateur)
            ->get(route('mon-materiel.index'))
            ->assertOk()
            ->assertViewHas('materielsActifs', fn ($materiels) => $materiels->pluck('numero_serie')->all() === ['WEB-ETU-ACTIF'])
            ->assertViewHas('historique', fn ($materiels) => $materiels->pluck('numero_serie')->all() === ['WEB-ETU-RENDU'])
            ->assertSee('ThinkPad actif')
            ->assertSee('ThinkPad rendu')
            ->assertSee('Historique');
    }

    private function creerPersonnel(User $utilisateur): Personnel
    {
        return Personnel::create([
            'user_id' => $utilisateur->id,
            'service' => 'Support IT',
            'poste' => 'Technicien',
            'type_contrat' => 'cdi',
            'role' => 'personnel',
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
            'materiel_id' => $materiel->id,
            'numero_serie' => $numeroSerie,
            'cpu' => 'Intel Core i5',
            'ram' => '16 Go',
            'stockage' => '512 Go SSD',
            'os' => 'Windows 11',
        ]);

        return [$materiel, $pc];
    }
}
