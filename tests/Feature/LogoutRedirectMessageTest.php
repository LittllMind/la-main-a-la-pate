<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutRedirectMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_redirects_to_login_with_success_flash(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect('/');
        $response->assertSessionHas('status', 'Vous êtes déconnecté.');
    }
}
