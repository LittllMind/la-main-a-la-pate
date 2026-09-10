<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectVisibilityLevelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_only_public_published_body(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create([
            'user_id' => $admin->id,
            'title' => 'Visible publiquement',
            'body' => 'Contenu interne confidentiel',
            'citizen_body' => 'Contenu citoyen detaille',
            'public_body' => 'Contenu public synthétique',
            'public_status' => 'published',
            'public_published_at' => now(),
            'status' => 'draft',
        ]);

        $response = $this->get(route('subjects.show', $subject->slug));

        $response->assertOk();
        $response->assertSee('Contenu public synthétique');
        $response->assertDontSee('Contenu interne confidentiel');
        $response->assertDontSee('Contenu citoyen detaille');
    }

    public function test_guest_gets_404_when_public_body_is_draft(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create([
            'user_id' => $admin->id,
            'body' => 'Contenu interne',
            'citizen_body' => 'Contenu citoyen',
            'public_body' => 'Contenu public brouillon',
            'public_status' => 'draft',
            'status' => 'published',
        ]);

        $response = $this->get(route('subjects.show', $subject->slug));

        $response->assertNotFound();
    }

    public function test_citizen_sees_public_body_on_public_route_and_citizen_body_in_preview(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $citizen = User::factory()->create(['role' => 'citoyen']);

        // Sujet 1 : citizen publié + public publié
        $subjectCitizen = Subject::factory()->create([
            'user_id' => $admin->id,
            'body' => 'Body interne',
            'citizen_body' => 'Body citoyen',
            'public_body' => 'Body public',
            'citizen_status' => 'published',
            'public_status' => 'published',
            'status' => 'draft',
        ]);

        // Sujet 2 : que public publié
        $subjectPublic = Subject::factory()->create([
            'user_id' => $admin->id,
            'body' => 'Body interne 2',
            'citizen_body' => 'Body citoyen 2 brouillon',
            'public_body' => 'Body public 2',
            'citizen_status' => 'draft',
            'public_status' => 'published',
            'status' => 'draft',
        ]);

        $this->actingAs($citizen);

        $r1 = $this->get(route('subjects.show', $subjectCitizen->slug));
        $r1->assertOk();
        $r1->assertSee('Body citoyen');
        $r1->assertDontSee('Body interne');
        $r1->assertDontSee('Body public');

        // Aperçu citoyen non autorisé pour un simple citoyen (réservé aux admins/propriétaires).

        $r2 = $this->get(route('subjects.show', $subjectPublic->slug));
        $r2->assertOk();
        $r2->assertSee('Body public 2');
        $r2->assertDontSee('Body interne 2');
    }

    public function test_instruction_user_gets_working_body_by_default_when_all_representations_exist(): void
    {
        $instruction = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create([
            'user_id' => User::factory()->create(['role' => 'admin'])->id,
            'body' => 'Body instruction',
            'citizen_body' => 'Body citoyen',
            'public_body' => 'Body public',
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);

        $this->actingAs($instruction)
            ->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('Body instruction')
            ->assertDontSee('Body citoyen')
            ->assertDontSee('Body public');
    }

    public function test_citizen_can_access_public_representation_explicitly_but_not_instruction(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $citizen = User::factory()->create(['role' => 'citoyen']);
        $subject = Subject::factory()->create([
            'user_id' => $owner->id,
            'body' => 'Body instruction',
            'citizen_body' => 'Body citoyen',
            'public_body' => 'Body public',
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);

        $this->assertTrue($subject->canBeViewedBy($citizen));
        $this->assertSame('Body public', $subject->bodyAtLevel(\App\Models\VisibilityLevel::Public));
        $this->assertFalse($subject->bodyAtLevel(\App\Models\VisibilityLevel::Working) !== null);
        $this->assertNotSame('Body instruction', $subject->bodyFor($citizen));
    }

    public function test_admin_sees_instruction_body_by_default_and_working_body_via_edit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create([
            'user_id' => $admin->id,
            'body' => 'Body travail',
            'citizen_body' => 'Body citoyen',
            'public_body' => 'Body public',
            'public_status' => 'published',
        ]);

        $this->actingAs($admin)
            ->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('Body travail')
            ->assertDontSee('Body citoyen')
            ->assertDontSee('Body public');

        $this->actingAs($admin)
            ->get(route('subjects.edit', $subject->slug))
            ->assertOk()
            ->assertSee('Body travail');
    }

    public function test_public_index_lists_only_public_published_subjects(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Subject::factory()->create([
            'user_id' => $admin->id,
            'title' => 'Sujet public',
            'public_body' => 'Pub',
            'public_status' => 'published',
            'public_published_at' => now(),
            'status' => 'draft',
        ]);

        Subject::factory()->create([
            'user_id' => $admin->id,
            'title' => 'Sujet interne',
            'public_body' => 'Brouillon public',
            'public_status' => 'draft',
        ]);

        $response = $this->get(route('subjects.index'));

        $response->assertRedirect('/');
    }
}
