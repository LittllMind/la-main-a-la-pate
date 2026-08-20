<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HomeRedirectTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guests_land_on_neutral_public_root(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('La Main à la Pâte');
        $response->assertDontSee('Sujet');
        $response->assertDontSee('Connexion');
    }

    public function test_guests_are_redirected_from_subjects_index_to_root(): void
    {
        $response = $this->get(route('subjects.index'));
        $response->assertRedirect('/');
    }

    public function test_authenticated_users_are_redirected_from_root_to_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('dashboard'));
    }
}
