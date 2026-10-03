<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_un_visiteur_est_redirige_vers_la_connexion(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
