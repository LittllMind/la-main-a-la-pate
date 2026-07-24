<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HomeRedirectTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guests_land_on_public_home(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Seraphotheque');
    }

    public function test_authenticated_users_are_redirected_from_root_to_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('dashboard'));
    }
}
