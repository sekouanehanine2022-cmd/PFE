<?php

namespace Tests\Feature\Api;

use App\Models\Notification;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_utilisateur_recupere_uniquement_ses_notifications(): void
    {
        $utilisateur = $this->creerPersonnel();
        $autreUtilisateur = $this->creerPersonnel();

        Notification::create([
            'user_id' => $utilisateur->id,
            'type' => 'ticket_assigne',
            'message' => 'Votre ticket est en cours de traitement.',
        ]);
        Notification::create([
            'user_id' => $utilisateur->id,
            'type' => 'ticket_resolu',
            'message' => 'Votre ticket est resolu.',
            'read_at' => now(),
        ]);
        Notification::create([
            'user_id' => $autreUtilisateur->id,
            'type' => 'ticket_assigne',
            'message' => 'Notification d un autre utilisateur.',
        ]);

        $this->withToken($utilisateur->createToken('Telephone')->plainTextToken)
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('nombre_non_lues', 1)
            ->assertJsonCount(2, 'notifications')
            ->assertJsonFragment(['message' => 'Votre ticket est en cours de traitement.'])
            ->assertJsonMissing(['message' => 'Notification d un autre utilisateur.']);
    }

    public function test_un_utilisateur_peut_marquer_sa_notification_comme_lue(): void
    {
        $utilisateur = $this->creerPersonnel();
        $notification = Notification::create([
            'user_id' => $utilisateur->id,
            'type' => 'ticket_assigne',
            'message' => 'Votre ticket est en cours de traitement.',
        ]);

        $this->withToken($utilisateur->createToken('Telephone')->plainTextToken)
            ->patchJson("/api/notifications/{$notification->id}/lire")
            ->assertOk()
            ->assertJsonPath('notification.est_lue', true);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_un_utilisateur_ne_peut_pas_lire_la_notification_d_un_autre(): void
    {
        $utilisateur = $this->creerPersonnel();
        $autreUtilisateur = $this->creerPersonnel();
        $notification = Notification::create([
            'user_id' => $autreUtilisateur->id,
            'type' => 'ticket_assigne',
            'message' => 'Notification privee.',
        ]);

        $this->withToken($utilisateur->createToken('Telephone')->plainTextToken)
            ->patchJson("/api/notifications/{$notification->id}/lire")
            ->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_un_utilisateur_peut_tout_marquer_comme_lu(): void
    {
        $utilisateur = $this->creerPersonnel();
        Notification::create([
            'user_id' => $utilisateur->id,
            'type' => 'ticket_assigne',
            'message' => 'Premiere notification.',
        ]);
        Notification::create([
            'user_id' => $utilisateur->id,
            'type' => 'ticket_resolu',
            'message' => 'Deuxieme notification.',
        ]);

        $this->withToken($utilisateur->createToken('Telephone')->plainTextToken)
            ->patchJson('/api/notifications/tout-lire')
            ->assertOk()
            ->assertJsonPath('nombre_non_lues', 0);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $utilisateur->id,
            'read_at' => null,
        ]);
    }

    public function test_les_routes_exigent_une_authentification(): void
    {
        $this->getJson('/api/notifications')->assertUnauthorized();
        $this->patchJson('/api/notifications/tout-lire')->assertUnauthorized();
    }

    private function creerPersonnel(): User
    {
        $utilisateur = User::factory()->create();
        Personnel::create([
            'user_id' => $utilisateur->id,
            'service' => 'Pedagogie',
            'poste' => 'Formateur',
            'type_contrat' => 'cdi',
            'role' => 'personnel',
        ]);

        return $utilisateur;
    }
}
