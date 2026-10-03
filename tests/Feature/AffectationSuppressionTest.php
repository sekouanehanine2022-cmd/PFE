<?php

namespace Tests\Feature;

use App\Models\Affectation;
use App\Models\Materiel;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffectationSuppressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_une_affectation_active_ne_peut_pas_etre_supprimee(): void
    {
        $admin = $this->creerPersonnel('admin');
        $personnel = $this->creerPersonnel('personnel');
        $materiel = $this->creerMateriel('affecte');
        $affectation = $this->creerAffectation($personnel->personnel, $materiel, 'active');

        $this->actingAs($admin)
            ->delete(route('affectations.destroy', $affectation))
            ->assertRedirect(route('affectations.index'))
            ->assertSessionHasErrors(['affectation']);

        $this->assertDatabaseHas('affectations', [
            'id' => $affectation->id,
            'statut' => 'active',
        ]);
        $this->assertDatabaseHas('materiels', [
            'id' => $materiel->id,
            'etat' => 'affecte',
        ]);
    }

    public function test_une_affectation_rendue_peut_etre_supprimee(): void
    {
        $admin = $this->creerPersonnel('admin');
        $personnel = $this->creerPersonnel('personnel');
        $materiel = $this->creerMateriel('disponible');
        $affectation = $this->creerAffectation($personnel->personnel, $materiel, 'rendu');

        $this->actingAs($admin)
            ->delete(route('affectations.destroy', $affectation))
            ->assertRedirect(route('affectations.index'))
            ->assertSessionHas('success', __('messages.affectation_supprimee'));

        $this->assertDatabaseMissing('affectations', ['id' => $affectation->id]);
        $this->assertDatabaseHas('materiels', [
            'id' => $materiel->id,
            'etat' => 'disponible',
        ]);
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

    private function creerMateriel(string $etat): Materiel
    {
        return Materiel::create([
            'type_materiel' => 'pc_portable',
            'nom' => 'PC de test',
            'marque' => 'Test',
            'etat' => $etat,
        ]);
    }

    private function creerAffectation(Personnel $personnel, Materiel $materiel, string $statut): Affectation
    {
        return Affectation::create([
            'personnel_id' => $personnel->id,
            'materiel_id' => $materiel->id,
            'date_debut' => now()->toDateString(),
            'date_retour' => $statut === 'rendu' ? now()->toDateString() : null,
            'statut' => $statut,
        ]);
    }
}
