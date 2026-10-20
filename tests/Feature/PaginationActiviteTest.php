<?php

namespace Tests\Feature;

use App\Models\Affectation;
use App\Models\Emprunt;
use App\Models\Etudiant;
use App\Models\Materiel;
use App\Models\PcPortable;
use App\Models\Personnel;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaginationActiviteTest extends TestCase
{
    use RefreshDatabase;

    public static function pages(): array
    {
        return [
            ['affectations.index', 'affectations', 'affectation', Affectation::class, 'en_cours'],
            ['emprunts.index', 'emprunts', 'emprunt', Emprunt::class, 'en_retard'],
            ['tickets.index', 'tickets', 'ticket', Ticket::class, 'ouvert'],
        ];
    }

    #[DataProvider('pages')]
    public function test_pagination_filtres_et_ouverture_notification(string $route, string $variable, string $selection, string $classe, string $statut): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-14 12:00:00'));
        $admin = $this->personnel('Admin', 'admin');
        $personnel = $this->personnel('CIBLE Personnel');
        $etudiant = User::factory()->create(['name' => 'CIBLE Etudiant']);
        Etudiant::create(['user_id' => $etudiant->id, 'type' => 'alt_externe']);
        $materiel = Materiel::create(['nom' => 'Dell', 'marque' => 'Dell', 'etat' => 'disponible', 'type_materiel' => 'pc_portable']);
        PcPortable::create(['materiel_id' => $materiel->id, 'numero_serie' => 'PAGE-TEST', 'cpu' => 'Intel i5', 'ram' => '16 Go', 'stockage' => '512 Go', 'os' => 'Windows 11']);
        $ids = [];
        for ($i = 0; $i < 21; $i++) {
            $donnees = match ($classe) {
                Affectation::class => ['personnel_id' => $personnel->id, 'materiel_id' => $materiel->id,
                    'date_debut' => today()->subDays(10), 'date_fin' => today()->subDay(), 'statut' => $i === 20 ? 'rendu' : 'active'],
                Emprunt::class => ['etudiant_id' => $etudiant->id, 'materiel_id' => $materiel->id,
                    'date_debut' => today()->subDays(10), 'date_fin_prevue' => today()->subDay(), 'statut' => $i === 20 ? 'rendu' : 'en_cours'],
                Ticket::class => ['demandeur_id' => $personnel->id, 'titre' => 'CIBLE Ticket', 'priorite' => 'normale', 'statut' => $i === 20 ? 'resolu' : 'ouvert'],
            };
            $objet = $classe::create($donnees);
            if ($objet instanceof Ticket) {
                $objet->demande()->create(['type_demande' => 'affectation']);
            }
            if ($i < 20) { $ids[] = $objet->id; }
        }
        $params = ['search' => 'CIBLE', 'statut' => $statut];
        $this->actingAs($admin)->get(route($route, $params))->assertOk()
            ->assertViewHas($variable, fn ($p) => $p->count() === 15 && $p->total() === 20 && str_contains($p->url(2), 'search=CIBLE'));
        $this->get(route($route, $params + ['page' => 2]))->assertOk()
            ->assertViewHas($variable, fn ($p) => $p->count() === 5 && $p->total() === 20
                && $p->getCollection()->modelKeys() === array_slice(array_reverse($ids), 15));
        $this->get(route($route, $params + [$selection => $ids[0]]))->assertOk()
            ->assertViewHas($variable, fn ($p) => $p->currentPage() === 2 && $p->getCollection()->contains('id', $ids[0]));
        $this->get(route($route, ['search' => 'INEXISTANT']))->assertOk()
            ->assertViewHas($variable, fn ($p) => $p->total() === 0);
    }

    public function test_mes_tickets_reste_limite_au_demandeur_y_compris_la_selection(): void
    {
        $personnel = $this->personnel('Premier');
        $autre = $this->personnel('Autre');
        for ($i = 0; $i < 16; $i++) {
            $ticket = Ticket::create(['titre' => 'Mon ticket', 'demandeur_id' => $personnel->id, 'statut' => 'ouvert', 'priorite' => 'normale']);
            $ticket->demande()->create(['type_demande' => 'affectation']);
        }
        $interdit = Ticket::create(['titre' => 'Confidentiel autre', 'demandeur_id' => $autre->id, 'statut' => 'ouvert', 'priorite' => 'normale']);
        $interdit->demande()->create(['type_demande' => 'affectation']);
        $this->actingAs($personnel)->get(route('tickets.index', ['page' => 2]))->assertOk()
            ->assertViewHas('tickets', fn ($p) => $p->total() === 16 && $p->count() === 1)
            ->assertDontSeeText('Confidentiel autre');
        $this->get(route('tickets.index', ['ticket' => $interdit->id]))->assertOk()
            ->assertViewHas('tickets', fn ($p) => $p->currentPage() === 1 && $p->total() === 16)
            ->assertDontSeeText('Confidentiel autre');
    }

    private function personnel(string $nom, string $role = 'personnel'): User
    {
        $user = User::factory()->create(['name' => $nom, 'mot_de_passe_change' => true]);
        Personnel::create(['user_id' => $user->id, 'role' => $role, 'type_contrat' => 'cdi']);
        return $user;
    }
}
