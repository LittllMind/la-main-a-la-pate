<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->admin()->create();
        $admin->update(['email_verified_at' => now()]);

        return $admin;
    }

    public function test_admin_can_list_users(): void
    {
        $admin = $this->admin();
        User::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk()
            ->assertViewIs('admin.users.index');
    }

    public function test_non_admin_cannot_list_users(): void
    {
        $user = User::factory()->create(['role' => 'citoyen']);
        $user->update(['email_verified_at' => now()]);

        $this->actingAs($user)->get(route('admin.users.index'))
            ->assertStatus(403);
    }

    public function test_admin_can_create_user(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Jean Dupont',
            'pseudonyme' => 'JeanDuRozier',
            'email' => 'jean@example.com',
            'commune' => 'Le Rozier',
            'role' => 'citoyen',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'jean@example.com',
            'role' => 'citoyen',
            'commune' => 'Le Rozier',
        ]);

        $user = User::where('email', 'jean@example.com')->first();
        $this->assertTrue(Hash::check('Password123!', $user->password));
    }

    public function test_admin_can_update_user_email_and_role(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['role' => 'member']);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'pseudonyme' => $target->pseudonyme,
            'email' => 'changed@example.com',
            'commune' => $target->commune,
            'role' => 'moderator',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'email' => 'changed@example.com',
            'role' => 'moderator',
            'email_verified_at' => null,
        ]);
    }

    public function test_admin_can_reset_user_password(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create();
        $old = $target->password;

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'pseudonyme' => $target->pseudonyme,
            'email' => $target->email,
            'commune' => $target->commune,
            'role' => $target->role,
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ]);

        $target->refresh();
        $this->assertTrue(Hash::check('NewPass123!', $target->password));
        $this->assertFalse(Hash::check('password', $target->password));
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

        $response->assertRedirect();
        $this->assertTrue(session()->has('error'));

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_delete_another_user(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $target));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertRedirect('/login');

        $this->post('/register', [
            'name' => 'Hacker',
            'pseudonyme' => 'hacker',
            'email' => 'hacker@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'rgpd_consent' => '1',
        ])->assertRedirect('/login');

        $this->assertDatabaseMissing('users', ['email' => 'hacker@example.com']);
    }
}
