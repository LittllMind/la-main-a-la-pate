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
}
