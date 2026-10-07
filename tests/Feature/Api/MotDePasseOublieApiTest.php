<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Notifications\CodeReinitialisationMotDePasseMobile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MotDePasseOublieApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_code_est_envoye_a_un_compte_existant(): void
    {
        Notification::fake();
        $utilisateur = User::factory()->create(['email' => 'mobile@efeledu.com']);

        $this->postJson('/api/mot-de-passe/code', ['email' => 'MOBILE@EFELEDU.COM'])
            ->assertOk()
            ->assertJsonPath('expires_in_minutes', 2);

        Notification::assertSentTo($utilisateur, CodeReinitialisationMotDePasseMobile::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $utilisateur->email]);
    }

    public function test_une_adresse_inconnue_est_refusee(): void
    {
        $this->postJson('/api/mot-de-passe/code', ['email' => 'inconnu@efeledu.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_un_code_valide_reinitialise_le_mot_de_passe(): void
    {
        Notification::fake();
        $utilisateur = User::factory()->create([
            'name' => 'Utilisateur Mobile',
            'email' => 'mobile@efeledu.com',
            'password' => 'Ancien1!',
            'mot_de_passe_change' => false,
        ]);
        $code = null;

        $this->postJson('/api/mot-de-passe/code', ['email' => $utilisateur->email])->assertOk();
        Notification::assertSentTo(
            $utilisateur,
            CodeReinitialisationMotDePasseMobile::class,
            function (CodeReinitialisationMotDePasseMobile $notification) use (&$code): bool {
                $code = $notification->code;

                return true;
            }
        );

        $this->postJson('/api/mot-de-passe/reinitialiser', [
            'email' => $utilisateur->email,
            'code' => $code,
            'password' => 'Nouveau2!',
            'password_confirmation' => 'Nouveau2!',
        ])->assertOk();

        $utilisateur->refresh();
        $this->assertTrue(Hash::check('Nouveau2!', $utilisateur->password));
        $this->assertTrue($utilisateur->mot_de_passe_change);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $utilisateur->email]);
    }

    public function test_un_code_expire_est_refuse(): void
    {
        $utilisateur = User::factory()->create(['email' => 'mobile@efeledu.com']);
        DB::table('password_reset_tokens')->insert([
            'email' => $utilisateur->email,
            'token' => Hash::make('123456'),
            'created_at' => now()->subMinutes(3),
        ]);

        $this->postJson('/api/mot-de-passe/reinitialiser', [
            'email' => $utilisateur->email,
            'code' => '123456',
            'password' => 'Nouveau2!',
            'password_confirmation' => 'Nouveau2!',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }
}
