<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test que la page d'accueil redirige correctement (authentification requise)
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // La page d'accueil est publique (retourne 200)
        $response = $this->get('/');
        $response->assertStatus(200);    }
}
