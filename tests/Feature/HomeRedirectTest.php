<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_is_seraphotheque(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('La Séraphothèque');
        $response->assertDontSee('Hall du Rozier');
    }

    public function test_hall_route_is_hidden_for_guests(): void
    {
        $response = $this->get('/hall');

        $response->assertRedirect('/login');
    }

    public function test_hall_route_is_visible_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/hall');

        $response->assertStatus(200);
        $response->assertSee('Hall du Rozier');
    }
}
