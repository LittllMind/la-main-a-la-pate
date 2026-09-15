<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminSubjectAccessTest extends TestCase
{
    use RefreshDatabase;

    private Subject $privateSubject;
    private SubjectDocument $privateDocument;

    protected function setUp(): void
    {
        parent::setUp();

        $this->privateSubject = Subject::factory()->create([
            'title' => 'Synthetic Super Admin Private Subject',
            'slug' => 'test-super-admin-private',
            'access_level' => 'super_admin_only',
            'body' => 'Synthetic working content.',
            'citizen_body' => 'Synthetic citizen content.',
            'public_body' => 'Synthetic public content.',
            'status' => 'published',
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);

        $this->privateDocument = SubjectDocument::factory()->create([
            'subject_id' => $this->privateSubject->id,
            'visibility' => 'working',
            'title' => 'Synthetic private working document',
        ]);

        ActivityLog::create([
            'user_id' => $this->privateSubject->user_id,
            'event_type' => 'update',
            'entity_type' => 'subject',
            'entity_id' => $this->privateSubject->id,
            'description' => 'Synthetic Super Admin Private Subject activity',
        ]);
    }

    public function test_only_super_admin_can_view_private_subject_and_working_documents(): void
    {
        $superAdmin = $this->user('super_admin');
        $this->actingAs($superAdmin)
            ->get(route('subjects.show', $this->privateSubject->slug))
            ->assertOk()
            ->assertSee($this->privateSubject->title);

        $this->actingAs($superAdmin)
            ->get(route('subjects.documents.index', $this->privateSubject->slug))
            ->assertOk()
            ->assertSee($this->privateDocument->title);

        $this->assertTrue($this->privateDocument->fresh()->visibleTo($superAdmin));

        foreach (['admin', 'moderator', 'citoyen'] as $role) {
            $user = $this->user($role);

            $this->actingAs($user)
                ->get(route('subjects.show', $this->privateSubject->slug))
                ->assertNotFound();

            $this->actingAs($user)
                ->get(route('subjects.documents.index', $this->privateSubject->slug))
                ->assertForbidden();

            $this->assertFalse($this->privateDocument->fresh()->visibleTo($user));
        }

        $this->get(route('subjects.show', $this->privateSubject->slug))
            ->assertNotFound();
        $this->get(route('subjects.documents.index', $this->privateSubject->slug))
            ->assertForbidden();
        $this->assertFalse($this->privateDocument->fresh()->visibleTo(null));
    }

    public function test_only_super_admin_can_preview_or_manage_private_subject(): void
    {
        $superAdmin = $this->user('super_admin');
        $this->actingAs($superAdmin)
            ->get(route('subjects.preview', [$this->privateSubject->slug, 'public']))
            ->assertOk();
        $this->actingAs($superAdmin)
            ->get(route('subjects.edit', $this->privateSubject->slug))
            ->assertOk();

        foreach (['admin', 'moderator', 'citoyen'] as $role) {
            $this->actingAs($this->user($role))
                ->get(route('subjects.preview', [$this->privateSubject->slug, 'public']))
                ->assertForbidden();
            $this->actingAs($this->user($role))
                ->get(route('subjects.edit', $this->privateSubject->slug))
                ->assertForbidden();
        }
    }

    public function test_private_subject_isolated_from_lists_search_tree_and_dashboard(): void
    {
        foreach (['admin', 'moderator', 'citoyen'] as $role) {
            $user = $this->user($role);

            $this->actingAs($user)
                ->get(route('subjects.index'))
                ->assertOk()
                ->assertDontSee($this->privateSubject->title)
                ->assertDontSee($this->privateSubject->slug);

            $this->actingAs($user)
                ->getJson(route('search', ['q' => 'private']))
                ->assertOk()
                ->assertJsonMissing(['slug' => $this->privateSubject->slug]);

            $this->actingAs($user)
                ->getJson(route('subjects.tree.data'))
                ->assertOk()
                ->assertJsonMissing(['slug' => $this->privateSubject->slug]);

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertDontSee($this->privateSubject->title)
                ->assertDontSee($this->privateSubject->slug);
        }

        $this->actingAs($this->user('admin'))
            ->get(route('admin.activity'))
            ->assertOk()
            ->assertDontSee($this->privateSubject->title);
        $this->actingAs($this->user('super_admin'))
            ->get(route('subjects.index'))
            ->assertOk()
            ->assertSee($this->privateSubject->title);

        $this->actingAs($this->user('super_admin'))
            ->getJson(route('search', ['q' => 'private']))
            ->assertOk()
            ->assertJsonFragment(['slug' => $this->privateSubject->slug]);

        $this->actingAs($this->user('super_admin'))
            ->getJson(route('subjects.tree.data'))
            ->assertOk()
            ->assertJsonFragment(['slug' => $this->privateSubject->slug]);

        $this->actingAs($this->user('super_admin'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($this->privateSubject->title);

        $this->actingAs($this->user('super_admin'))
            ->get(route('admin.activity'))
            ->assertOk()
            ->assertSee($this->privateSubject->title);
    }

    public function test_standard_subject_keeps_existing_visibility_behavior(): void
    {
        $standard = Subject::factory()->create([
            'title' => 'Synthetic Standard Subject',
            'slug' => 'test-super-admin-standard',
            'access_level' => 'standard',
            'status' => 'published',
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);

        foreach (['super_admin', 'admin', 'moderator', 'citoyen'] as $role) {
            $this->actingAs($this->user($role))
                ->get(route('subjects.show', $standard->slug))
                ->assertOk()
                ->assertSee($standard->title);
        }

        $this->get(route('subjects.show', $standard->slug))
            ->assertOk()
            ->assertSee($standard->title);
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'email_verified_at' => now(),
            'requires_setup' => false,
        ]);
    }
}
