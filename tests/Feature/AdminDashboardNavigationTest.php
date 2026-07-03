<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->admin()->create();
        $admin->update(['email_verified_at' => now()]);
        return $admin;
    }

    public function test_admin_panel_displays_user_management_links(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.panel'));

        $response->assertOk();
        $response->assertSee(route('admin.users.index'));
        $response->assertSee(route('admin.users.create'));
        $response->assertSee('Créer un compte');
        $response->assertSee('Utilisateurs');
    }

    public function test_admin_panel_displays_content_management_links(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.panel'));

        $response->assertOk();
        $response->assertSee(route('admin.posts.index'));
        $response->assertSee(route('admin.posts.create'));
        $response->assertSee(route('admin.sections.index'));
        $response->assertSee(route('admin.sections.create'));
        $response->assertSee(route('subjects.index'));
        $response->assertSee(route('subjects.create'));
        $response->assertSee(route('subjects.import.create'));
        $response->assertSee(route('subjects.pdf.index'));
    }

    public function test_admin_panel_displays_community_tools_links(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.panel'));

        $response->assertOk();
        $response->assertSee(route('community.index'));
        $response->assertSee(route('admin.routes'));
        $response->assertSee(url('/'));
    }

    public function test_admin_navbar_highlights_current_section(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.panel'));

        $response->assertOk();
        $response->assertSee('Tableau de bord');
        $response->assertSee('Utilisateurs');
        $response->assertSee('Articles');
        $response->assertSee('Sections');
        $response->assertSee('Sujets');
        $response->assertSee('Routes');
        $response->assertSee('Site public');
    }
}
