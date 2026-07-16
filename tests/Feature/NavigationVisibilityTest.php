<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_only_see_seraphotheque_brand_and_link(): void
    {
        $response = $this->get('/seraphotheque');

        $response->assertStatus(200);
        $response->assertSee('Seraphotheque');
        $response->assertDontSee('Hall');
        $response->assertDontSee('Communaute');
        $response->assertDontSee('Tableau de bord');
    }

    public function test_authenticated_users_see_sujets_and_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/seraphotheque');

        $response->assertStatus(200);
        $response->assertSee('Sujets');
        $response->assertSee('Tableau de bord');
        $response->assertDontSee('Communaute');
    }

    public function test_admin_users_see_dashboard_link_and_admin_entry(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get('/seraphotheque');

        $response->assertStatus(200);
        $response->assertSee('Tableau de bord');
        $response->assertSee('admin');
    }
}
