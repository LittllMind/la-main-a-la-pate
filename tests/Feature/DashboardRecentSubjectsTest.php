<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\SubjectComment;
use App\Models\SubjectVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRecentSubjectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_lists_recently_active_subjects(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();

        $oldestSubject = Subject::factory()->create([
            'user_id' => $author->id,
            'title' => 'Sujet sans activite recente',
            'updated_at' => now()->subDays(10),
        ]);

        $versionedSubject = Subject::factory()->create([
            'user_id' => $author->id,
            'title' => 'Sujet avec version recente',
            'updated_at' => now()->subDays(5),
        ]);
        SubjectVersion::factory()->create([
            'subject_id' => $versionedSubject->id,
            'user_id' => $author->id,
            'created_at' => now()->subHour(),
        ]);

        $commentedSubject = Subject::factory()->create([
            'user_id' => $author->id,
            'title' => 'Sujet avec commentaire recent',
            'updated_at' => now()->subDays(2),
        ]);
        SubjectComment::factory()->create([
            'subject_id' => $commentedSubject->id,
            'user_id' => $author->id,
            'created_at' => now()->subMinutes(30),
        ]);

        $response = $this->actingAs($viewer)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Derniers sujets actualises');
        $response->assertSeeInOrder([
            'Sujet avec commentaire recent',
            'Sujet avec version recente',
            'Sujet sans activite recente',
        ]);
    }

    public function test_dashboard_recent_subjects_limit_is_respected(): void
    {
        $user = User::factory()->create();
        Subject::factory()->count(8)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $this->assertCount(5, $response->viewData('recentSubjects'));
    }

    public function test_dashboard_does_not_list_subject_hidden_from_viewer(): void
    {
        $viewer = User::factory()->create(['role' => 'citoyen']);
        $author = User::factory()->create(['role' => 'admin']);

        Subject::factory()->create([
            'user_id' => $author->id,
            'title' => 'Sujet visible dashboard',
            'citizen_body' => 'Contenu citoyen',
            'citizen_status' => 'published',
            'updated_at' => now(),
        ]);
        Subject::factory()->create([
            'user_id' => $author->id,
            'title' => 'Sujet interdit dashboard',
            'body' => 'Contenu instruction',
            'citizen_body' => null,
            'public_body' => null,
            'updated_at' => now()->addMinute(),
        ]);

        $response = $this->actingAs($viewer)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Sujet visible dashboard');
        $response->assertDontSee('Sujet interdit dashboard');
    }

    public function test_admin_dashboard_excludes_archived_subject_but_direct_access_remains_available(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create(['role' => 'admin']);

        $activeSubject = Subject::factory()->create([
            'user_id' => $author->id,
            'title' => 'Sujet actif dashboard',
            'updated_at' => now()->subMinute(),
        ]);
        $archivedSubject = Subject::factory()->create([
            'user_id' => $author->id,
            'title' => 'Sujet archive dashboard',
            'status' => 'archived',
            'updated_at' => now(),
        ]);

        $dashboard = $this->actingAs($admin)->get(route('dashboard'));

        $dashboard->assertOk()
            ->assertSee($activeSubject->title)
            ->assertDontSee($archivedSubject->title);

        $this->get(route('subjects.show', $archivedSubject->slug))
            ->assertOk()
            ->assertSee($archivedSubject->title);
    }
}
