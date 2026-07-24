<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_only_see_seraphotheque_brand_and_link(): void
    {
        $response = $this->get('/seraphotheque');

        $response->assertStatus(200);
        $response->assertSee('Seraphotheque');
        $response->assertDontSee('Tableau de bord');
    }

    public function test_authenticated_users_see_core_mobile_first_navigation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/seraphotheque');

        $response->assertStatus(200);
        $response->assertSee('Arbre Sujets');
        $response->assertSee('Documents');
        $response->assertSee('Tableau de bord');

        $this->assertStringNotContainsString('">Sujets</a>', $response->getContent());
    }

    public function test_mobile_menu_contains_profile_admin_and_logout(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/seraphotheque');

        $response->assertStatus(200);
        $response->assertSee('Mon profil');
        $response->assertSee('Administration');
        $response->assertSee('Deconnexion');
    }

    public function test_mobile_menu_does_not_show_admin_for_non_admin(): void
    {
        $user = User::factory()->create(['role' => 'citoyen']);

        $response = $this->actingAs($user)->get('/seraphotheque');

        $response->assertStatus(200);
        $response->assertSee('Mon profil');
        $response->assertSee('Deconnexion');
        $response->assertDontSee('Administration');
    }
}
