<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCredentialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_uses_external_credentials(): void
    {
        // Injecter des credentials de test (pas opérationnels)
        config()->set('lmalp.admin_email', 'test-admin@example.test');
        config()->set('lmalp.admin_password', 'test-password-123');

        $this->seed(\Database\Seeders\UserSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'test-admin@example.test',
            'role' => 'admin',
        ]);

        $user = \App\Models\User::where('email', 'test-admin@example.test')->first();
        $this->assertTrue(Hash::check('test-password-123', $user->password));
    }

    public function test_seeder_throws_when_credentials_missing(): void
    {
        config()->set('lmalp.admin_email', null);
        config()->set('lmalp.admin_password', null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('LMALP_ADMIN_EMAIL and LMALP_ADMIN_PASSWORD must be set');

        $this->seed(\Database\Seeders\UserSeeder::class);
    }

    public function test_seeder_throws_when_email_empty(): void
    {
        config()->set('lmalp.admin_email', '');
        config()->set('lmalp.admin_password', 'some-password');

        $this->expectException(\RuntimeException::class);

        $this->seed(\Database\Seeders\UserSeeder::class);
    }

    public function test_seeder_throws_when_password_empty(): void
    {
        config()->set('lmalp.admin_email', 'admin@example.test');
        config()->set('lmalp.admin_password', '');

        $this->expectException(\RuntimeException::class);

        $this->seed(\Database\Seeders\UserSeeder::class);
    }

    public function test_seeded_admin_can_login_with_plain_password(): void
    {
        config()->set('lmalp.admin_email', 'test-admin@example.test');
        config()->set('lmalp.admin_password', 'test-password-123');

        $this->seed(\Database\Seeders\UserSeeder::class);

        $response = $this->post('/login', [
            'login'    => 'test-admin@example.test',
            'password' => 'test-password-123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }
}
