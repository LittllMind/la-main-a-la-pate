<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserShortcutsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dashboard_displays_user_shortcuts(): void
    {
        $user = User::factory()->create();
        $user->shortcuts()->create([
            'label' => 'Conseil municipal',
            'url' => '/sujets/categorie/conseil-municipal',
            'icon' => '🏛️',
            'position' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Conseil municipal');
        $response->assertSee('/sujets/categorie/conseil-municipal');
    }

    public function test_user_can_create_a_shortcut(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('shortcuts.store'), [
            'label' => 'Budget 2026',
            'url' => '/documents/budget-2026',
            'icon' => '💶',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('user_shortcuts', [
            'user_id' => $user->id,
            'label' => 'Budget 2026',
            'url' => '/documents/budget-2026',
        ]);
    }

    public function test_user_cannot_create_shortcut_for_another_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $response = $this->actingAs($other)->post(route('shortcuts.store'), [
            'label' => 'Pirate',
            'url' => '/admin',
            'icon' => '💀',
            'user_id' => $user->id,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('user_shortcuts', [
            'user_id' => $user->id,
            'label' => 'Pirate',
        ]);
        $this->assertDatabaseHas('user_shortcuts', [
            'user_id' => $other->id,
            'label' => 'Pirate',
        ]);
    }

    public function test_user_can_delete_own_shortcut(): void
    {
        $user = User::factory()->create();
        $shortcut = $user->shortcuts()->create(['label' => 'A supprimer', 'url' => '/tmp', 'icon' => 'X']);

        $response = $this->actingAs($user)->delete(route('shortcuts.destroy', $shortcut));

        $response->assertRedirect(route('dashboard'));
        $this->assertModelMissing($shortcut);
    }

    public function test_user_cannot_delete_other_users_shortcut(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $shortcut = $user->shortcuts()->create(['label' => 'Protege', 'url' => '/tmp', 'icon' => '🔒']);

        $response = $this->actingAs($other)->delete(route('shortcuts.destroy', $shortcut));

        $response->assertForbidden();
        $this->assertModelExists($shortcut);
    }
}
