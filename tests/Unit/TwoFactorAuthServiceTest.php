<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FALaravel\Google2FA;
use Tests\TestCase;

class TwoFactorAuthServiceTest extends TestCase
{
    private TwoFactorAuthService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TwoFactorAuthService::class);
    }

    public function test_il_genere_un_secret_compatible_avec_totp(): void
    {
        $secret = $this->service->genererSecret();

        $this->assertSame(32, strlen($secret));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    public function test_il_verifie_un_code_totp_valide_et_refuse_un_code_invalide(): void
    {
        $secret = $this->service->genererSecret();
        $codeCourant = app(Google2FA::class)->getCurrentOtp($secret);

        $this->assertTrue($this->service->verifierCode($secret, $codeCourant));
        $this->assertFalse($this->service->verifierCode($secret, '000000'));
        $this->assertFalse($this->service->verifierCode($secret, '12345'));
    }

    public function test_il_genere_huit_codes_de_recuperation_uniques_et_les_hache(): void
    {
        $codes = $this->service->genererCodesRecuperation();
        $codesHaches = $this->service->hacherCodesRecuperation($codes);

        $this->assertCount(8, $codes);
        $this->assertCount(8, array_unique($codes));
        $this->assertCount(8, $codesHaches);

        foreach ($codes as $index => $code) {
            $this->assertMatchesRegularExpression('/^[A-Z0-9]{5}-[A-Z0-9]{5}$/', $code);
            $this->assertNotSame($code, $codesHaches[$index]);
            $this->assertTrue(Hash::check($code, $codesHaches[$index]));
        }
    }

    public function test_il_genere_un_qr_code_svg_pour_l_utilisateur(): void
    {
        $utilisateur = new User([
            'name' => 'Utilisateur Test',
            'email' => 'utilisateur@efeledu.com',
        ]);

        $qrCode = $this->service->genererQrCode(
            $utilisateur,
            $this->service->genererSecret()
        );

        $this->assertStringContainsString('<svg', $qrCode);
    }
}
