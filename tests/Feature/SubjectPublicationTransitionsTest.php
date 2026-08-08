<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectPublicationTransitionsTest extends TestCase
{
    use RefreshDatabase;

    private function createSubjectWithBodies(): Subject
    {
        return Subject::factory()->create([
            'body' => 'WORKING_SECRET_8F93X',
            'citizen_body' => 'CITIZEN_SECRET_72ABC',
            'public_body' => 'PUBLIC_VISIBLE_39ZZ',
            'status' => 'draft',
            'citizen_status' => 'draft',
            'public_status' => 'draft',
        ]);
    }

    public function test_admin_can_publish_public_version_when_body_present(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = $this->createSubjectWithBodies();

        $this->actingAs($admin)
            ->patch(route('subjects.publish.public', $subject->slug))
            ->assertRedirect(route('subjects.edit', $subject->slug));

        $subject->refresh();
        $this->assertEquals('published', $subject->public_status);
        $this->assertNotNull($subject->public_published_at);
        $this->assertEquals('draft', $subject->citizen_status);
        $this->assertEquals('draft', $subject->status);
    }

    public function test_admin_can_publish_citizen_version_when_body_present(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = $this->createSubjectWithBodies();

        $this->actingAs($admin)
            ->patch(route('subjects.publish.citizen', $subject->slug))
            ->assertRedirect(route('subjects.edit', $subject->slug));

        $subject->refresh();
        $this->assertEquals('published', $subject->citizen_status);
        $this->assertNotNull($subject->citizen_published_at);
        $this->assertEquals('draft', $subject->public_status);
    }

    public function test_cannot_publish_public_version_with_empty_body(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create([
            'body' => 'WORKING_SECRET_8F93X',
            'citizen_body' => 'CITIZEN_SECRET_72ABC',
            'public_body' => '',
            'public_status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->from(route('subjects.edit', $subject->slug))
            ->patch(route('subjects.publish.public', $subject->slug))
            ->assertRedirect(route('subjects.edit', $subject->slug))
            ->assertSessionHas('error', 'La version publique ne peut pas être publiée sans contenu.');

        $subject->refresh();
        $this->assertEquals('draft', $subject->public_status);
    }

    public function test_cannot_publish_citizen_version_with_empty_body(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create([
            'body' => 'WORKING_SECRET_8F93X',
            'citizen_body' => '',
            'public_body' => 'PUBLIC_VISIBLE_39ZZ',
            'citizen_status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->from(route('subjects.edit', $subject->slug))
            ->patch(route('subjects.publish.citizen', $subject->slug))
            ->assertRedirect(route('subjects.edit', $subject->slug))
            ->assertSessionHas('error', 'La version citoyenne ne peut pas être publiée sans contenu.');

        $subject->refresh();
        $this->assertEquals('draft', $subject->citizen_status);
    }

    public function test_admin_can_hide_public_version_preserving_body(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = $this->createSubjectWithBodies();

        $this->actingAs($admin)->patch(route('subjects.publish.public', $subject->slug));
        $this->actingAs($admin)->patch(route('subjects.hide.public', $subject->slug));

        $subject->refresh();
        $this->assertEquals('hidden', $subject->public_status);
        $this->assertEquals('PUBLIC_VISIBLE_39ZZ', $subject->public_body);
        $this->assertNotNull($subject->public_published_at);
    }

    public function test_admin_can_hide_citizen_version_preserving_body(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = $this->createSubjectWithBodies();

        $this->actingAs($admin)->patch(route('subjects.publish.citizen', $subject->slug));
        $this->actingAs($admin)->patch(route('subjects.hide.citizen', $subject->slug));

        $subject->refresh();
        $this->assertEquals('hidden', $subject->citizen_status);
        $this->assertEquals('CITIZEN_SECRET_72ABC', $subject->citizen_body);
    }

    public function test_other_citizen_cannot_publish_public_version(): void
    {
        $owner = User::factory()->create(['role' => 'citoyen']);
        $other = User::factory()->create(['role' => 'citoyen']);
        $subject = Subject::factory()->for($owner)->create([
            'public_body' => 'PUBLIC_VISIBLE_39ZZ',
            'public_status' => 'draft',
        ]);

        $this->actingAs($other)
            ->patch(route('subjects.publish.public', $subject->slug))
            ->assertForbidden();

        $subject->refresh();
        $this->assertEquals('draft', $subject->public_status);
    }

    public function test_owner_can_publish_and_hide_own_subject_versions(): void
    {
        $owner = User::factory()->create(['role' => 'citoyen']);
        $subject = $this->createSubjectWithBodies();
        $subject->update(['user_id' => $owner->id]);

        $this->actingAs($owner)->patch(route('subjects.publish.public', $subject->slug));
        $subject->refresh();
        $this->assertEquals('published', $subject->public_status);

        $this->actingAs($owner)->patch(route('subjects.hide.public', $subject->slug));
        $subject->refresh();
        $this->assertEquals('hidden', $subject->public_status);
    }

    public function test_updating_one_body_does_not_change_other_version_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = $this->createSubjectWithBodies();
        $this->actingAs($admin)->patch(route('subjects.publish.public', $subject->slug));
        $this->actingAs($admin)->patch(route('subjects.publish.citizen', $subject->slug));

        // Met à jour uniquement le body de travail
        $this->actingAs($admin)
            ->from(route('subjects.edit', $subject->slug))
            ->put(route('subjects.update', $subject->slug), [
                'title' => $subject->title,
                'theme' => $subject->theme,
                'body' => 'NOUVEAU_WORKING_SECRET_77XXX',
            ]);

        $subject->refresh();
        $this->assertEquals('NOUVEAU_WORKING_SECRET_77XXX', $subject->body);
        $this->assertEquals('published', $subject->public_status);
        $this->assertEquals('published', $subject->citizen_status);
    }
}
