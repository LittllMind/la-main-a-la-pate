<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteMapMindmapTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_site_map(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user);

        $response = $this->get(route('site.map'));
        $response->assertOk()
            ->assertSee('Plan du site')
            ->assertSee('Espace sujets')
            ->assertSee('Communauté')
            ->assertSee('Administration');
    }

    public function test_authenticated_non_admin_cannot_see_admin_section(): void
    {
        $user = User::factory()->create(['role' => 'citoyen']);

        $this->actingAs($user);

        $response = $this->get(route('site.map'));
        $response->assertOk()
            ->assertSee('Plan du site')
            ->assertSee('Espace sujets')
            ->assertSee('Communauté')
            ->assertDontSee('Administration');
    }
}
