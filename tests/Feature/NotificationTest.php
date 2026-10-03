<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Personnel;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_utilisateur_ne_voit_que_ses_notifications(): void
    {
        $utilisateur = $this->creerPersonnel();
        $autreUtilisateur = $this->creerPersonnel();
        $ticket = $this->creerTicket($utilisateur);

        Notification::create([
            'user_id' => $utilisateur->id,
            'ticket_id' => $ticket->id,
            'type' => 'ticket_assigne',
            'message' => 'Notification visible.',
        ]);
        Notification::create([
            'user_id' => $autreUtilisateur->id,
            'ticket_id' => $ticket->id,
            'type' => 'nouveau_ticket',
            'message' => 'Notification privee.',
        ]);

        $this->actingAs($utilisateur)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSeeText('Notification visible.')
            ->assertDontSeeText('Notification privee.');
    }

    public function test_utilisateur_ne_peut_pas_lire_la_notification_d_un_autre(): void
    {
        $utilisateur = $this->creerPersonnel();
        $autreUtilisateur = $this->creerPersonnel();
        $ticket = $this->creerTicket($utilisateur);
        $notification = Notification::create([
            'user_id' => $autreUtilisateur->id,
            'ticket_id' => $ticket->id,
            'type' => 'nouveau_ticket',
            'message' => 'Notification protegee.',
        ]);

        $this->actingAs($utilisateur)
            ->patch(route('notifications.lire', $notification))
            ->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_tout_marquer_comme_lu_ne_modifie_que_les_notifications_de_l_utilisateur(): void
    {
        $utilisateur = $this->creerPersonnel();
        $autreUtilisateur = $this->creerPersonnel();
        $ticket = $this->creerTicket($utilisateur);

        $notificationUne = $this->creerNotification($utilisateur, $ticket, 'ticket_assigne');
        $notificationDeux = $this->creerNotification($utilisateur, $ticket, 'reponse_incident');
        $notificationAutre = $this->creerNotification($autreUtilisateur, $ticket, 'nouveau_ticket');

        $this->actingAs($utilisateur)
            ->patch(route('notifications.tout-lire'))
            ->assertRedirect(route('notifications.index'))
            ->assertSessionHas('success', __('messages.notifications_toutes_lues'));

        $this->assertNotNull($notificationUne->fresh()->read_at);
        $this->assertNotNull($notificationDeux->fresh()->read_at);
        $this->assertNull($notificationAutre->fresh()->read_at);
    }

    private function creerPersonnel(): User
    {
        $utilisateur = User::factory()->create(['mot_de_passe_change' => true]);

        Personnel::create([
            'user_id' => $utilisateur->id,
            'type_contrat' => 'cdi',
            'role' => 'personnel',
        ]);

        return $utilisateur;
    }

    private function creerTicket(User $demandeur): Ticket
    {
        return Ticket::create([
            'titre' => 'Ticket de test',
            'description' => 'Description du ticket.',
            'priorite' => 'normale',
            'statut' => 'ouvert',
            'demandeur_id' => $demandeur->id,
        ]);
    }

    private function creerNotification(User $utilisateur, Ticket $ticket, string $type): Notification
    {
        return Notification::create([
            'user_id' => $utilisateur->id,
            'ticket_id' => $ticket->id,
            'type' => $type,
            'message' => 'Mise a jour du ticket.',
        ]);
    }
}
