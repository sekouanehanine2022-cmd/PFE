<?php

namespace Tests\Feature;

use App\Models\Personnel;
use App\Services\AffectationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AffectationDateFinTest extends TestCase
{
    public function test_cdi_ne_demande_pas_de_date_de_fin(): void
    {
        $personnel = new Personnel(['type_contrat' => 'cdi']);
        $request = Request::create('/', 'POST');

        $this->assertNull(app(AffectationService::class)->validerDateFinCreationMateriel($request, $personnel));
    }

    public function test_cdd_et_alternant_demandent_une_date_de_fin(): void
    {
        foreach (['cdd', 'alternant_interne'] as $typeContrat) {
            $personnel = new Personnel(['type_contrat' => $typeContrat]);
            $request = Request::create('/', 'POST');

            try {
                app(AffectationService::class)->validerDateFinCreationMateriel($request, $personnel);
                $this->fail('La date de fin devrait etre obligatoire pour ' . $typeContrat);
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('date_fin', $exception->errors());
            }
        }
    }

    public function test_date_valide_et_date_passee(): void
    {
        $personnel = new Personnel(['type_contrat' => 'cdd']);
        $dateValide = now()->addDay()->toDateString();
        $requestValide = Request::create('/', 'POST', ['date_fin' => $dateValide]);

        $this->assertSame($dateValide, app(AffectationService::class)->validerDateFinCreationMateriel($requestValide, $personnel));

        $requestPassee = Request::create('/', 'POST', ['date_fin' => now()->subDay()->toDateString()]);
        $this->expectException(ValidationException::class);
        app(AffectationService::class)->validerDateFinCreationMateriel($requestPassee, $personnel);
    }
}
