<?php

namespace Tests\Feature;

use App\Models\Cable;
use App\Models\Notification;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CableNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creation_d_un_cable_sous_le_seuil_notifie_tous_les_admins(): void
    {
        $adminUn = $this->creerPersonnel('admin');
        $adminDeux = $this->creerPersonnel('admin');
        $personnel = $this->creerPersonnel('personnel');

        $this->actingAs($adminUn)
            ->post(route('cables.store'), $this->donneesCable([
                'reference' => 'CB-BAS',
                'quantite' => 5,
                'seuil_alerte' => 5,
            ]))
            ->assertRedirect(route('cables.index'));

        $cable = Cable::where('reference', 'CB-BAS')->firstOrFail();

        foreach ([$adminUn, $adminDeux] as $admin) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $admin->id,
                'cable_id' => $cable->id,
                'type' => 'stock_cable_bas',
                'read_at' => null,
            ]);
        }

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $personnel->id,
            'cable_id' => $cable->id,
        ]);
    }

    public function test_bouton_moins_notifie_au_franchissement_sans_doublon_puis_met_a_jour_la_rupture(): void
    {
        $admin = $this->creerPersonnel('admin');
        $cable = $this->creerCable(6, 5, 'CB-MOINS');

        $this->actingAs($admin)
            ->post(route('cables.decrementer', $cable))
            ->assertRedirect(route('cables.index'));

        $notification = Notification::where('cable_id', $cable->id)->firstOrFail();
        $this->assertSame(5, $cable->fresh()->quantite_disponible);

        $notification->update(['read_at' => now()]);

        $this->post(route('cables.decrementer', $cable));

        $this->assertDatabaseCount('notifications', 1);
        $this->assertNotNull($notification->fresh()->read_at);

        $cable->update(['quantite_disponible' => 1]);
        $this->post(route('cables.decrementer', $cable));

        $notification->refresh();
        $this->assertDatabaseCount('notifications', 1);
        $this->assertNull($notification->read_at);
        $this->assertSame(
            __('messages.notification_stock_cable_rupture', [
                'type' => $cable->type_cable,
                'reference' => $cable->reference,
            ]),
            $notification->message
        );
    }

    public function test_retrait_de_stock_notifie_quand_le_stock_passe_sous_le_seuil(): void
    {
        $admin = $this->creerPersonnel('admin');
        $cable = $this->creerCable(8, 5, 'CB-RETRAIT');

        $this->actingAs($admin)
            ->post(route('cables.retirer-stock', $cable), ['quantite' => 3])
            ->assertRedirect(route('cables.index'));

        $this->assertSame(5, $cable->fresh()->quantite_disponible);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'cable_id' => $cable->id,
            'type' => 'stock_cable_bas',
        ]);
    }

    public function test_augmentation_du_seuil_notifie_si_le_stock_devient_bas(): void
    {
        $admin = $this->creerPersonnel('admin');
        $cable = $this->creerCable(6, 3, 'CB-SEUIL');

        $this->actingAs($admin)
            ->patch(route('cables.update', $cable), $this->donneesCable([
                'seuil_alerte' => 6,
            ]))
            ->assertRedirect(route('cables.index'));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'cable_id' => $cable->id,
            'type' => 'stock_cable_bas',
        ]);
    }

    public function test_aucune_notification_tant_que_le_stock_reste_au_dessus_du_seuil(): void
    {
        $admin = $this->creerPersonnel('admin');
        $cable = $this->creerCable(8, 5, 'CB-OK');

        $this->actingAs($admin)
            ->post(route('cables.decrementer', $cable))
            ->assertRedirect(route('cables.index'));

        $this->assertSame(7, $cable->fresh()->quantite_disponible);
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

    private function creerCable(int $quantite, int $seuil, string $reference): Cable
    {
        return Cable::create([
            'reference' => $reference,
            'type_cable' => 'HDMI',
            'longueur' => '1.5m',
            'quantite' => $quantite,
            'quantite_disponible' => $quantite,
            'seuil_alerte' => $seuil,
        ]);
    }

    private function donneesCable(array $surcharge = []): array
    {
        return array_merge([
            'reference' => 'CB-TEST',
            'type_cable' => 'HDMI',
            'longueur' => '1.5m',
            'quantite' => 10,
            'seuil_alerte' => 5,
            'couleur' => 'Noir',
            'emplacement' => 'Stock',
        ], $surcharge);
    }
}
