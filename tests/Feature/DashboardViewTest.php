<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\SubjectComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_dashboard_displays_user_created_subjects(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $user->id, 'title' => 'Mon sujet test']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Tableau de bord');
        $response->assertSee('Mon sujet test');
    }

    public function test_dashboard_displays_user_recent_comments(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $subject = Subject::factory()->create();
        $comment = SubjectComment::factory()->create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'body' => 'Mon commentaire de test',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Mon commentaire de test');
    }

    public function test_dashboard_shows_quick_links(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('/sujets');
        $response->assertSee('/hall');
        $response->assertSee('/profile');
    }

    public function test_dashboard_shows_admin_link_only_for_admins(): void
    {
        $admin = User::factory()->admin()->create(['email_verified_at' => now()]);
        $citizen = User::factory()->create(['email_verified_at' => now()]);

        $adminResponse = $this->actingAs($admin)->get('/dashboard');
        $adminResponse->assertSee('/admin');

        $citizenResponse = $this->actingAs($citizen)->get('/dashboard');
        $citizenResponse->assertDontSee('/admin');
    }

    public function test_dashboard_does_not_show_other_users_subjects(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create();
        Subject::factory()->create(['user_id' => $otherUser->id, 'title' => 'Sujet d un autre']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Mes sujets');
        $response->assertDontSee('Sujet d un autre');
    }

    public function test_dashboard_displays_subject_status_label(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $draft = Subject::factory()->create(['user_id' => $user->id, 'title' => 'Brouillon test', 'status' => 'draft']);
        $published = Subject::factory()->create(['user_id' => $user->id, 'title' => 'Public test', 'status' => 'published']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Brouillon');
        $response->assertSee('Publie');
    }
}
