<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_own_profile(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($user)->get('/users/' . $user->id);

        $response->assertOk();
        $response->assertViewIs('users.show');
        $response->assertViewHas('user', $user);
    }

    public function test_guest_cannot_access_user_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->get('/users/' . $user->id);

        $response->assertRedirect('/login');
    }

    public function test_user_can_edit_own_profile(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($user)->get('/users/' . $user->id . '/edit');

        $response->assertOk();
        $response->assertViewIs('users.edit');
    }

    public function test_user_can_update_own_profile(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        // Ajouter current_password pour passer la validation
        $params = ['name' => 'Nouveau Nom', 'email' => 'nouveau@email.com', 'current_password' => 'password'];

        $response = $this->actingAs($user)->put('/users/' . $user->id, $params);

        $response->assertRedirect();

        $user->refresh();
        $this->assertEquals('Nouveau Nom', $user->name);
        $this->assertEquals('nouveau@email.com', $user->email);
    }

    public function test_user_cannot_access_other_user_profile(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $otherUser = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($user)->get('/users/' . $otherUser->id);

        $response->assertForbidden();
    }

    public function test_user_cannot_edit_other_user_profile(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $otherUser = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($user)->put('/users/' . $otherUser->id, [
            'name' => 'Hack',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_access_any_user_profile(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($admin)->get('/users/' . $user->id);

        $response->assertOk();
    }
}
