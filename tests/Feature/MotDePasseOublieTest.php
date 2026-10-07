<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ReinitialiserMotDePasse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class MotDePasseOublieTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_lien_de_reinitialisation_expire_apres_deux_minutes(): void
    {
        $this->assertSame(2, config('auth.passwords.users.expire'));
    }

    public function test_le_formulaire_est_affiche_en_francais(): void
    {
        $this->get('/password/reset')
            ->assertOk()
            ->assertSee('Mot de passe oublie')
            ->assertSee('Envoyer le lien');
    }

    public function test_une_adresse_inconnue_affiche_un_message_explicite(): void
    {
        $this->from('/password/reset')
            ->post('/password/email', ['email' => 'inconnu@efeledu.com'])
            ->assertRedirect('/password/reset')
            ->assertSessionHasErrors([
                'email' => __('passwords.user'),
            ]);
    }

    public function test_un_compte_existant_recoit_le_lien_en_francais(): void
    {
        Notification::fake();
        $utilisateur = User::factory()->create([
            'email' => 'personnel@efeledu.com',
        ]);

        $this->post('/password/email', ['email' => $utilisateur->email])
            ->assertSessionHas('status', __('passwords.sent'));

        Notification::assertSentTo($utilisateur, ReinitialiserMotDePasse::class);
    }

    public function test_un_nouveau_mot_de_passe_valide_reinitialise_le_compte(): void
    {
        $utilisateur = User::factory()->create([
            'name' => 'Rafik Selmi',
            'email' => 'r.selmi@intedgroup.com',
            'password' => 'Ancien1!',
            'mot_de_passe_change' => false,
        ]);
        $token = Password::broker()->createToken($utilisateur);

        $this->post('/password/reset', [
            'token' => $token,
            'email' => $utilisateur->email,
            'password' => 'Nouveau2!',
            'password_confirmation' => 'Nouveau2!',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', __('passwords.reset'));

        $utilisateur->refresh();
        $this->assertTrue(Hash::check('Nouveau2!', $utilisateur->password));
        $this->assertTrue($utilisateur->mot_de_passe_change);
        $this->assertGuest();
    }

    public function test_un_mot_de_passe_faible_est_refuse(): void
    {
        $utilisateur = User::factory()->create([
            'email' => 'personnel@efeledu.com',
        ]);
        $token = Password::broker()->createToken($utilisateur);

        $this->post('/password/reset', [
            'token' => $token,
            'email' => $utilisateur->email,
            'password' => 'faible',
            'password_confirmation' => 'faible',
        ])
            ->assertSessionHasErrors('password');
    }
}
