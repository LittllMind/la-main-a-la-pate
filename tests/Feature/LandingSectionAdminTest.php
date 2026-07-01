<?php

namespace Tests\Feature;

use App\Models\LandingSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingSectionAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_landing_sections(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->update(['email_verified_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.sections.index'));

        $response->assertOk()
            ->assertViewIs('admin.sections.index');
    }

    public function test_non_admin_cannot_list_landing_sections(): void
    {
        $user = User::factory()->create(['role' => 'moderator']);
        $user->update(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get(route('admin.sections.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_create_a_section(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->update(['email_verified_at' => now()]);

        $response = $this->actingAs($admin)->post(route('admin.sections.store'), [
            'key' => 'custom-section',
            'title' => 'Section personnalisée',
            'subtitle' => 'Sous-titre',
            'body' => '<p>Contenu HTML.</p>',
            'position' => 10,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.sections.index'));

        $this->assertDatabaseHas('landing_sections', [
            'key' => 'custom-section',
            'title' => 'Section personnalisée',
        ]);
    }

    public function test_admin_can_update_a_section(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->update(['email_verified_at' => now()]);
        $section = LandingSection::create([
            'key' => 'hero',
            'title' => 'Ancien titre',
            'subtitle' => null,
            'body' => null,
            'position' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.sections.update', $section->id), [
            'key' => 'hero',
            'title' => 'Nouveau titre',
            'subtitle' => 'Nouveau sous-titre',
            'body' => '<p>Nouveau contenu.</p>',
            'position' => 1,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.sections.index'));

        $this->assertDatabaseHas('landing_sections', [
            'id' => $section->id,
            'title' => 'Nouveau titre',
        ]);
    }

    public function test_admin_can_toggle_section_active_state(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->update(['email_verified_at' => now()]);
        $section = LandingSection::create([
            'key' => 'toggleable',
            'title' => 'Toggle me',
            'position' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.sections.toggle', $section->id));

        $response->assertRedirect(route('admin.sections.index'));
        $this->assertDatabaseHas('landing_sections', [
            'id' => $section->id,
            'is_active' => false,
        ]);
    }

    public function test_home_page_displays_active_sections_in_position_order(): void
    {
        LandingSection::create([
            'key' => 'b',
            'title' => 'Deuxième section',
            'position' => 2,
            'is_active' => true,
        ]);
        LandingSection::create([
            'key' => 'a',
            'title' => 'Première section',
            'position' => 1,
            'is_active' => true,
        ]);
        LandingSection::create([
            'key' => 'c',
            'title' => 'Section inactive',
            'position' => 0,
            'is_active' => false,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/hall');

        $response->assertOk()
            ->assertSeeInOrder(['Première section', 'Deuxième section'])
            ->assertDontSee('Section inactive');
    }
}
