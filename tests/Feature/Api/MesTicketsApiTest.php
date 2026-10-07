<?php

namespace Tests\Feature\Api;

use App\Models\Etudiant;
use App\Models\Personnel;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MesTicketsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_personnel_recupere_uniquement_ses_tickets(): void
    {
        $utilisateur = User::factory()->create();
        $this->creerPersonnel($utilisateur);

        $incident = Ticket::create([
            'titre' => 'Ecran noir',
            'description' => 'Le PC ne demarre plus.',
            'priorite' => 'haute',
            'statut' => 'en_cours',
            'demandeur_id' => $utilisateur->id,
        ]);
        $incident->incident()->create([
            'reponse_admin' => 'Passez au support informatique.',
            'date_reponse' => now(),
        ]);

        $demande = Ticket::create([
            'titre' => 'Demande de clavier',
            'description' => 'Besoin d un clavier.',
            'priorite' => 'normale',
            'statut' => 'refuse',
            'demandeur_id' => $utilisateur->id,
        ]);
        $demande->demande()->create([
            'type_demande' => 'affectation',
            'motif_refus' => 'Aucun clavier disponible.',
            'date_refus' => now(),
        ]);

        $autreUtilisateur = User::factory()->create();
        $this->creerPersonnel($autreUtilisateur);
        $autreTicket = Ticket::create([
            'titre' => 'Ticket d un autre utilisateur',
            'priorite' => 'normale',
            'statut' => 'ouvert',
            'demandeur_id' => $autreUtilisateur->id,
        ]);
        $autreTicket->incident()->create();

        $this->withToken($utilisateur->createToken('Telephone personnel')->plainTextToken)
            ->getJson('/api/mes-tickets')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonFragment([
                'type' => 'incident',
                'titre' => 'Ecran noir',
                'reponse_admin' => 'Passez au support informatique.',
            ])
            ->assertJsonFragment([
                'type' => 'affectation',
                'titre' => 'Demande de clavier',
                'motif_refus' => 'Aucun clavier disponible.',
            ])
            ->assertJsonMissing([
                'titre' => 'Ticket d un autre utilisateur',
            ]);
    }

    public function test_un_etudiant_recupere_sa_demande_emprunt(): void
    {
        $utilisateur = User::factory()->create();
        Etudiant::create([
            'user_id' => $utilisateur->id,
            'type' => 'etud_initial',
        ]);

        $ticket = Ticket::create([
            'titre' => 'Demande de PC',
            'description' => 'PC necessaire pour la formation.',
            'priorite' => 'normale',
            'statut' => 'ouvert',
            'demandeur_id' => $utilisateur->id,
        ]);
        $ticket->demande()->create(['type_demande' => 'emprunt']);

        $this->withToken($utilisateur->createToken('Telephone etudiant')->plainTextToken)
            ->getJson('/api/mes-tickets')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('tickets.0.type', 'emprunt')
            ->assertJsonPath('tickets.0.titre', 'Demande de PC')
            ->assertJsonPath('tickets.0.statut', 'ouvert');
    }

    public function test_la_route_exige_une_authentification(): void
    {
        $this->getJson('/api/mes-tickets')->assertUnauthorized();
    }

    public function test_un_administrateur_ne_peut_pas_consulter_la_route_mobile(): void
    {
        $utilisateur = User::factory()->create();
        $this->creerPersonnel($utilisateur, 'admin');

        $this->withToken($utilisateur->createToken('Telephone admin')->plainTextToken)
            ->getJson('/api/mes-tickets')
            ->assertForbidden();
    }

    public function test_les_options_personnel_masquent_incident_sans_materiel(): void
    {
        $utilisateur = User::factory()->create();
        $this->creerPersonnel($utilisateur);

        $this->withToken($utilisateur->createToken('Telephone personnel')->plainTextToken)
            ->getJson('/api/mes-tickets/options')
            ->assertOk()
            ->assertJsonPath('peut_declarer_incident', false)
            ->assertJsonCount(1, 'types')
            ->assertJsonPath('types.0.value', 'affectation')
            ->assertJsonCount(0, 'materiels');
    }

    public function test_un_personnel_peut_creer_une_demande_affectation_depuis_api(): void
    {
        $utilisateur = User::factory()->create();
        $this->creerPersonnel($utilisateur);
        $donnees = [
            'type' => 'affectation',
            'titre' => 'Demande de clavier',
            'description' => 'Besoin d un clavier pour mon poste.',
            'priorite' => 'normale',
        ];

        $this->withToken($utilisateur->createToken('Telephone personnel')->plainTextToken)
            ->postJson('/api/mes-tickets', $donnees)
            ->assertCreated()
            ->assertJsonPath('message', __('messages.ticket_cree'))
            ->assertJsonPath('ticket.type', 'affectation')
            ->assertJsonPath('ticket.statut', 'ouvert');

        $ticket = Ticket::query()->where('demandeur_id', $utilisateur->id)->firstOrFail();

        $this->assertDatabaseHas('ticket_demandes', [
            'ticket_id' => $ticket->id,
            'type_demande' => 'affectation',
        ]);
    }

    public function test_un_etudiant_ne_peut_pas_creer_une_demande_affectation(): void
    {
        $utilisateur = User::factory()->create();
        Etudiant::create([
            'user_id' => $utilisateur->id,
            'type' => 'etud_initial',
        ]);

        $this->withToken($utilisateur->createToken('Telephone etudiant')->plainTextToken)
            ->postJson('/api/mes-tickets', [
                'type' => 'affectation',
                'titre' => 'Demande interdite',
                'priorite' => 'normale',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_une_double_creation_mobile_ne_cree_qu_un_ticket(): void
    {
        $utilisateur = User::factory()->create();
        $this->creerPersonnel($utilisateur);
        $jeton = $utilisateur->createToken('Telephone personnel')->plainTextToken;
        $donnees = [
            'type' => 'affectation',
            'titre' => 'Demande en double',
            'description' => 'Une seule demande doit etre creee.',
            'priorite' => 'normale',
        ];

        $this->withToken($jeton)
            ->postJson('/api/mes-tickets', $donnees)
            ->assertCreated();

        $this->withToken($jeton)
            ->postJson('/api/mes-tickets', $donnees)
            ->assertConflict()
            ->assertJsonPath('message', __('messages.ticket_creation_deja_en_cours'));

        $this->assertDatabaseCount('tickets', 1);
        $this->assertDatabaseCount('ticket_demandes', 1);
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
