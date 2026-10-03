<?php

namespace Tests\Feature;

use App\Mail\DemandeAccepteeMail;
use App\Mail\DemandeRefuseeMail;
use App\Mail\NouveauTicketAdminMail;
use App\Mail\TicketAssigneMail;
use App\Mail\TicketCreeMail;
use App\Mail\ReponseIncidentMail;
use App\Mail\TicketResoluMail;
use App\Models\Affectation;
use App\Models\Etudiant;
use App\Models\Emprunt;
use App\Models\Materiel;
use App\Models\Notification;
use App\Models\Personnel;
use App\Models\PcPortable;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TicketAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_admin_accede_au_dashboard_mais_ne_peut_pas_creer_de_ticket(): void
    {
        $admin = $this->creerPersonnel('admin');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertDontSee('Nouveau ticket');

        $this->actingAs($admin)
            ->post(route('tickets.store'), $this->donneesTicket())
            ->assertForbidden();
    }

    public function test_personnel_est_redirige_vers_mon_materiel_et_ne_voit_que_ses_tickets(): void
    {
        $personnel = $this->creerPersonnel('personnel');
        $autrePersonnel = $this->creerPersonnel('personnel');

        Ticket::create($this->donneesTicketCreation($personnel, 'Mon ticket personnel'));
        Ticket::create($this->donneesTicketCreation($autrePersonnel, 'Ticket autre personnel'));

        $this->actingAs($personnel)
            ->get(route('dashboard'))
            ->assertRedirect(route('mon-materiel.index'));

        $this->actingAs($personnel)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('Nouveau ticket')
            ->assertSeeText("Demande d'affectation", false)
            ->assertDontSeeText("Demande d'emprunt", false)
            ->assertSee('Mon ticket personnel')
            ->assertDontSee('Ticket autre personnel');

        $this->actingAs($personnel)
            ->post(route('tickets.store'), $this->donneesTicket())
            ->assertRedirect(route('tickets.index'));

        Mail::assertSent(TicketCreeMail::class, fn ($mail) => $mail->hasTo($personnel->email));
    }

    public function test_etudiant_est_redirige_vers_mon_materiel_et_peut_creer_un_ticket(): void
    {
        $etudiant = User::factory()->create(['mot_de_passe_change' => true]);
        Etudiant::create([
            'user_id' => $etudiant->id,
            'type' => 'etud_initial',
        ]);

        $this->actingAs($etudiant)
            ->get(route('dashboard'))
            ->assertRedirect(route('mon-materiel.index'));

        $this->actingAs($etudiant)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('Nouveau ticket')
            ->assertSeeText("Demande d'emprunt", false)
            ->assertDontSeeText("Demande d'affectation", false);

        $this->actingAs($etudiant)
            ->post(route('tickets.store'), $this->donneesTicket('emprunt'))
            ->assertRedirect(route('tickets.index'));

        $this->assertDatabaseHas('tickets', ['demandeur_id' => $etudiant->id]);
    }

    public function test_creation_ticket_notifie_tous_les_admins_et_pas_les_autres_utilisateurs(): void
    {
        $adminUn = $this->creerPersonnel('admin');
        $adminDeux = $this->creerPersonnel('admin');
        $personnelOrdinaire = $this->creerPersonnel('personnel');
        $demandeur = $this->creerPersonnel('personnel');

        $this->actingAs($demandeur)
            ->post(route('tickets.store'), $this->donneesTicket())
            ->assertRedirect(route('tickets.index'));

        Mail::assertSent(TicketCreeMail::class, fn ($mail) => $mail->hasTo($demandeur->email));
        Mail::assertSent(NouveauTicketAdminMail::class, fn ($mail) => $mail->hasTo($adminUn->email));
        Mail::assertSent(NouveauTicketAdminMail::class, fn ($mail) => $mail->hasTo($adminDeux->email));
        Mail::assertNotSent(
            NouveauTicketAdminMail::class,
            fn ($mail) => $mail->hasTo($personnelOrdinaire->email) || $mail->hasTo($demandeur->email)
        );
        Mail::assertSent(NouveauTicketAdminMail::class, 2);

        $ticket = Ticket::query()->where('demandeur_id', $demandeur->id)->firstOrFail();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $adminUn->id,
            'ticket_id' => $ticket->id,
            'type' => 'nouveau_ticket',
            'read_at' => null,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $adminDeux->id,
            'ticket_id' => $ticket->id,
            'type' => 'nouveau_ticket',
            'read_at' => null,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $personnelOrdinaire->id,
            'ticket_id' => $ticket->id,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $demandeur->id,
            'ticket_id' => $ticket->id,
        ]);
    }

    public function test_point_rouge_admin_disparait_uniquement_apres_lecture_de_sa_notification(): void
    {
        $adminUn = $this->creerPersonnel('admin');
        $adminDeux = $this->creerPersonnel('admin');
        $demandeur = $this->creerPersonnel('personnel');

        $this->actingAs($demandeur)
            ->post(route('tickets.store'), $this->donneesTicket())
            ->assertRedirect(route('tickets.index'));

        $notificationAdminUn = Notification::query()
            ->where('user_id', $adminUn->id)
            ->where('type', 'nouveau_ticket')
            ->firstOrFail();
        $notificationAdminDeux = Notification::query()
            ->where('user_id', $adminDeux->id)
            ->where('type', 'nouveau_ticket')
            ->firstOrFail();

        $this->actingAs($adminUn)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('ticket-unread-dot');

        $this->actingAs($adminDeux)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('ticket-unread-dot');

        $this->actingAs($adminUn)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSeeText($notificationAdminUn->message)
            ->assertSee('ticket-unread-dot');

        $this->actingAs($adminUn)
            ->patch(route('notifications.lire', $notificationAdminUn))
            ->assertRedirect(route('tickets.index', ['ticket' => $notificationAdminUn->ticket_id]));

        $this->assertNotNull($notificationAdminUn->fresh()->read_at);
        $this->assertNull($notificationAdminDeux->fresh()->read_at);

        $this->actingAs($adminUn)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('ticket-unread-dot');

        $this->actingAs($adminDeux)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('ticket-unread-dot');
    }

    public function test_creation_ticket_remplit_la_sous_table_correspondante(): void
    {
        $personnel = $this->creerPersonnel('personnel');
        [$materiel, $pcPortable] = $this->creerPcPortableDisponible();

        $this->actingAs($personnel)
            ->post(route('tickets.store'), $this->donneesTicket('affectation'))
            ->assertRedirect(route('tickets.index'));

        $demande = Ticket::query()
            ->where('demandeur_id', $personnel->id)
            ->whereHas('demande', fn ($query) => $query->where('type_demande', 'affectation'))
            ->latest('id')
            ->firstOrFail();

        $this->assertDatabaseHas('ticket_demandes', [
            'ticket_id' => $demande->id,
            'type_demande' => 'affectation',
        ]);
        $this->assertDatabaseMissing('ticket_incidents', [
            'ticket_id' => $demande->id,
        ]);

        Affectation::create([
            'personnel_id' => $personnel->personnel->id,
            'materiel_id' => $materiel->id,
            'date_debut' => now()->toDateString(),
            'statut' => 'active',
        ]);

        $donneesIncident = $this->donneesTicket('incident');
        $donneesIncident['numero_serie'] = $pcPortable->numero_serie;

        $this->actingAs($personnel)
            ->post(route('tickets.store'), $donneesIncident)
            ->assertRedirect(route('tickets.index'));

        $incident = Ticket::query()
            ->where('demandeur_id', $personnel->id)
            ->whereHas('incident')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($materiel->id, $incident->materiel_id);
        $this->assertDatabaseHas('ticket_incidents', [
            'ticket_id' => $incident->id,
        ]);
        $this->assertDatabaseMissing('ticket_demandes', [
            'ticket_id' => $incident->id,
        ]);
    }

    public function test_incident_est_masque_et_refuse_sans_materiel_actif(): void
    {
        $personnel = $this->creerPersonnel('personnel');
        $etudiant = $this->creerEtudiant();
        $donnees = $this->donneesTicket('incident');
        $donnees['numero_serie'] = 'MATERIEL-INEXISTANT';

        $this->actingAs($personnel)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertDontSee('<option value="incident"', false);

        $this->actingAs($personnel)
            ->post(route('tickets.store'), $donnees)
            ->assertSessionHasErrors('type');

        $this->actingAs($etudiant)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertDontSee('<option value="incident"', false);

        $this->actingAs($etudiant)
            ->post(route('tickets.store'), $donnees)
            ->assertSessionHasErrors('type');

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_personnel_peut_declarer_un_incident_uniquement_sur_son_materiel_actif(): void
    {
        $personnel = $this->creerPersonnel('personnel');
        [$materielAutorise, $pcAutorise] = $this->creerPcPortableDisponible('PC-PERSONNEL-001');
        [$materielInterdit, $pcInterdit] = $this->creerPcPortableDisponible('PC-AUTRE-001');

        Affectation::create([
            'personnel_id' => $personnel->personnel->id,
            'materiel_id' => $materielAutorise->id,
            'date_debut' => now()->toDateString(),
            'statut' => 'active',
        ]);

        $this->actingAs($personnel)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('<option value="incident"', false)
            ->assertSee('data-numero-serie="'.$pcAutorise->numero_serie.'"', false)
            ->assertSee('Casque (non affecté)')
            ->assertSee($pcAutorise->numero_serie)
            ->assertDontSee($pcInterdit->numero_serie);

        $donneesInterdites = $this->donneesTicket('incident');
        $donneesInterdites['type_materiel'] = 'casque';
        $donneesInterdites['numero_serie'] = $pcInterdit->numero_serie;

        $this->actingAs($personnel)
            ->post(route('tickets.store'), $donneesInterdites)
            ->assertSessionHasErrors('type_materiel');

        $donneesAutorisees = $this->donneesTicket('incident');
        $donneesAutorisees['numero_serie'] = $pcInterdit->numero_serie;

        $this->actingAs($personnel)
            ->post(route('tickets.store'), $donneesAutorisees)
            ->assertRedirect(route('tickets.index'));

        $this->assertDatabaseHas('tickets', [
            'demandeur_id' => $personnel->id,
            'materiel_id' => $materielAutorise->id,
        ]);
        $this->assertDatabaseMissing('tickets', [
            'demandeur_id' => $personnel->id,
            'materiel_id' => $materielInterdit->id,
        ]);
    }

    public function test_etudiant_peut_declarer_un_incident_sur_son_pc_emprunte(): void
    {
        $etudiant = $this->creerEtudiant();
        [$materiel, $pcPortable] = $this->creerPcPortableDisponible('PC-ETUDIANT-001');

        Emprunt::create([
            'etudiant_id' => $etudiant->etudiant->id,
            'pc_numero_serie' => $pcPortable->numero_serie,
            'date_debut' => now()->toDateString(),
            'date_fin_prevue' => now()->addMonth()->toDateString(),
            'statut' => 'en_cours',
        ]);

        $this->actingAs($etudiant)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('<option value="incident"', false)
            ->assertSee($pcPortable->numero_serie);

        $donnees = $this->donneesTicket('incident');
        $donnees['numero_serie'] = $pcPortable->numero_serie;

        $this->actingAs($etudiant)
            ->post(route('tickets.store'), $donnees)
            ->assertRedirect(route('tickets.index'));

        $ticket = Ticket::query()
            ->where('demandeur_id', $etudiant->id)
            ->where('materiel_id', $materiel->id)
            ->firstOrFail();

        $this->assertDatabaseHas('ticket_incidents', ['ticket_id' => $ticket->id]);
    }

    public function test_une_double_soumission_ne_cree_qu_un_seul_ticket(): void
    {
        $personnel = $this->creerPersonnel('personnel');
        $donnees = $this->donneesTicket('affectation');

        $this->actingAs($personnel)
            ->post(route('tickets.store'), $donnees)
            ->assertRedirect(route('tickets.index'));

        $this->actingAs($personnel)
            ->post(route('tickets.store'), $donnees)
            ->assertRedirect(route('tickets.index'))
            ->assertSessionHas('info', __('messages.ticket_creation_deja_en_cours'));

        $this->assertDatabaseCount('tickets', 1);
        $this->assertDatabaseCount('ticket_demandes', 1);
    }

    public function test_personnel_et_etudiant_ne_peuvent_pas_contourner_les_types_autorises(): void
    {
        $personnel = $this->creerPersonnel('personnel');
        $etudiant = User::factory()->create(['mot_de_passe_change' => true]);
        Etudiant::create([
            'user_id' => $etudiant->id,
            'type' => 'etud_initial',
        ]);

        $this->actingAs($personnel)
            ->post(route('tickets.store'), $this->donneesTicket('emprunt'))
            ->assertSessionHasErrors('type');

        $this->actingAs($etudiant)
            ->post(route('tickets.store'), $this->donneesTicket('affectation'))
            ->assertSessionHasErrors('type');

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_priorite_basse_ne_peut_plus_etre_utilisee(): void
    {
        $personnel = $this->creerPersonnel('personnel');
        $donnees = $this->donneesTicket();
        $donnees['priorite'] = 'basse';

        $this->actingAs($personnel)
            ->post(route('tickets.store'), $donnees)
            ->assertSessionHasErrors('priorite');

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_admin_peut_s_assigner_un_ticket_ouvert_qui_ne_peut_plus_etre_reassigne(): void
    {
        $admin = $this->creerPersonnel('admin');
        $autreAdmin = $this->creerPersonnel('admin');
        $demandeur = $this->creerPersonnel('personnel');
        $ticket = Ticket::create($this->donneesTicketCreation($demandeur, 'Ticket a assigner'));

        $this->actingAs($admin)
            ->patch(route('tickets.assigner', $ticket))
            ->assertRedirect(route('tickets.index'))
            ->assertSessionHas('success', __('messages.ticket_assigne'));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'technicien_id' => $admin->id,
            'statut' => 'en_cours',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $demandeur->id,
            'ticket_id' => $ticket->id,
            'type' => 'ticket_assigne',
            'read_at' => null,
        ]);

        Mail::assertSent(TicketAssigneMail::class, fn ($mail) => $mail->hasTo($demandeur->email));

        $this->actingAs($autreAdmin)
            ->patch(route('tickets.assigner', $ticket))
            ->assertRedirect(route('tickets.index'))
            ->assertSessionHasErrors('ticket');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'technicien_id' => $admin->id,
            'statut' => 'en_cours',
        ]);
    }

    public function test_point_rouge_disparait_quand_le_demandeur_lit_sa_notification(): void
    {
        $admin = $this->creerPersonnel('admin');
        $demandeur = $this->creerPersonnel('personnel');
        $ticket = Ticket::create($this->donneesTicketCreation($demandeur, 'Ticket avec notification'));

        $this->actingAs($admin)
            ->patch(route('tickets.assigner', $ticket))
            ->assertRedirect(route('tickets.index'));

        $notification = Notification::query()
            ->where('user_id', $demandeur->id)
            ->where('ticket_id', $ticket->id)
            ->where('type', 'ticket_assigne')
            ->firstOrFail();

        $this->actingAs($demandeur)
            ->get(route('mon-materiel.index'))
            ->assertOk()
            ->assertSee('ticket-unread-dot');

        $this->actingAs($demandeur)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSeeText($notification->message)
            ->assertSee('ticket-unread-dot');

        $this->actingAs($demandeur)
            ->patch(route('notifications.lire', $notification))
            ->assertRedirect(route('tickets.index', ['ticket' => $ticket->id]));

        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($demandeur)
            ->get(route('mon-materiel.index'))
            ->assertOk()
            ->assertDontSee('ticket-unread-dot');
    }

    public function test_personnel_et_etudiant_ne_peuvent_pas_assigner_un_ticket(): void
    {
        $personnel = $this->creerPersonnel('personnel');
        $etudiant = User::factory()->create(['mot_de_passe_change' => true]);
        Etudiant::create([
            'user_id' => $etudiant->id,
            'type' => 'etud_initial',
        ]);
        $ticket = Ticket::create($this->donneesTicketCreation($personnel, 'Ticket protege'));

        $this->actingAs($personnel)
            ->patch(route('tickets.assigner', $ticket))
            ->assertForbidden();

        $this->actingAs($etudiant)
            ->patch(route('tickets.assigner', $ticket))
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'technicien_id' => null,
            'statut' => 'ouvert',
        ]);
    }

    public function test_ticket_resolu_ou_ferme_ne_peut_pas_etre_assigne(): void
    {
        $admin = $this->creerPersonnel('admin');
        $demandeur = $this->creerPersonnel('personnel');

        foreach (['resolu', 'ferme'] as $statut) {
            $ticket = Ticket::create(array_merge(
                $this->donneesTicketCreation($demandeur, 'Ticket ' . $statut),
                ['statut' => $statut]
            ));

            $this->actingAs($admin)
                ->patch(route('tickets.assigner', $ticket))
                ->assertRedirect(route('tickets.index'))
                ->assertSessionHasErrors('ticket');

            $this->assertDatabaseHas('tickets', [
                'id' => $ticket->id,
                'technicien_id' => null,
                'statut' => $statut,
            ]);
        }
    }

    public function test_admin_assigne_peut_resoudre_un_ticket_en_cours(): void
    {
        $admin = $this->creerPersonnel('admin');
        $demandeur = $this->creerPersonnel('personnel');
        $ticket = $this->creerTicketIncident(array_merge(
            $this->donneesTicketCreation($demandeur, 'Ticket en cours'),
            [
                'type' => 'incident',
                'technicien_id' => $admin->id,
                'statut' => 'en_cours',
                'reponse_admin' => 'Le probleme a ete corrige.',
                'date_reponse' => now(),
            ]
        ));

        $this->actingAs($admin)
            ->patch(route('tickets.resoudre', $ticket))
            ->assertRedirect(route('tickets.index'))
            ->assertSessionHas('success', __('messages.ticket_resolu'));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'technicien_id' => $admin->id,
            'statut' => 'resolu',
        ]);

        $this->assertNotNull($ticket->fresh()->date_resolution);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $demandeur->id,
            'ticket_id' => $ticket->id,
            'type' => 'ticket_resolu',
            'read_at' => null,
        ]);
        Mail::assertSent(TicketResoluMail::class, fn ($mail) => $mail->ticket->is($ticket));

    }

    public function test_un_incident_sans_reponse_ne_peut_pas_etre_resolu(): void
    {
        $admin = $this->creerPersonnel('admin');
        $demandeur = $this->creerPersonnel('personnel');
        $ticket = $this->creerTicketIncident(array_merge(
            $this->donneesTicketCreation($demandeur, 'Incident sans reponse'),
            ['type' => 'incident', 'technicien_id' => $admin->id, 'statut' => 'en_cours']
        ));

        $this->actingAs($admin)
            ->patch(route('tickets.resoudre', $ticket))
            ->assertRedirect(route('tickets.index'))
            ->assertSessionHasErrors('ticket');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'statut' => 'en_cours',
            'date_resolution' => null,
        ]);
        Mail::assertNotSent(TicketResoluMail::class);
    }

    public function test_ticket_non_assigne_ou_assigne_a_un_autre_admin_ne_peut_pas_etre_resolu(): void
    {
        $admin = $this->creerPersonnel('admin');
        $autreAdmin = $this->creerPersonnel('admin');
        $demandeur = $this->creerPersonnel('personnel');
        $ticketNonAssigne = Ticket::create(array_merge(
            $this->donneesTicketCreation($demandeur, 'Ticket non assigne'),
            ['statut' => 'en_cours']
        ));
        $ticketAutreAdmin = Ticket::create(array_merge(
            $this->donneesTicketCreation($demandeur, 'Ticket autre admin'),
            ['technicien_id' => $autreAdmin->id, 'statut' => 'en_cours']
        ));

        foreach ([$ticketNonAssigne, $ticketAutreAdmin] as $ticket) {
            $this->actingAs($admin)
                ->patch(route('tickets.resoudre', $ticket))
                ->assertRedirect(route('tickets.index'))
                ->assertSessionHasErrors('ticket');

            $this->assertDatabaseHas('tickets', [
                'id' => $ticket->id,
                'statut' => 'en_cours',
            ]);
        }
    }

    public function test_personnel_et_etudiant_ne_peuvent_pas_resoudre_un_ticket(): void
    {
        $admin = $this->creerPersonnel('admin');
        $personnel = $this->creerPersonnel('personnel');
        $etudiant = User::factory()->create(['mot_de_passe_change' => true]);
        Etudiant::create([
            'user_id' => $etudiant->id,
            'type' => 'etud_initial',
        ]);
        $ticket = Ticket::create(array_merge(
            $this->donneesTicketCreation($personnel, 'Ticket protege'),
            ['technicien_id' => $admin->id, 'statut' => 'en_cours']
        ));

        $this->actingAs($personnel)
            ->patch(route('tickets.resoudre', $ticket))
            ->assertForbidden();

        $this->actingAs($etudiant)
            ->patch(route('tickets.resoudre', $ticket))
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'statut' => 'en_cours',
        ]);
    }

    public function test_admin_assigne_peut_creer_une_affectation_depuis_un_ticket(): void
    {
        $admin = $this->creerPersonnel('admin');
        $demandeur = $this->creerPersonnel('personnel');
        $ticket = $this->creerTicketDemande(array_merge(
            $this->donneesTicketCreation($demandeur, 'Demande de PC'),
            ['technicien_id' => $admin->id, 'statut' => 'en_cours']
        ));
        [$materiel, $pcPortable] = $this->creerPcPortableDisponible();

        $this->actingAs($admin)
            ->post(route('tickets.affectation.store', $ticket), [
                'materiel_type' => 'pc-portable',
                'materiel_numero_serie' => $pcPortable->numero_serie,
                'date_debut' => '2026-09-21',
            ])
            ->assertRedirect(route('tickets.index'))
            ->assertSessionHas('success', __('messages.ticket_affectation_creee'));

        $this->assertDatabaseHas('affectations', [
            'ticket_id' => $ticket->id,
            'personnel_id' => $demandeur->personnel->id,
            'materiel_id' => $materiel->id,
            'statut' => 'active',
        ]);
        $this->assertDatabaseHas('materiels', [
            'id' => $materiel->id,
            'etat' => 'affecte',
        ]);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'materiel_id' => $materiel->id,
            'statut' => 'resolu',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $demandeur->id,
            'ticket_id' => $ticket->id,
            'type' => 'affectation_acceptee',
            'read_at' => null,
        ]);

        Mail::assertSent(DemandeAccepteeMail::class, function ($mail) use ($demandeur) {
            return $mail->hasTo($demandeur->email)
                && $mail->ticket->demande->type_demande === 'affectation';
        });
    }

    public function test_admin_non_assigne_ne_peut_pas_creer_affectation_depuis_un_ticket(): void
    {
        $adminAssigne = $this->creerPersonnel('admin');
        $autreAdmin = $this->creerPersonnel('admin');
        $demandeur = $this->creerPersonnel('personnel');
        $ticket = $this->creerTicketDemande(array_merge(
            $this->donneesTicketCreation($demandeur, 'Demande protegee'),
            ['technicien_id' => $adminAssigne->id, 'statut' => 'en_cours']
        ));
        [$materiel, $pcPortable] = $this->creerPcPortableDisponible();

        $this->actingAs($autreAdmin)
            ->post(route('tickets.affectation.store', $ticket), [
                'materiel_type' => 'pc-portable',
                'materiel_numero_serie' => $pcPortable->numero_serie,
                'date_debut' => '2026-09-21',
            ])
            ->assertSessionHasErrors('ticket');

        $this->assertDatabaseCount('affectations', 0);
        $this->assertDatabaseHas('materiels', [
            'id' => $materiel->id,
            'etat' => 'disponible',
        ]);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'materiel_id' => null,
            'statut' => 'en_cours',
        ]);
    }

    public function test_admin_assigne_peut_creer_un_emprunt_depuis_un_ticket(): void
    {
        $admin = $this->creerPersonnel('admin');
        $etudiant = $this->creerEtudiant();
        $ticket = $this->creerTicketDemande(array_merge(
            $this->donneesTicketCreation($etudiant, 'Demande emprunt PC'),
            [
                'type' => 'emprunt',
                'technicien_id' => $admin->id,
                'statut' => 'en_cours',
            ]
        ));
        [$materiel, $pcPortable] = $this->creerPcPortableDisponible();

        $this->actingAs($admin)
            ->post(route('tickets.emprunt.store', $ticket), [
                'traitement_ticket' => 'emprunt',
                'ticket_id' => $ticket->id,
                'materiel_type' => 'pc-portable',
                'materiel_numero_serie' => $pcPortable->numero_serie,
                'date_debut' => '2026-09-21',
                'date_fin_prevue' => '2026-09-30',
            ])
            ->assertRedirect(route('tickets.index'))
            ->assertSessionHas('success', __('messages.ticket_emprunt_cree'));

        $this->assertDatabaseHas('emprunts', [
            'ticket_id' => $ticket->id,
            'etudiant_id' => $etudiant->etudiant->id,
            'pc_numero_serie' => $pcPortable->numero_serie,
            'statut' => 'en_cours',
        ]);
        $this->assertDatabaseHas('materiels', [
            'id' => $materiel->id,
            'etat' => 'emprunte',
        ]);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'materiel_id' => $materiel->id,
            'statut' => 'resolu',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $etudiant->id,
            'ticket_id' => $ticket->id,
            'type' => 'emprunt_accepte',
            'read_at' => null,
        ]);

        Mail::assertSent(DemandeAccepteeMail::class, function ($mail) use ($etudiant) {
            return $mail->hasTo($etudiant->email)
                && $mail->ticket->demande->type_demande === 'emprunt';
        });
    }

    public function test_une_demande_ne_peut_pas_etre_traitee_comme_un_autre_type(): void
    {
        $admin = $this->creerPersonnel('admin');
        $personnel = $this->creerPersonnel('personnel');
        $etudiant = $this->creerEtudiant();

        $demandeEmprunt = $this->creerTicketDemande(array_merge(
            $this->donneesTicketCreation($etudiant, 'Demande emprunt'),
            ['type' => 'emprunt', 'technicien_id' => $admin->id, 'statut' => 'en_cours']
        ));
        $demandeAffectation = $this->creerTicketDemande(array_merge(
            $this->donneesTicketCreation($personnel, 'Demande affectation'),
            ['technicien_id' => $admin->id, 'statut' => 'en_cours']
        ));

        $this->actingAs($admin)
            ->post(route('tickets.affectation.store', $demandeEmprunt))
            ->assertSessionHasErrors('ticket');

        $this->actingAs($admin)
            ->post(route('tickets.emprunt.store', $demandeAffectation))
            ->assertSessionHasErrors('ticket');

        $this->assertDatabaseCount('affectations', 0);
        $this->assertDatabaseCount('emprunts', 0);
    }

    public function test_un_etudiant_ne_peut_pas_avoir_deux_pc_en_cours_depuis_un_ticket(): void
    {
        $admin = $this->creerPersonnel('admin');
        $etudiant = $this->creerEtudiant();
        [$premierMateriel, $premierPc] = $this->creerPcPortableDisponible('TEST-PC-101');
        [$secondMateriel, $secondPc] = $this->creerPcPortableDisponible('TEST-PC-102');

        Emprunt::create([
            'etudiant_id' => $etudiant->etudiant->id,
            'pc_numero_serie' => $premierPc->numero_serie,
            'date_debut' => '2026-09-20',
            'date_fin_prevue' => '2026-09-30',
            'statut' => 'en_cours',
        ]);

        $ticket = $this->creerTicketDemande(array_merge(
            $this->donneesTicketCreation($etudiant, 'Deuxieme demande de PC'),
            ['type' => 'emprunt', 'technicien_id' => $admin->id, 'statut' => 'en_cours']
        ));

        $this->actingAs($admin)
            ->post(route('tickets.emprunt.store', $ticket), [
                'traitement_ticket' => 'emprunt',
                'ticket_id' => $ticket->id,
                'materiel_type' => 'pc-portable',
                'materiel_numero_serie' => $secondPc->numero_serie,
                'date_debut' => '2026-09-22',
                'date_fin_prevue' => '2026-10-01',
            ])
            ->assertSessionHasErrors('materiel_numero_serie');

        $this->assertDatabaseCount('emprunts', 1);
        $this->assertDatabaseHas('materiels', ['id' => $secondMateriel->id, 'etat' => 'disponible']);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'statut' => 'en_cours']);
    }

    public function test_la_creation_directe_emprunt_refuse_un_deuxieme_pc_et_les_autres_types(): void
    {
        $admin = $this->creerPersonnel('admin');
        $etudiant = $this->creerEtudiant();
        [$premierMateriel, $premierPc] = $this->creerPcPortableDisponible('TEST-PC-201');
        [$secondMateriel, $secondPc] = $this->creerPcPortableDisponible('TEST-PC-202');

        Emprunt::create([
            'etudiant_id' => $etudiant->etudiant->id,
            'pc_numero_serie' => $premierPc->numero_serie,
            'date_debut' => '2026-09-20',
            'date_fin_prevue' => '2026-09-30',
            'statut' => 'en_cours',
        ]);

        $donnees = [
            'etudiant_id' => $etudiant->etudiant->id,
            'materiel_numero_serie' => $secondPc->numero_serie,
            'date_debut' => '2026-09-22',
            'date_fin_prevue' => '2026-10-01',
        ];

        $this->actingAs($admin)
            ->post(route('emprunts.store'), $donnees + ['materiel_type' => 'pc-portable'])
            ->assertSessionHasErrors('etudiant_id');

        $this->actingAs($admin)
            ->post(route('emprunts.store'), $donnees + ['materiel_type' => 'mini-pc'])
            ->assertSessionHasErrors('materiel_type');

        $this->assertDatabaseCount('emprunts', 1);
        $this->assertDatabaseHas('materiels', ['id' => $secondMateriel->id, 'etat' => 'disponible']);

        Emprunt::query()->firstOrFail()->update([
            'statut' => 'rendu',
            'date_retour' => '2026-09-22',
        ]);

        $this->actingAs($admin)
            ->post(route('emprunts.store'), $donnees + ['materiel_type' => 'pc-portable'])
            ->assertRedirect(route('emprunts.index'))
            ->assertSessionHas('success', __('messages.emprunt_cree'));

        $this->assertDatabaseCount('emprunts', 2);
        $this->assertDatabaseHas('materiels', ['id' => $secondMateriel->id, 'etat' => 'emprunte']);
    }

    public function test_les_materiels_autres_que_pc_portable_refusent_etat_emprunte(): void
    {
        $admin = $this->creerPersonnel('admin');

        foreach (['mini-pc.store', 'ecrans.store', 'claviers.store', 'souris.store', 'casques.store'] as $route) {
            $this->actingAs($admin)
                ->post(route($route), ['etat' => 'emprunte'])
                ->assertSessionHasErrors('etat');
        }

        $this->assertDatabaseCount('emprunts', 0);
    }

    public function test_creation_pc_portable_peut_creer_un_emprunt_direct_par_numero_de_serie(): void
    {
        $admin = $this->creerPersonnel('admin');
        $etudiant = $this->creerEtudiant();

        $this->actingAs($admin)
            ->post(route('pc-portables.store'), [
                'nom' => 'Latitude 5550',
                'marque' => 'Dell',
                'numero_serie' => 'DIRECT-PC-001',
                'cpu' => 'Intel Core i5',
                'ram' => '16 Go DDR5',
                'stockage' => '512 Go SSD',
                'os' => 'Windows 11',
                'etat' => 'emprunte',
                'a_qui_id' => $etudiant->id,
            ])
            ->assertRedirect(route('pc-portables.index'));

        $this->assertDatabaseHas('emprunts', [
            'etudiant_id' => $etudiant->etudiant->id,
            'pc_numero_serie' => 'DIRECT-PC-001',
            'statut' => 'en_cours',
        ]);
    }

    public function test_admin_non_assigne_ne_peut_pas_creer_emprunt_depuis_un_ticket(): void
    {
        $adminAssigne = $this->creerPersonnel('admin');
        $autreAdmin = $this->creerPersonnel('admin');
        $etudiant = $this->creerEtudiant();
        $ticket = $this->creerTicketDemande(array_merge(
            $this->donneesTicketCreation($etudiant, 'Demande emprunt protegee'),
            [
                'type' => 'emprunt',
                'technicien_id' => $adminAssigne->id,
                'statut' => 'en_cours',
            ]
        ));
        [$materiel, $pcPortable] = $this->creerPcPortableDisponible();

        $this->actingAs($autreAdmin)
            ->post(route('tickets.emprunt.store', $ticket), [
                'traitement_ticket' => 'emprunt',
                'ticket_id' => $ticket->id,
                'materiel_type' => 'pc-portable',
                'materiel_numero_serie' => $pcPortable->numero_serie,
                'date_debut' => '2026-09-21',
                'date_fin_prevue' => '2026-09-30',
            ])
            ->assertSessionHasErrors('ticket');

        $this->assertDatabaseCount('emprunts', 0);

        $this->assertDatabaseHas('materiels', [
            'id' => $materiel->id,
            'etat' => 'disponible',
        ]);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'materiel_id' => null,
            'statut' => 'en_cours',
        ]);
    }

    public function test_admin_assigne_peut_refuser_une_demande_et_le_demandeur_voit_le_motif(): void
    {
        $admin = $this->creerPersonnel('admin');
        $demandeur = $this->creerPersonnel('personnel');
        $ticket = $this->creerTicketDemande(array_merge(
            $this->donneesTicketCreation($demandeur, 'Demande sans stock'),
            ['technicien_id' => $admin->id, 'statut' => 'en_cours']
        ));
        $motif = 'Aucun PC portable disponible actuellement.';

        $this->actingAs($admin)
            ->patch(route('tickets.refuser', $ticket), ['motif_refus' => $motif])
            ->assertRedirect(route('tickets.index'))
            ->assertSessionHas('success', __('messages.ticket_refuse'));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'statut' => 'refuse',
            'materiel_id' => null,
        ]);
        $this->assertDatabaseHas('ticket_demandes', [
            'ticket_id' => $ticket->id,
            'type_demande' => 'affectation',
            'motif_refus' => $motif,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $demandeur->id,
            'ticket_id' => $ticket->id,
            'type' => 'ticket_refuse',
            'message' => __('messages.notification_ticket_refuse', [
                'titre' => $ticket->titre,
                'motif' => $motif,
            ]),
            'read_at' => null,
        ]);
        $this->assertDatabaseCount('affectations', 0);
        $this->assertDatabaseCount('emprunts', 0);

        Mail::assertSent(DemandeRefuseeMail::class, function ($mail) use ($demandeur, $motif) {
            return $mail->hasTo($demandeur->email)
                && $mail->ticket->demande->motif_refus === $motif;
        });

        $this->actingAs($demandeur)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('Refusé')
            ->assertSee($motif);
    }

    public function test_refus_exige_un_motif_et_l_admin_assigne(): void
    {
        $adminAssigne = $this->creerPersonnel('admin');
        $autreAdmin = $this->creerPersonnel('admin');
        $demandeur = $this->creerPersonnel('personnel');
        $ticket = $this->creerTicketDemande(array_merge(
            $this->donneesTicketCreation($demandeur, 'Demande protegee contre le refus'),
            ['technicien_id' => $adminAssigne->id, 'statut' => 'en_cours']
        ));

        $this->actingAs($adminAssigne)
            ->patch(route('tickets.refuser', $ticket), ['motif_refus' => ''])
            ->assertSessionHasErrors('motif_refus');

        $this->actingAs($autreAdmin)
            ->patch(route('tickets.refuser', $ticket), ['motif_refus' => 'Refus non autorise.'])
            ->assertSessionHasErrors('ticket');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'statut' => 'en_cours',
        ]);
    }

    public function test_un_incident_ne_peut_pas_etre_refuse_comme_une_demande(): void
    {
        $admin = $this->creerPersonnel('admin');
        $demandeur = $this->creerPersonnel('personnel');
        $ticket = $this->creerTicketIncident(array_merge(
            $this->donneesTicketCreation($demandeur, 'Incident materiel'),
            [
                'type' => 'incident',
                'technicien_id' => $admin->id,
                'statut' => 'en_cours',
            ]
        ));

        $this->actingAs($admin)
            ->patch(route('tickets.refuser', $ticket), ['motif_refus' => 'Incident refuse.'])
            ->assertSessionHasErrors('ticket');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'statut' => 'en_cours',
        ]);
    }

    public function test_admin_assigne_peut_repondre_puis_modifier_sa_reponse_incident(): void
    {
        $admin = $this->creerPersonnel('admin');
        $demandeur = $this->creerPersonnel('personnel');
        $ticket = $this->creerTicketIncident(array_merge(
            $this->donneesTicketCreation($demandeur, 'PC en panne'),
            [
                'type' => 'incident',
                'technicien_id' => $admin->id,
                'statut' => 'en_cours',
            ]
        ));
        $premiereReponse = 'Deposez votre ordinateur au support IT pour un diagnostic.';

        $this->actingAs($admin)
            ->patch(route('tickets.repondre', $ticket), ['reponse_admin' => $premiereReponse])
            ->assertRedirect(route('tickets.index'))
            ->assertSessionHas('success', __('messages.ticket_reponse_envoyee'));

        $this->assertDatabaseHas('ticket_incidents', [
            'ticket_id' => $ticket->id,
            'reponse_admin' => $premiereReponse,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $demandeur->id,
            'ticket_id' => $ticket->id,
            'type' => 'reponse_incident',
            'read_at' => null,
        ]);
        Mail::assertSent(ReponseIncidentMail::class, function ($mail) use ($demandeur, $premiereReponse) {
            return $mail->hasTo($demandeur->email)
                && ! $mail->estModification
                && $mail->ticket->incident->reponse_admin === $premiereReponse;
        });

        $this->actingAs($demandeur)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee($premiereReponse);

        Mail::fake();
        $nouvelleReponse = 'Deposez votre ordinateur demain matin au support IT.';

        $this->actingAs($admin)
            ->patch(route('tickets.repondre', $ticket), ['reponse_admin' => $nouvelleReponse])
            ->assertSessionHas('success', __('messages.ticket_reponse_modifiee'));

        $this->assertDatabaseHas('ticket_incidents', [
            'ticket_id' => $ticket->id,
            'reponse_admin' => $nouvelleReponse,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $demandeur->id,
            'ticket_id' => $ticket->id,
            'type' => 'reponse_incident_modifiee',
            'read_at' => null,
        ]);
        Mail::assertSent(ReponseIncidentMail::class, function ($mail) use ($demandeur) {
            return $mail->hasTo($demandeur->email) && $mail->estModification;
        });
    }

    public function test_reponse_incident_est_reservee_a_l_admin_assigne_et_a_un_ticket_en_cours(): void
    {
        $adminAssigne = $this->creerPersonnel('admin');
        $autreAdmin = $this->creerPersonnel('admin');
        $demandeur = $this->creerPersonnel('personnel');
        $incident = $this->creerTicketIncident(array_merge(
            $this->donneesTicketCreation($demandeur, 'Incident protege'),
            [
                'type' => 'incident',
                'technicien_id' => $adminAssigne->id,
                'statut' => 'en_cours',
            ]
        ));

        $this->actingAs($autreAdmin)
            ->patch(route('tickets.repondre', $incident), ['reponse_admin' => 'Reponse interdite.'])
            ->assertSessionHasErrors('ticket');

        $incident->update(['statut' => 'resolu']);

        $this->actingAs($adminAssigne)
            ->patch(route('tickets.repondre', $incident), ['reponse_admin' => 'Reponse trop tardive.'])
            ->assertSessionHasErrors('ticket');

        $demandeAffectation = $this->creerTicketDemande(array_merge(
            $this->donneesTicketCreation($demandeur, 'Demande affectation'),
            ['technicien_id' => $adminAssigne->id, 'statut' => 'en_cours']
        ));

        $this->actingAs($adminAssigne)
            ->patch(route('tickets.repondre', $demandeAffectation), ['reponse_admin' => 'Mauvais type.'])
            ->assertSessionHasErrors('ticket');

        $this->assertDatabaseMissing('ticket_incidents', [
            'ticket_id' => $incident->id,
            'reponse_admin' => 'Reponse interdite.',
        ]);
        Mail::assertNotSent(ReponseIncidentMail::class);
    }

    private function creerPersonnel(string $role): User
    {
        $utilisateur = User::factory()->create(['mot_de_passe_change' => true]);

        Personnel::create([
            'user_id' => $utilisateur->id,
            'type_contrat' => 'cdi',
            'role' => $role,
        ]);

        return $utilisateur;
    }

    private function creerEtudiant(): User
    {
        $utilisateur = User::factory()->create(['mot_de_passe_change' => true]);

        Etudiant::create([
            'user_id' => $utilisateur->id,
            'type' => 'etud_initial',
        ]);

        return $utilisateur;
    }

    private function creerTicketIncident(array $donnees): Ticket
    {
        $reponseAdmin = $donnees['reponse_admin'] ?? null;
        $dateReponse = $donnees['date_reponse'] ?? null;
        unset($donnees['type'], $donnees['reponse_admin'], $donnees['date_reponse']);

        $ticket = Ticket::create($donnees);

        $ticket->incident()->create([
            'reponse_admin' => $reponseAdmin,
            'date_reponse' => $dateReponse,
        ]);

        return $ticket;
    }

    private function creerTicketDemande(array $donnees): Ticket
    {
        $typeDemande = $donnees['type'] ?? 'affectation';
        $motifRefus = $donnees['motif_refus'] ?? null;
        $dateRefus = $donnees['date_refus'] ?? null;
        unset($donnees['type'], $donnees['motif_refus'], $donnees['date_refus']);

        $ticket = Ticket::create($donnees);

        $ticket->demande()->create([
            'type_demande' => $typeDemande,
            'motif_refus' => $motifRefus,
            'date_refus' => $dateRefus,
        ]);

        return $ticket;
    }

    private function donneesTicket(string $type = 'affectation'): array
    {
        $donnees = [
            'titre' => 'Probleme informatique',
            'description' => 'Description du probleme rencontre.',
            'type' => $type,
            'priorite' => 'normale',
        ];

        if ($type === 'incident') {
            $donnees['type_materiel'] = 'pc_portable';
        }

        return $donnees;
    }

    private function donneesTicketCreation(User $demandeur, string $titre): array
    {
        return [
            'titre' => $titre,
            'description' => 'Description du ticket.',
            'type' => 'affectation',
            'priorite' => 'normale',
            'statut' => 'ouvert',
            'demandeur_id' => $demandeur->id,
        ];
    }

    private function creerPcPortableDisponible(string $numeroSerie = 'TEST-PC-001'): array
    {
        $materiel = Materiel::create([
            'type_materiel' => 'pc_portable',
            'nom' => 'Latitude 5540',
            'marque' => 'Dell',
            'etat' => 'disponible',
        ]);

        $pcPortable = PcPortable::create([
            'numero_serie' => $numeroSerie,
            'materiel_id' => $materiel->id,
            'cpu' => 'Intel Core i5',
            'ram' => '16 Go DDR5',
            'stockage' => '512 Go SSD',
            'os' => 'Windows 11',
        ]);

        return [$materiel, $pcPortable];
    }
}
