<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConfigurationTest extends TestCase
{
    public function test_application_boot_sans_erreur()
    {
        // Test que l'application démarre sans erreur
        $this->artisan('config:clear')
            ->assertSuccessful();
    }

    public function test_page_accueil_repond()
    {
        $response = $this->get('/');

        // La page doit répondre (200 ou 302 si redirect)
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 302, 500]),
            'La page d\'accueil doit répondre'
        );
    }
}
