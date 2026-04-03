<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_own_profile(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
        ]);

        $response = $this->actingAs($user)->get("/users/{$user->id}");

        $response->assertOk();
    }

    /**
     * Test: Un user ne peut pas voir le profil d'un autre user
     * IDOR Protection
     */
    public function test_user_cannot_view_other_user_profile(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $otherUser = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($user)->get("/users/{$otherUser->id}");

        $response->assertForbidden();
    }

    /**
     * Test: Un admin peut voir n'importe quel profil
     */
    public function test_admin_can_view_any_user_profile(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherUser = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($admin)->get("/users/{$otherUser->id}");

        $response->assertOk();
    }

    /**
     * Test: Un employé peut voir d'autres profils (pour support client)
     */
    public function test_employe_can_view_other_user_profile(): void
    {
        $employe = User::factory()->create(['role' => 'employe']);
        $otherUser = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($employe)->get("/users/{$otherUser->id}");

        $response->assertOk();
    }

    /**
     * Test: Un employé peut voir son propre profil
     */
    public function test_employe_can_view_own_profile(): void
    {
        $employe = User::factory()->create(['role' => 'employe']);

        $response = $this->actingAs($employe)->get("/users/{$employe->id}");

        $response->assertOk();
    }

    /**
     * Test: Un user non connecté est redirigé vers login
     */
    public function test_guest_cannot_view_user_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->get("/users/{$user->id}");

        $response->assertRedirect('/login');
    }

    /**
     * Test: Un user peut modifier son propre profil
     */
    public function test_user_can_update_own_profile(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'name' => 'Ancien Nom',
            'email' => 'ancien@example.com',
        ]);

        $response = $this->actingAs($user)->put("/users/{$user->id}", [
            'name' => 'Nouveau Nom',
            'email' => 'nouveau@example.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nouveau Nom',
            'email' => 'nouveau@example.com',
        ]);
    }

    /**
     * Test: Un user ne peut pas modifier le profil d'un autre
     */
    public function test_user_cannot_update_other_user_profile(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $otherUser = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($user)->put("/users/{$otherUser->id}", [
            'name' => 'Hacked Name',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', [
            'id' => $otherUser->id,
            'name' => 'Hacked Name',
        ]);
    }

    /**
     * Test: Validation email unique (sauf soi-même)
     */
    public function test_user_cannot_use_existing_email(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'email' => 'user@example.com',
        ]);
        $otherUser = User::factory()->create([
            'role' => 'client',
            'email' => 'autre@example.com',
        ]);

        $response = $this->actingAs($user)->put("/users/{$user->id}", [
            'name' => 'Mon Nom',
            'email' => 'autre@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * Test: Aucun user ne peut modifier son rôle (sauf admin)
     */
    public function test_user_cannot_change_own_role(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'name' => 'Client',
        ]);

        $response = $this->actingAs($user)->put("/users/{$user->id}", [
            'name' => 'Client',
            'email' => $user->email,
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'client',
        ]);
    }

    /**
     * Test: Admin peut modifier son propre profil
     */
    public function test_admin_can_update_own_profile(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin Ancien',
        ]);

        $response = $this->actingAs($admin)->put("/users/{$admin->id}", [
            'name' => 'Admin Nouveau',
            'email' => 'admin_new_' . uniqid() . '@test.com',
        ]);

        // Vérifier pas d'erreur 403 (autorisé) ou redirection (succès)
        $this->assertTrue(
            $response->isRedirect() || $response->isSuccessful(),
            'La requête devrait rediriger ou succéder, pas retourner une erreur'
        );
    }
}
