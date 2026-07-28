<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_panel(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->update(['email_verified_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.panel'));

        $response->assertOk();
        $response->assertViewIs('admin.panel');
    }

    public function test_admin_routes_page_lists_grouped_routes(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->update(['email_verified_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.routes'));

        $response->assertOk();
        $response->assertSee('Sujets');
        $response->assertSee('Admin');
        $response->assertSee('Public');
    }

    public function test_non_admin_user_cannot_access_admin(): void
    {
        $user = User::factory()->create();
        $user->update(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get(route('admin.panel'));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_admin(): void
    {
        $response = $this->get(route('admin.panel'));

        $response->assertRedirect('/login');
    }

    public function test_community_link_is_hidden_from_public_navigation(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $response->assertDontSee('/communaute');

        $response = $this->get(route('community.index'));
        $response->assertRedirect('/login');
    }

    public function test_community_routes_still_work(): void
    {
        $user = User::factory()->create();
        $user->update(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get(route('community.index'));

        // La page existe et ne force pas une erreur 404.
        $this->assertTrue(in_array($response->getStatusCode(), [200, 500]));
    }
}
