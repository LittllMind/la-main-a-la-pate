<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileSecurityTest extends TestCase
{
    use RefreshDatabase;

    // ========== TEST ACCÈS PROFIL ==========

    public function test_user_can_access_own_profile(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        
        $response = $this->actingAs($user)->get("/users/{$user->id}");
        
        $response->assertOk();
        $response->assertViewIs('users.show');
    }

    public function test_user_cannot_access_other_user_profile(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $otherUser = User::factory()->create(['role' => 'client']);
        
        $response = $this->actingAs($user)->get("/users/{$otherUser->id}");
        
        $response->assertForbidden(); // 403
    }

    public function test_admin_can_access_any_user_profile(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherUser = User::factory()->create(['role' => 'client']);
        
        $response = $this->actingAs($admin)->get("/users/{$otherUser->id}");
        
        $response->assertOk();
    }

    public function test_employe_can_access_any_user_profile(): void
    {
        $employe = User::factory()->create(['role' => 'employe']);
        $otherUser = User::factory()->create(['role' => 'client']);
        
        $response = $this->actingAs($employe)->get("/users/{$otherUser->id}");
        
        $response->assertOk();
    }

    // ========== TEST ÉDITION PROFIL ==========

    public function test_user_can_edit_own_profile(): void
    {
        $user = User::factory()->create(['role' => 'client', 'name' => 'Ancien Nom']);
        
        $response = $this->actingAs($user)->put("/users/{$user->id}", [
            'name' => 'Nouveau Nom',
            'email' => $user->email,
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nouveau Nom',
        ]);
    }

    public function test_user_cannot_edit_other_user_profile(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $otherUser = User::factory()->create(['role' => 'client', 'name' => 'Nom Original']);
        
        $response = $this->actingAs($user)->put("/users/{$otherUser->id}", [
            'name' => 'Nom Modifié',
            'email' => $otherUser->email,
        ]);
        
        $response->assertForbidden();
        $this->assertDatabaseHas('users', [
            'id' => $otherUser->id,
            'name' => 'Nom Original',
        ]);
    }

    public function test_user_cannot_change_own_role(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        
        $response = $this->actingAs($user)->put("/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'admin', // Tentative d'escalade
        ]);
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'client',
        ]);
    }

    public function test_admin_can_change_user_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'client']);
        
        $response = $this->actingAs($admin)->put("/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'employe',
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'employe',
        ]);
    }

    // ========== TEST PASSWORD ==========

    public function test_user_can_update_own_password(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $oldPassword = $user->password;
        
        $response = $this->actingAs($user)->patch("/users/{$user->id}/password", [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        
        $response->assertRedirect();
        $user->refresh();
        $this->assertNotEquals($oldPassword, $user->password);
    }

    public function test_user_cannot_update_other_user_password(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $otherUser = User::factory()->create(['role' => 'client']);
        $oldPassword = $otherUser->password;
        
        $response = $this->actingAs($user)->patch("/users/{$otherUser->id}/password", [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        
        $response->assertForbidden();
    }

    // ========== TEST IDOR FONDS (si applicable) ==========

    public function test_unauthenticated_user_cannot_access_profile(): void
    {
        $user = User::factory()->create();
        
        $response = $this->get("/users/{$user->id}");
        
        $response->assertRedirect('/login');
    }

    public function test_access_to_nonexistent_user_returns_404(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get('/users/99999');
        
        $response->assertNotFound();
    }
}