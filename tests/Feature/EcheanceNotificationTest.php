<?php

namespace Tests\Feature;

use App\Models\Affectation;
use App\Models\Emprunt;
use App\Models\Etudiant;
use App\Models\Materiel;
use App\Models\Notification;
use App\Models\PcPortable;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class EcheanceNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_la_commande_notifie_tous_les_admins_sept_jours_avant_sans_doublon(): void
    {
        Carbon::setTestNow('2026-09-25 08:00:00');

        $adminUn = $this->creerPersonnel('admin');
        $adminDeux = $this->creerPersonnel('admin');
        $collaborateur = $this->creerPersonnel('personnel');
        $etudiant = $this->creerEtudiant();
        [$materielAffecte] = $this->creerPc('PC-AFFECTE', 'affecte');
        [, $pcEmprunte] = $this->creerPc('PC-EMPRUNTE', 'emprunte');

        $affectation = Affectation::create([
            'personnel_id' => $collaborateur->personnel->id,
            'materiel_id' => $materielAffecte->id,
            'date_debut' => today(),
            'date_fin' => today()->addDays(7),
            'statut' => 'active',
        ]);

        $emprunt = Emprunt::create([
            'etudiant_id' => $etudiant->etudiant->id,
            'pc_numero_serie' => $pcEmprunte->numero_serie,
            'date_debut' => today(),
            'date_fin_prevue' => today()->addDays(7),
            'statut' => 'en_cours',
        ]);

        Artisan::call('notifications:verifier-echeances');
        Artisan::call('notifications:verifier-echeances');

        foreach ([$adminUn, $adminDeux] as $admin) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $admin->id,
                'affectation_id' => $affectation->id,
                'type' => 'echeance_affectation_7_jours',
                'read_at' => null,
            ]);
            $this->assertDatabaseHas('notifications', [
                'user_id' => $admin->id,
                'emprunt_id' => $emprunt->id,
                'type' => 'echeance_emprunt_7_jours',
                'read_at' => null,
            ]);
        }

        $this->assertDatabaseCount('notifications', 4);

        $notificationAffectation = Notification::query()
            ->where('user_id', $adminUn->id)
            ->where('affectation_id', $affectation->id)
            ->firstOrFail();

        $this->actingAs($adminUn)
            ->patch(route('notifications.lire', $notificationAffectation))
            ->assertRedirect(route('affectations.index', ['affectation' => $affectation->id]));

        $notificationEmprunt = Notification::query()
            ->where('user_id', $adminDeux->id)
            ->where('emprunt_id', $emprunt->id)
            ->firstOrFail();

        $this->actingAs($adminDeux)
            ->patch(route('notifications.lire', $notificationEmprunt))
            ->assertRedirect(route('emprunts.index', ['emprunt' => $emprunt->id]));
    }

    public function test_la_commande_ignore_les_autres_dates_les_retours_et_les_affectations_sans_fin(): void
    {
        Carbon::setTestNow('2026-09-25 08:00:00');

        $this->creerPersonnel('admin');
        $collaborateur = $this->creerPersonnel('personnel');
        $etudiant = $this->creerEtudiant();
        [$materiel, $pc] = $this->creerPc('PC-SANS-ALERTE', 'disponible');

        Affectation::create([
            'personnel_id' => $collaborateur->personnel->id,
            'materiel_id' => $materiel->id,
            'date_debut' => today(),
            'date_fin' => today()->addDays(8),
            'statut' => 'active',
        ]);
        Affectation::create([
            'personnel_id' => $collaborateur->personnel->id,
            'materiel_id' => $materiel->id,
            'date_debut' => today(),
            'date_fin' => today()->addDays(7),
            'date_retour' => today(),
            'statut' => 'rendu',
        ]);
        Affectation::create([
            'personnel_id' => $collaborateur->personnel->id,
            'materiel_id' => $materiel->id,
            'date_debut' => today(),
            'date_fin' => null,
            'statut' => 'active',
        ]);
        Emprunt::create([
            'etudiant_id' => $etudiant->etudiant->id,
            'pc_numero_serie' => $pc->numero_serie,
            'date_debut' => today(),
            'date_fin_prevue' => today()->addDays(7),
            'date_retour' => today(),
            'statut' => 'rendu',
        ]);

        Artisan::call('notifications:verifier-echeances');

        $this->assertDatabaseCount('notifications', 0);
    }

    private function creerPersonnel(string $role): User
    {
        $utilisateur = User::factory()->create(['mot_de_passe_change' => true]);

        Personnel::create([
            'user_id' => $utilisateur->id,
            'type_contrat' => 'cdi',
            'role' => $role,
        ]);

        return $utilisateur->load('personnel');
    }

    private function creerEtudiant(): User
    {
        $utilisateur = User::factory()->create(['mot_de_passe_change' => true]);

        Etudiant::create([
            'user_id' => $utilisateur->id,
            'type' => 'etud_initial',
        ]);

        return $utilisateur->load('etudiant');
    }

    private function creerPc(string $numeroSerie, string $etat): array
    {
        $materiel = Materiel::create([
            'type_materiel' => 'pc_portable',
            'nom' => 'Dell Latitude',
            'marque' => 'Dell',
            'etat' => $etat,
        ]);

        $pc = PcPortable::create([
            'numero_serie' => $numeroSerie,
            'materiel_id' => $materiel->id,
            'cpu' => 'Intel i5',
            'ram' => '16 Go DDR5',
            'stockage' => '512 Go SSD',
            'os' => 'Windows 11',
        ]);

        return [$materiel, $pc];
    }
}
