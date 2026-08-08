<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavbarLogoutDesktopTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_sees_logout_outside_mobile_menu(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get('/dashboard');
        $html = $response->getContent();

        preg_match_all('/Deconnexion/', $html, $matches);
        $this->assertGreaterThanOrEqual(
            2,
            count($matches[0]),
            'La deconnexion doit exister dans la navbar desktop ET dans le menu mobile.'
        );
    }
}
