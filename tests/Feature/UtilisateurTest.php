<?php

namespace Tests\Feature;

use App\Models\Etudiant;
use App\Models\Personnel;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UtilisateurTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_admin_affiche_la_page_des_comptes_avec_les_bonnes_statistiques(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Test',
            'mot_de_passe_change' => true,
        ]);
        $this->creerPersonnel($admin, 'admin');

        $personnel = User::factory()->create([
            'name' => 'Personnel Test',
            'mot_de_passe_change' => true,
        ]);
        $this->creerPersonnel($personnel);

        $etudiant = User::factory()->unverified()->create([
            'name' => 'Etudiant Test',
            'mot_de_passe_change' => true,
        ]);
        Etudiant::create([
            'user_id' => $etudiant->id,
            'type' => 'etud_initial',
            'promotion' => 'CDA',
            'etablissement' => 'IEG',
        ]);

        $this->actingAs($admin)
            ->get(route('utilisateurs.index'))
            ->assertOk()
            ->assertViewHas('total', 3)
            ->assertViewHas('totalPersonnels', 2)
            ->assertViewHas('totalEtudiants', 1)
            ->assertViewHas('enAttenteVerification', 1)
            ->assertSee('Admin Test')
            ->assertSee('Personnel Test')
            ->assertSee('Etudiant Test')
            ->assertSee('Affichage 3 sur 3 compte(s)')
            ->assertSee('Acces')
            ->assertSee(route('utilisateurs.mot-de-passe', $personnel))
            ->assertSee(route('utilisateurs.blocage', $personnel))
            ->assertSee(route('utilisateurs.destroy', $personnel));
    }

    public function test_la_recherche_filtre_le_tableau_sans_modifier_les_statistiques_globales(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Test',
            'mot_de_passe_change' => true,
        ]);
        $this->creerPersonnel($admin, 'admin');

        $personnel = User::factory()->create([
            'name' => 'Nadia Recherche',
            'email' => 'nadia@efeledu.com',
            'mot_de_passe_change' => true,
        ]);
        $this->creerPersonnel($personnel);

        $this->actingAs($admin)
            ->get(route('utilisateurs.index', ['search' => 'Nadia']))
            ->assertOk()
            ->assertViewHas('total', 2)
            ->assertSee('Nadia Recherche')
            ->assertDontSee('Admin Test')
            ->assertSee('Affichage 1 sur 1 compte(s)');
    }

    public function test_la_pagination_n_affiche_pas_le_resume_anglais_de_laravel(): void
    {
        $admin = User::factory()->create([
            'mot_de_passe_change' => true,
        ]);
        $this->creerPersonnel($admin, 'admin');
        User::factory()->count(15)->create([
            'mot_de_passe_change' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('utilisateurs.index'))
            ->assertOk()
            ->assertSee('Affichage 15 sur 16 compte(s)')
            ->assertSee('aria-label="Page 2"', false)
            ->assertDontSee('Showing 1 to 15 of 16 results');
    }

    public function test_les_filtres_separent_le_personnel_des_etudiants(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Filtre',
            'mot_de_passe_change' => true,
        ]);
        $this->creerPersonnel($admin, 'admin');

        $etudiant = User::factory()->create([
            'name' => 'Etudiant Filtre',
            'mot_de_passe_change' => true,
        ]);
        Etudiant::create([
            'user_id' => $etudiant->id,
            'type' => 'etud_initial',
            'promotion' => 'CDA',
            'etablissement' => 'IEG',
        ]);

        $this->actingAs($admin)
            ->get(route('utilisateurs.index', ['type' => 'personnel']))
            ->assertOk()
            ->assertSee('Admin Filtre')
            ->assertDontSee('Etudiant Filtre');

        $this->actingAs($admin)
            ->get(route('utilisateurs.index', ['type' => 'etudiant']))
            ->assertOk()
            ->assertSee('Etudiant Filtre')
            ->assertDontSee('Admin Filtre');
    }

    public function test_un_admin_peut_bloquer_puis_debloquer_un_compte(): void
    {
        $admin = User::factory()->create(['mot_de_passe_change' => true]);
        $this->creerPersonnel($admin, 'admin');

        $utilisateur = User::factory()->create(['mot_de_passe_change' => true]);
        $this->creerPersonnel($utilisateur);

        DB::table('sessions')->insert([
            'id' => 'session-utilisateur-bloque',
            'user_id' => $utilisateur->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);
        $utilisateur->createToken('Telephone a bloquer');

        $this->actingAs($admin)
            ->patch(route('utilisateurs.blocage', $utilisateur))
            ->assertRedirect()
            ->assertSessionHas('success', __('messages.utilisateur_bloque'));

        $this->assertTrue($utilisateur->fresh()->acces_bloque);
        $this->assertDatabaseMissing('sessions', ['id' => 'session-utilisateur-bloque']);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $utilisateur->id]);

        $this->actingAs($admin)
            ->patch(route('utilisateurs.blocage', $utilisateur))
            ->assertRedirect()
            ->assertSessionHas('success', __('messages.utilisateur_debloque'));

        $this->assertFalse($utilisateur->fresh()->acces_bloque);
    }

    public function test_un_admin_ne_peut_pas_bloquer_son_propre_compte(): void
    {
        $admin = User::factory()->create(['mot_de_passe_change' => true]);
        $this->creerPersonnel($admin, 'admin');

        $this->actingAs($admin)
            ->patch(route('utilisateurs.blocage', $admin))
            ->assertRedirect()
            ->assertSessionHasErrors('utilisateur');

        $this->assertFalse($admin->fresh()->acces_bloque);
    }

    public function test_un_admin_peut_reinitialiser_un_mot_de_passe_et_forcer_son_changement(): void
    {
        $admin = User::factory()->create(['mot_de_passe_change' => true]);
        $this->creerPersonnel($admin, 'admin');

        $utilisateur = User::factory()->create([
            'password' => 'AncienPassword1!',
            'mot_de_passe_change' => true,
        ]);
        $this->creerPersonnel($utilisateur);

        DB::table('sessions')->insert([
            'id' => 'session-utilisateur-reinitialise',
            'user_id' => $utilisateur->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);
        $utilisateur->createToken('Telephone a deconnecter');

        $this->actingAs($admin)
            ->patch(route('utilisateurs.mot-de-passe', $utilisateur), [
                'nouveau_mot_de_passe' => 'NouveauPassword1!',
                'nouveau_mot_de_passe_confirmation' => 'NouveauPassword1!',
                'forcer_changement_mot_de_passe' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', __('messages.utilisateur_mot_de_passe_reinitialise'));

        $utilisateur->refresh();

        $this->assertTrue(Hash::check('NouveauPassword1!', $utilisateur->password));
        $this->assertFalse($utilisateur->mot_de_passe_change);
        $this->assertDatabaseMissing('sessions', ['id' => 'session-utilisateur-reinitialise']);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $utilisateur->id]);
    }

    public function test_un_admin_peut_reinitialiser_un_mot_de_passe_sans_forcer_son_changement(): void
    {
        $admin = User::factory()->create(['mot_de_passe_change' => true]);
        $this->creerPersonnel($admin, 'admin');

        $utilisateur = User::factory()->create(['mot_de_passe_change' => false]);
        $this->creerPersonnel($utilisateur);

        $this->actingAs($admin)
            ->patch(route('utilisateurs.mot-de-passe', $utilisateur), [
                'nouveau_mot_de_passe' => 'NouveauPassword1!',
                'nouveau_mot_de_passe_confirmation' => 'NouveauPassword1!',
            ])
            ->assertRedirect();

        $this->assertTrue($utilisateur->fresh()->mot_de_passe_change);
    }

    public function test_la_reinitialisation_refuse_une_confirmation_incorrecte(): void
    {
        $admin = User::factory()->create(['mot_de_passe_change' => true]);
        $this->creerPersonnel($admin, 'admin');

        $utilisateur = User::factory()->create([
            'password' => 'AncienPassword1!',
            'mot_de_passe_change' => true,
        ]);
        $this->creerPersonnel($utilisateur);

        $this->actingAs($admin)
            ->patch(route('utilisateurs.mot-de-passe', $utilisateur), [
                'nouveau_mot_de_passe' => 'NouveauPassword1!',
                'nouveau_mot_de_passe_confirmation' => 'AutrePassword1!',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('nouveau_mot_de_passe', null, 'reinitialisationMotDePasse');

        $this->assertTrue(Hash::check('AncienPassword1!', $utilisateur->fresh()->password));
    }

    public function test_un_admin_ne_peut_pas_reinitialiser_son_propre_mot_de_passe_depuis_la_liste(): void
    {
        $admin = User::factory()->create([
            'password' => 'AncienPassword1!',
            'mot_de_passe_change' => true,
        ]);
        $this->creerPersonnel($admin, 'admin');

        $this->actingAs($admin)
            ->patch(route('utilisateurs.mot-de-passe', $admin), [
                'nouveau_mot_de_passe' => 'NouveauPassword1!',
                'nouveau_mot_de_passe_confirmation' => 'NouveauPassword1!',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('utilisateur');

        $this->assertTrue(Hash::check('AncienPassword1!', $admin->fresh()->password));
    }

    public function test_un_compte_bloque_ne_peut_pas_se_connecter(): void
    {
        $utilisateur = User::factory()->create([
            'email' => 'compte.bloque@efeledu.com',
            'password' => 'Password1!',
            'mot_de_passe_change' => true,
            'acces_bloque' => true,
        ]);
        $this->creerPersonnel($utilisateur);

        $this->post(route('login'), [
            'email' => $utilisateur->email,
            'password' => 'Password1!',
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_un_compte_bloque_deja_connecte_est_deconnecte(): void
    {
        $utilisateur = User::factory()->create([
            'mot_de_passe_change' => true,
            'acces_bloque' => true,
        ]);
        $this->creerPersonnel($utilisateur);

        $this->actingAs($utilisateur)
            ->get(route('mon-materiel.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_un_admin_peut_supprimer_un_compte_sans_historique(): void
    {
        $admin = User::factory()->create(['mot_de_passe_change' => true]);
        $this->creerPersonnel($admin, 'admin');

        $utilisateur = User::factory()->create(['mot_de_passe_change' => true]);
        $personnel = $this->creerPersonnel($utilisateur);
        $utilisateur->createToken('Telephone du compte supprime');

        $this->actingAs($admin)
            ->delete(route('utilisateurs.destroy', $utilisateur))
            ->assertRedirect()
            ->assertSessionHas('success', __('messages.utilisateur_supprime'));

        $this->assertDatabaseMissing('users', ['id' => $utilisateur->id]);
        $this->assertDatabaseMissing('personnels', ['id' => $personnel->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $utilisateur->id]);
    }

    public function test_un_admin_ne_peut_pas_supprimer_son_propre_compte(): void
    {
        $admin = User::factory()->create(['mot_de_passe_change' => true]);
        $this->creerPersonnel($admin, 'admin');

        $this->actingAs($admin)
            ->delete(route('utilisateurs.destroy', $admin))
            ->assertRedirect()
            ->assertSessionHasErrors('utilisateur');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_un_compte_avec_un_historique_ne_peut_pas_etre_supprime(): void
    {
        $admin = User::factory()->create(['mot_de_passe_change' => true]);
        $this->creerPersonnel($admin, 'admin');

        $utilisateur = User::factory()->create(['mot_de_passe_change' => true]);
        $this->creerPersonnel($utilisateur);

        Ticket::create([
            'titre' => 'Incident de test',
            'description' => 'Historique a conserver',
            'priorite' => 'normale',
            'statut' => 'ouvert',
            'demandeur_id' => $utilisateur->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('utilisateurs.destroy', $utilisateur))
            ->assertRedirect()
            ->assertSessionHasErrors('utilisateur');

        $this->assertDatabaseHas('users', ['id' => $utilisateur->id]);
        $this->assertDatabaseHas('tickets', ['demandeur_id' => $utilisateur->id]);
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
}
