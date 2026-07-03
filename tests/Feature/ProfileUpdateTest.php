<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_own_profile_information(): void
    {
        $user = User::factory()->create(['role' => 'citoyen']);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Nouveau Nom',
            'pseudonyme' => 'nouveauPseudo',
            'email' => 'newemail@example.com',
            'commune' => 'Autre Commune',
        ]);

        $response->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertEquals('Nouveau Nom', $user->name);
        $this->assertEquals('nouveauPseudo', $user->pseudonyme);
        $this->assertEquals('newemail@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_user_cannot_update_email_to_existing_one(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'pseudonyme' => $user->pseudonyme,
            'email' => $other->email,
        ])->assertSessionHasErrors('email');
    }
}
