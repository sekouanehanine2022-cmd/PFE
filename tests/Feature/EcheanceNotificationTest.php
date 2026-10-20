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

    public function test_la_commande_notifie_tous_les_admins_des_le_lendemain_sans_doublon(): void
    {
        Carbon::setTestNow('2026-09-25 08:00:00');

        $adminUn = $this->creerPersonnel('admin');
        $adminDeux = $this->creerPersonnel('admin');
        $collaborateur = $this->creerPersonnel('personnel');
        $etudiant = $this->creerEtudiant();
        [$materielAffecte] = $this->creerPc('PC-AFFECTE', 'affecte');
        [, $pcEmprunte] = $this->creerPc('PC-EMPRUNTE', 'emprunte');

        $affectation = Affectation::create([
            'personnel_id' => $collaborateur->personnel->user_id,
            'materiel_id' => $materielAffecte->id,
            'date_debut' => today()->subDays(10),
            'date_fin' => today()->subDay(),
            'statut' => 'active',
        ]);

        $emprunt = Emprunt::create([
            'etudiant_id' => $etudiant->etudiant->user_id,
            'materiel_id' => $pcEmprunte->materiel_id,
            'date_debut' => today()->subDays(10),
            'date_fin_prevue' => today()->subDay(),
            'statut' => 'en_cours',
        ]);

        Artisan::call('notifications:verifier-echeances');
        Artisan::call('notifications:verifier-echeances');

        foreach ([$adminUn, $adminDeux] as $admin) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $admin->id,
                'affectation_id' => $affectation->id,
                'type' => 'retard_affectation',
                'date_echeance' => today()->subDay()->toDateString(),
                'read_at' => null,
            ]);
            $this->assertDatabaseHas('notifications', [
                'user_id' => $admin->id,
                'emprunt_id' => $emprunt->id,
                'type' => 'retard_emprunt',
                'date_echeance' => today()->subDay()->toDateString(),
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

        Carbon::setTestNow('2026-09-26 08:00:00');
        Artisan::call('notifications:verifier-echeances');
        $this->assertDatabaseCount('notifications', 4);
        $this->assertNotNull($notificationAffectation->fresh()->read_at);
        $this->assertNotNull($notificationEmprunt->fresh()->read_at);
    }

    public function test_la_commande_ignore_les_dates_non_depassees_les_retours_et_les_affectations_sans_fin(): void
    {
        Carbon::setTestNow('2026-09-25 08:00:00');

        $this->creerPersonnel('admin');
        $collaborateur = $this->creerPersonnel('personnel');
        $etudiant = $this->creerEtudiant();
        [$materiel, $pc] = $this->creerPc('PC-SANS-ALERTE', 'disponible');

        Affectation::create([
            'personnel_id' => $collaborateur->personnel->user_id,
            'materiel_id' => $materiel->id,
            'date_debut' => today(),
            'date_fin' => today()->addDays(8),
            'statut' => 'active',
        ]);
        Affectation::create([
            'personnel_id' => $collaborateur->personnel->user_id,
            'materiel_id' => $materiel->id,
            'date_debut' => today(),
            'date_fin' => today()->subDay(),
            'date_retour' => today(),
            'statut' => 'rendu',
        ]);
        Affectation::create([
            'personnel_id' => $collaborateur->personnel->user_id,
            'materiel_id' => $materiel->id,
            'date_debut' => today(),
            'date_fin' => null,
            'statut' => 'active',
        ]);
        Emprunt::create([
            'etudiant_id' => $etudiant->etudiant->user_id,
            'materiel_id' => $pc->materiel_id,
            'date_debut' => today(),
            'date_fin_prevue' => today()->subDay(),
            'date_retour' => today(),
            'statut' => 'rendu',
        ]);

        foreach ([0, 7] as $jours) {
            Affectation::create([
                'personnel_id' => $collaborateur->id, 'materiel_id' => $materiel->id,
                'date_debut' => today()->subDays(10), 'date_fin' => today()->addDays($jours), 'statut' => 'active',
            ]);
            Emprunt::create([
                'etudiant_id' => $etudiant->id, 'materiel_id' => $pc->materiel_id,
                'date_debut' => today()->subDays(10), 'date_fin_prevue' => today()->addDays($jours), 'statut' => 'en_cours',
            ]);
        }

        Artisan::call('notifications:verifier-echeances');

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_retard_ancien_prolongation_et_retour_sont_pris_en_compte(): void
    {
        Carbon::setTestNow('2026-09-25 08:00:00');
        $this->creerPersonnel('admin');
        $collaborateur = $this->creerPersonnel('personnel');
        $etudiant = $this->creerEtudiant();
        [$materiel, $pc] = $this->creerPc('PC-RETARD-ANCIEN', 'disponible');

        $affectation = Affectation::create([
            'personnel_id' => $collaborateur->id, 'materiel_id' => $materiel->id,
            'date_debut' => today()->subDays(20), 'date_fin' => today()->subDays(5), 'statut' => 'active',
        ]);
        $emprunt = Emprunt::create([
            'etudiant_id' => $etudiant->id, 'materiel_id' => $pc->materiel_id,
            'date_debut' => today()->subDays(20), 'date_fin_prevue' => today()->subDays(5), 'statut' => 'en_retard',
        ]);

        Artisan::call('notifications:verifier-echeances');
        $this->assertDatabaseCount('notifications', 2);
        $message = Notification::where('affectation_id', $affectation->id)->firstOrFail()->message;
        $this->assertStringContainsString($collaborateur->name, $message);
        $this->assertStringContainsString('PC-RETARD-ANCIEN', $message);
        $this->assertStringContainsString('20/09/2026', $message);

        $affectation->update(['date_fin' => '2026-09-28']);
        $emprunt->update(['date_fin_prevue' => '2026-09-28']);
        Artisan::call('notifications:verifier-echeances');
        $this->assertDatabaseCount('notifications', 2);

        Carbon::setTestNow('2026-09-29 08:00:00');
        Artisan::call('notifications:verifier-echeances');
        Artisan::call('notifications:verifier-echeances');
        $this->assertDatabaseCount('notifications', 4);

        $affectation->update(['date_fin' => '2026-09-27', 'date_retour' => today()]);
        $emprunt->update(['date_fin_prevue' => '2026-09-27', 'date_retour' => today()]);
        Artisan::call('notifications:verifier-echeances');
        $this->assertDatabaseCount('notifications', 4);
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
