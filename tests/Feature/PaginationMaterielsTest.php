<?php

namespace Tests\Feature;

use App\Models\Cable;
use App\Models\Casque;
use App\Models\Clavier;
use App\Models\Ecran;
use App\Models\Imprimante;
use App\Models\Materiel;
use App\Models\MiniPc;
use App\Models\PcPortable;
use App\Models\Personnel;
use App\Models\Souris;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaginationMaterielsTest extends TestCase
{
    use RefreshDatabase;

    public static function pages(): array
    {
        return [
            [PcPortable::class, 'pc-portables.index', 'pcPortables', 'pc_portable'],
            [MiniPc::class, 'mini-pc.index', 'miniPcs', 'mini_pc'],
            [Ecran::class, 'ecrans.index', 'ecrans', 'ecran'],
            [Imprimante::class, 'imprimantes.index', 'imprimantes', 'imprimante'],
            [Clavier::class, 'claviers.index', 'claviers', 'clavier'],
            [Souris::class, 'souris.index', 'souris', 'souris'],
            [Casque::class, 'casques.index', 'casques', 'casque'],
            [Cable::class, 'cables.index', 'cables', 'cable'],
        ];
    }

    #[DataProvider('pages')]
    public function test_pagination_recherche_et_filtres_sur_les_huit_pages(string $classe, string $route, string $variable, string $type): void
    {
        $admin = User::factory()->create(['mot_de_passe_change' => true]);
        Personnel::create(['user_id' => $admin->id, 'role' => 'admin', 'type_contrat' => 'cdi']);
        $attendus = [];
        for ($i = 1; $i <= 21; $i++) {
            $objet = $this->creer($classe, $type, $i, true);
            if ($i <= 20) {
                $attendus[] = $objet->getKey();
            }
        }
        $this->creer($classe, $type, 22, false);
        $filtres = $classe === Cable::class ? ['statut' => 'stock_bas'] : ['etat' => 'disponible'];
        $params = ['search' => 'CIBLE'] + $filtres;

        $this->actingAs($admin)->get(route($route, $params))
            ->assertOk()->assertViewHas($variable, function ($pagination) {
                return $pagination->total() === 20 && $pagination->count() === 15
                    && str_contains($pagination->url(2), 'search=CIBLE');
            })->assertSee('Affichage 1 a 15 sur 20');

        $reponse = $this->get(route($route, $params + ['page' => 2]));
        $reponse->assertOk()->assertViewHas($variable, function ($pagination) use ($attendus, $filtres) {
            return $pagination->count() === 5 && $pagination->total() === 20
                && $pagination->getCollection()->modelKeys() === array_slice($attendus, 15)
                && str_contains($pagination->url(1), http_build_query($filtres));
        })->assertSee('Affichage 16 a 20 sur 20');

        $this->get(route($route, ['search' => 'INEXISTANT']))
            ->assertOk()->assertViewHas($variable, fn ($pagination) => $pagination->total() === 0)
            ->assertSee('Affichage 0 a 0 sur 0');
    }

    private function creer(string $classe, string $type, int $i, bool $cible)
    {
        $numero = ($cible ? 'CIBLE-' : 'AUTRE-').str_pad((string) $i, 3, '0', STR_PAD_LEFT);
        if ($classe === Cable::class) {
            return Cable::create(['reference' => $numero, 'type_cable' => 'HDMI', 'longueur' => '2m',
                'quantite' => 10, 'quantite_disponible' => $i === 21 ? 10 : 2, 'seuil_alerte' => 5]);
        }
        $materiel = Materiel::create(['nom' => $numero, 'marque' => 'Test', 'type_materiel' => $type,
            'etat' => $i === 21 ? 'en_panne' : 'disponible']);
        $specifiques = match ($classe) {
            PcPortable::class, MiniPc::class => ['cpu' => 'Intel i5', 'ram' => '16 Go DDR4', 'stockage' => '512 Go SSD', 'os' => 'Windows 11'],
            Ecran::class => ['taille' => '24', 'resolution' => '1920x1080'],
            Imprimante::class => ['type_impression' => 'Laser', 'connexion' => 'USB'],
            default => ['connexion' => 'USB'],
        };
        return $classe::create(['materiel_id' => $materiel->id, 'numero_serie' => $numero] + $specifiques);
    }
}
