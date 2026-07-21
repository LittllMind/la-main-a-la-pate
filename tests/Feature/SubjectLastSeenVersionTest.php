<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\SubjectVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectLastSeenVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_show_records_last_seen_version(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $subject = Subject::factory()->create(['user_id' => $author->id]);
        $v1 = SubjectVersion::factory()->create([
            'subject_id' => $subject->id,
            'user_id' => $author->id,
            'body' => 'v1',
        ]);

        $this->actingAs($other);
        $response = $this->get(route('subjects.show', $subject->slug));
        $response->assertOk();

        $this->assertDatabaseHas('subject_user_last_seen_versions', [
            'user_id' => $other->id,
            'subject_id' => $subject->id,
            'version_id' => $v1->id,
        ]);
    }

    public function test_subject_lists_shows_updated_badge_for_unseen_version(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $subject = Subject::factory()->create(['user_id' => $author->id]);

        // other user has seen v1
        $v1 = SubjectVersion::factory()->create([
            'subject_id' => $subject->id,
            'user_id' => $author->id,
        ]);
        \App\Models\SubjectUserLastSeenVersion::create([
            'user_id' => $other->id,
            'subject_id' => $subject->id,
            'version_id' => $v1->id,
            'seen_at' => now(),
        ]);

        // author creates v2
        $v2 = SubjectVersion::factory()->create([
            'subject_id' => $subject->id,
            'user_id' => $author->id,
        ]);

        $this->actingAs($other);
        $response = $this->get(route('subjects.index'));
        $response->assertOk()->assertSee('Mis à jour');
    }

    public function test_subject_list_does_not_show_updated_badge_after_user_views_subject(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $subject = Subject::factory()->create(['user_id' => $author->id, 'status' => 'draft', 'visibility' => 'citoyen']);
        $subject->collaborators()->syncWithoutDetaching([$other->id]);

        $v2 = SubjectVersion::factory()->create([
            'subject_id' => $subject->id,
            'user_id' => $author->id,
        ]);

        $this->actingAs($other);
        $this->get(route('subjects.show', $subject->slug))->assertOk();

        $response = $this->get(route('subjects.index'));
        $response->assertOk()->assertDontSee('Mis à jour');
    }
}
