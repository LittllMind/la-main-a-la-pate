<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardActivityFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_recent_activity_feed(): void
    {
        $user = User::factory()->create();
        $subject = Subject::factory()->create(['user_id' => $user->id]);

        ActivityLog::log(
            event: 'create',
            user: $user,
            entityType: 'subject',
            entityId: $subject->id,
            description: "Création du sujet « {$subject->title} »"
        );

        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk()
            ->assertSee('Activité récente')
            ->assertSee($subject->title);
    }
}
