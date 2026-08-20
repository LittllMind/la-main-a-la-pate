<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectDraftPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_subjects_index(): void
    {
        $response = $this->get(route('subjects.index'));

        $response->assertRedirect('/');
    }

    public function test_other_citizen_cannot_see_someone_elses_draft_in_index(): void
    {
        $owner = User::factory()->create(['role' => 'citoyen', 'password' => 'password']);
        $other = User::factory()->create(['role' => 'citoyen', 'password' => 'password']);
        $draft = Subject::factory()->for($owner)->create(['status' => 'draft', 'citizen_status' => 'draft', 'public_status' => 'draft', 'citizen_body' => null, 'public_body' => null]);

        $response = $this->actingAs($other)->get(route('subjects.index'));

        $response->assertOk();
        $response->assertDontSee($draft->title);
    }

    public function test_owner_can_see_their_draft_in_index(): void
    {
        $owner = User::factory()->create(['role' => 'citoyen', 'password' => 'password']);
        $draft = Subject::factory()->for($owner)->create(['status' => 'draft', 'citizen_status' => 'draft', 'public_status' => 'draft', 'citizen_body' => null, 'public_body' => null]);

        $response = $this->actingAs($owner)->get(route('subjects.index'));

        $response->assertOk();
        $response->assertSee($draft->title);
    }

    public function test_moderator_can_see_any_draft_in_index(): void
    {
        $owner = User::factory()->create(['role' => 'citoyen', 'password' => 'password']);
        $moderator = User::factory()->create(['role' => 'moderator', 'password' => 'password']);
        $draft = Subject::factory()->for($owner)->create(['status' => 'draft', 'citizen_status' => 'draft', 'public_status' => 'draft', 'citizen_body' => null, 'public_body' => null]);

        $response = $this->actingAs($moderator)->get(route('subjects.index'));

        $response->assertOk();
        $response->assertSee($draft->title);
    }

    public function test_published_subject_is_visible_to_authenticated_citizen_in_index(): void
    {
        $citizen = User::factory()->create(['role' => 'citoyen', 'password' => 'password']);
        $subject = Subject::factory()->create(['status' => 'published']);

        $response = $this->actingAs($citizen)->get(route('subjects.index'));

        $response->assertOk();
        $response->assertSee($subject->title);
    }

    public function test_other_citizen_cannot_view_someone_elses_draft(): void
    {
        $owner = User::factory()->create(['role' => 'citoyen', 'password' => 'password']);
        $other = User::factory()->create(['role' => 'citoyen', 'password' => 'password']);
        $draft = Subject::factory()->for($owner)->create(['status' => 'draft', 'citizen_status' => 'draft', 'public_status' => 'draft', 'citizen_body' => null, 'public_body' => null]);

        $response = $this->actingAs($other)->get(route('subjects.show', $draft->slug));

        $response->assertNotFound();
    }

    public function test_moderator_can_edit_someone_elses_draft(): void
    {
        $owner = User::factory()->create(['role' => 'citoyen', 'password' => 'password']);
        $moderator = User::factory()->create(['role' => 'moderator', 'password' => 'password']);
        $draft = Subject::factory()->for($owner)->create(['status' => 'draft', 'citizen_status' => 'draft', 'public_status' => 'draft', 'citizen_body' => null, 'public_body' => null]);

        $response = $this->actingAs($moderator)->get(route('subjects.edit', $draft->slug));

        $response->assertOk();
    }

    public function test_other_citizen_cannot_edit_someone_elses_draft(): void
    {
        $owner = User::factory()->create(['role' => 'citoyen', 'password' => 'password']);
        $other = User::factory()->create(['role' => 'citoyen', 'password' => 'password']);
        $draft = Subject::factory()->for($owner)->create(['status' => 'draft', 'citizen_status' => 'draft', 'public_status' => 'draft', 'citizen_body' => null, 'public_body' => null]);

        $response = $this->actingAs($other)->get(route('subjects.edit', $draft->slug));

        $response->assertStatus(403);
    }
}
