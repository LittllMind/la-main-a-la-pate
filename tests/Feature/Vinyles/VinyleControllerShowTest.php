<?php

namespace Tests\Feature\Vinyles;

use App\Models\User;
use App\Models\Vinyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Note: La route vinyles.show n'existe pas (pas de méthode show dans VinyleController)
 * Ce fichier teste uniquement l'édition (edit).
 */
class VinyleControllerShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_vinyle_edit(): void
    {
        $admin = $this->adminUser();
        $vinyle = Vinyle::factory()->create();

        $response = $this->actingAs($admin)->get(route('vinyles.edit', $vinyle));

        $response->assertOk()
            ->assertViewIs('vinyles.form')
            ->assertViewHas('vinyle', $vinyle);
    }

    public function test_employe_can_access_vinyle_edit(): void
    {
        $employe = $this->employeUser();
        $vinyle = Vinyle::factory()->create();

        $response = $this->actingAs($employe)->get(route('vinyles.edit', $vinyle));

        $response->assertOk()
            ->assertViewIs('vinyles.form');
    }

    public function test_client_is_redirected_from_edit(): void
    {
        $client = $this->clientUser();
        $vinyle = Vinyle::factory()->create();

        $response = $this->actingAs($client)->get(route('vinyles.edit', $vinyle));

        // Redirection vers kiosque (middleware role redirige vers kiosque.index)
        $response->assertRedirect(route('kiosque.index'));
    }

    public function test_guest_is_redirected_to_login_for_edit(): void
    {
        $vinyle = Vinyle::factory()->create();

        $response = $this->get(route('vinyles.edit', $vinyle));

        $response->assertRedirect(route('login'));
    }

    public function test_invalid_vinyle_edit_returns_404(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get(route('vinyles.edit', 99999));

        $response->assertNotFound();
    }
}
