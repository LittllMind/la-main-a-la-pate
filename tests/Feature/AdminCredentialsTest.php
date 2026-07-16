<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCredentialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_admin_has_new_email_and_role(): void
    {
        $this->seed(\Database\Seeders\UserSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'aurelien.tisserand18@gmail.com',
            'role' => 'admin',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'admin@example.com',
        ]);
    }

    public function test_seeded_admin_can_login_with_plain_password(): void
    {
        $this->seed(\Database\Seeders\UserSeeder::class);

        $response = $this->post('/login', [
            'login' => 'aurelien.tisserand18@gmail.com',
            'password' => 'pass',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }
}
