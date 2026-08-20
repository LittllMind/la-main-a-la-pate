<?php

namespace Tests\Feature;

use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchUiTest extends TestCase
{
    /**
     * @test
     */
    public function search_input_is_visible_in_navbar_for_authenticated_users(): void
    {
        $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Rechercher');
    }

    /**
     * @test
     */
    public function search_input_is_hidden_for_guests(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $response->assertDontSee('/recherche');
    }

    /**
     * @test
     */
    public function search_page_returns_html_with_results(): void
    {
        $this->artisan('migrate:fresh')->assertSuccessful();
        $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);
        Subject::factory()->create(['title' => 'Budget 2026', 'body' => 'texte']);

        $response = $this->actingAs($user)->get('/recherche?q=budget');
        $response->assertOk();
        $response->assertSee('Budget 2026');
        $response->assertSee('Recherche');
    }

    /**
     * @test
     */
    public function search_page_shows_empty_state(): void
    {
        $this->artisan('migrate:fresh')->assertSuccessful();
        $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get('/recherche?q=terme_inexistant_xyz');
        $response->assertOk();
        $response->assertSee('Aucun resultat');
    }

    /**
     * @test
     */
    public function short_query_redirects_home(): void
    {
        $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);
        $response = $this->actingAs($user)->get('/recherche?q=a');
        $response->assertRedirect('/');
    }
}
