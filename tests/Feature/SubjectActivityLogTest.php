<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $author;
    private User $collaborator;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create();
        $this->collaborator = User::factory()->create();
        $this->subject = Subject::factory()->create(['user_id' => $this->author->id]);
        $this->subject->collaborators()->attach($this->collaborator->id);
    }

    public function test_comment_on_subject_is_logged(): void
    {
        $this->actingAs($this->collaborator);

        $response = $this->post(route('subjects.comments.store', $this->subject->slug), [
            'body' => 'Nouveau commentaire de test',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'event_type' => 'comment',
            'entity_type' => 'subject',
            'entity_id' => $this->subject->id,
            'user_id' => $this->collaborator->id,
        ]);
    }

    public function test_add_collaborator_is_logged(): void
    {
        $newUser = User::factory()->create();

        $this->actingAs($this->author);

        $response = $this->post(route('subjects.collaborators.store', $this->subject->slug), [
            'user_id' => $newUser->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'event_type' => 'collaborator_added',
            'entity_type' => 'subject',
            'entity_id' => $this->subject->id,
            'user_id' => $this->author->id,
        ]);
    }

    public function test_remove_collaborator_is_logged(): void
    {
        $this->actingAs($this->author);

        $response = $this->delete(route('subjects.collaborators.destroy', [
            $this->subject->slug,
            $this->collaborator->id,
        ]));

        $response->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'event_type' => 'collaborator_removed',
            'entity_type' => 'subject',
            'entity_id' => $this->subject->id,
            'user_id' => $this->author->id,
        ]);
    }

    public function test_start_publication_vote_is_logged(): void
    {
        $this->actingAs($this->author);

        $response = $this->post(route('subjects.collaborators.startVote', $this->subject->slug));

        $response->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'event_type' => 'vote_started',
            'entity_type' => 'subject',
            'entity_id' => $this->subject->id,
            'user_id' => $this->author->id,
        ]);
    }

    public function test_publication_vote_is_logged(): void
    {
        $this->subject->startPublicationVote();

        $this->actingAs($this->collaborator);

        $response = $this->post(route('subjects.collaborators.vote', $this->subject->slug), [
            'vote' => 'approved',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'event_type' => 'vote_cast',
            'entity_type' => 'subject',
            'entity_id' => $this->subject->id,
            'user_id' => $this->collaborator->id,
        ]);
    }
}
