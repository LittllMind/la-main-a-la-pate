<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function admin_can_view_users_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(3)->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index'));

        $response->assertOk()
            ->assertViewIs('admin.users.index')
            ->assertViewHas('users');
    }

    /** @test */
    public function employe_cannot_view_users_list(): void
    {
        $employe = User::factory()->create(['role' => 'employe']);

        $response = $this->actingAs($employe)
            ->get(route('admin.users.index'));

        $response->assertRedirect();
    }

    /** @test */
    public function client_cannot_view_users_list(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($client)
            ->get(route('admin.users.index'));

        $response->assertRedirect();
    }

    /** @test */
    public function guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.users.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function admin_can_create_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $userData = [
            'name' => 'Nouveau User',
            'email' => 'nouveau@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'employe',
        ];

        $response = $this->actingAs($admin)
            ->post(route('admin.users.store'), $userData);

        $response->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => 'Nouveau User',
            'email' => 'nouveau@example.com',
            'role' => 'employe',
        ]);
    }

    /** @test */
    public function admin_cannot_create_user_with_invalid_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $userData = [
            'name' => 'Nouveau User',
            'email' => 'email-invalide',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'employe',
        ];

        $response = $this->actingAs($admin)
            ->post(route('admin.users.store'), $userData);

        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseMissing('users', ['name' => 'Nouveau User']);
    }

    /** @test */
    public function admin_cannot_create_user_with_duplicate_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['email' => 'existant@example.com']);

        $userData = [
            'name' => 'Nouveau User',
            'email' => 'existant@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'employe',
        ];

        $response = $this->actingAs($admin)
            ->post(route('admin.users.store'), $userData);

        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function admin_can_edit_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'employe']);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.edit', $user));

        $response->assertOk()
            ->assertViewIs('admin.users.edit')
            ->assertViewHas('user', $user);
    }

    /** @test */
    public function admin_can_update_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'name' => 'Ancien Nom',
            'role' => 'client',
        ]);

        $updateData = [
            'name' => 'Nouveau Nom',
            'email' => $user->email,
            'role' => 'employe',
        ];

        $response = $this->actingAs($admin)
            ->put(route('admin.users.update', $user), $updateData);

        $response->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nouveau Nom',
            'role' => 'employe',
        ]);
    }

    /** @test */
    public function admin_can_delete_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        $response = $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user));

        $response->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** @test */
    public function admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin));

        $response->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    /** @test */
    public function users_list_is_paginated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(25)->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index'));

        $response->assertOk()
            ->assertViewHas('users')
            ->assertViewHas('users', function ($users) {
                return $users->count() <= 15; // Pagination par défaut
            });
    }
}
