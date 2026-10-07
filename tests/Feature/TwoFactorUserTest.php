<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TwoFactorUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_secret_et_les_codes_de_recuperation_sont_chiffres_et_masques(): void
    {
        $utilisateur = User::factory()->create();
        $utilisateur->forceFill([
            'two_factor_secret' => 'SECRET-TRES-SENSIBLE',
            'two_factor_recovery_codes' => ['CODE-1111', 'CODE-2222'],
            'two_factor_confirmed_at' => now(),
        ])->save();

        $donneesBrutes = DB::table('users')->where('id', $utilisateur->id)->first();

        $this->assertNotSame('SECRET-TRES-SENSIBLE', $donneesBrutes->two_factor_secret);
        $this->assertStringNotContainsString('CODE-1111', $donneesBrutes->two_factor_recovery_codes);

        $utilisateur->refresh();
        $this->assertSame('SECRET-TRES-SENSIBLE', $utilisateur->two_factor_secret);
        $this->assertSame(['CODE-1111', 'CODE-2222'], $utilisateur->two_factor_recovery_codes);
        $this->assertTrue($utilisateur->doubleAuthentificationActive());
        $this->assertArrayNotHasKey('two_factor_secret', $utilisateur->toArray());
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $utilisateur->toArray());
    }

    public function test_la_double_authentification_reste_inactive_tant_qu_elle_n_est_pas_confirmee(): void
    {
        $utilisateur = User::factory()->create();
        $utilisateur->forceFill([
            'two_factor_secret' => 'SECRET-EN-ATTENTE',
            'two_factor_confirmed_at' => null,
        ]);

        $this->assertFalse($utilisateur->doubleAuthentificationActive());
    }

    public function test_un_code_de_recuperation_ne_peut_etre_utilise_qu_une_fois(): void
    {
        $utilisateur = User::factory()->create();
        $service = app(TwoFactorAuthService::class);
        $codes = $service->regenererCodesRecuperation($utilisateur);

        $this->assertTrue($service->utiliserCodeRecuperation($utilisateur, $codes[0]));
        $this->assertFalse($service->utiliserCodeRecuperation($utilisateur->fresh(), $codes[0]));
        $this->assertCount(7, $utilisateur->fresh()->two_factor_recovery_codes);
    }

    public function test_la_desactivation_efface_toutes_les_donnees_2fa(): void
    {
        $utilisateur = User::factory()->create();
        $utilisateur->forceFill([
            'two_factor_secret' => 'SECRET-A-EFFACER',
            'two_factor_recovery_codes' => ['code-hache'],
            'two_factor_confirmed_at' => now(),
        ])->save();

        app(TwoFactorAuthService::class)->desactiver($utilisateur);

        $utilisateur->refresh();
        $this->assertNull($utilisateur->two_factor_secret);
        $this->assertNull($utilisateur->two_factor_recovery_codes);
        $this->assertNull($utilisateur->two_factor_confirmed_at);
        $this->assertFalse($utilisateur->doubleAuthentificationActive());
    }
}
