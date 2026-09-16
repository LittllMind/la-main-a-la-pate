<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ACL restrictif : un sujet en access_level=collaborators_only
 * n'est visible/éditable que par son auteur, les collaborateurs
 * explicitement ajoutés, et un super_admin.
 *
 * Admin, moderator, citoyen et guest non collaborateurs sont bloqués.
 */
class SubjectCollaboratorsOnlyTest extends TestCase
{
    use RefreshDatabase;

    private Subject $subject;
    private User $owner;
    private User $collaborator;
    private User $admin;
    private User $moderator;
    private User $citizen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'citoyen', 'password' => 'password']);
        $this->collaborator = User::factory()->create(['role' => 'citoyen', 'password' => 'password']);
        $this->admin = User::factory()->create(['role' => 'admin', 'password' => 'password']);
        $this->moderator = User::factory()->create(['role' => 'moderator', 'password' => 'password']);
        $this->citizen = User::factory()->create(['role' => 'citoyen', 'password' => 'password']);

        $category = Category::factory()->create();
        $subCategory = SubCategory::factory()->create(['category_id' => $category->id]);

        $this->subject = Subject::factory()->for($this->owner)->create([
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'title' => 'Sujet restreint aux collaborateurs',
            'slug' => 'sujet-collaborateurs-seuls',
            'body' => 'CORPS_TRAVAIL_RESTREINT',
            'citizen_body' => null,
            'public_body' => null,
            'status' => 'draft',
            'citizen_status' => 'draft',
            'public_status' => 'draft',
            'access_level' => Subject::ACCESS_LEVEL_COLLABORATORS_ONLY,
        ]);

        $this->subject->collaborators()->attach($this->collaborator->id);
    }

    public function test_author_can_view_and_edit(): void
    {
        $this->actingAs($this->owner)
            ->get(route('subjects.show', $this->subject->slug))
            ->assertOk()
            ->assertSee('CORPS_TRAVAIL_RESTREINT');

        $this->actingAs($this->owner)
            ->get(route('subjects.edit', $this->subject->slug))
            ->assertOk();
    }

    public function test_collaborator_can_view_and_edit(): void
    {
        $this->actingAs($this->collaborator)
            ->get(route('subjects.show', $this->subject->slug))
            ->assertOk()
            ->assertSee('CORPS_TRAVAIL_RESTREINT');

        $this->actingAs($this->collaborator)
            ->get(route('subjects.edit', $this->subject->slug))
            ->assertOk();
    }

    public function test_admin_non_collaborator_is_blocked(): void
    {
        $this->actingAs($this->admin)
            ->get(route('subjects.show', $this->subject->slug))
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->get(route('subjects.edit', $this->subject->slug))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get(route('subjects.preview', [$this->subject->slug, 'citizen']))
            ->assertForbidden();
    }

    public function test_moderator_non_collaborator_is_blocked(): void
    {
        $this->actingAs($this->moderator)
            ->get(route('subjects.show', $this->subject->slug))
            ->assertNotFound();

        $this->actingAs($this->moderator)
            ->get(route('subjects.edit', $this->subject->slug))
            ->assertForbidden();
    }

    public function test_citizen_non_collaborator_is_blocked(): void
    {
        $this->actingAs($this->citizen)
            ->get(route('subjects.show', $this->subject->slug))
            ->assertNotFound();

        $this->actingAs($this->citizen)
            ->get(route('subjects.index'))
            ->assertOk()
            ->assertDontSee($this->subject->title);
    }

    public function test_guest_is_blocked(): void
    {
        $this->get(route('subjects.show', $this->subject->slug))
            ->assertNotFound();

        $this->get(route('subjects.preview', [$this->subject->slug, 'public']))
            ->assertRedirect('/');
    }

    public function test_search_tree_dashboard_exclude_subject(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/recherche?q=' . urlencode('CORPS_TRAVAIL_RESTREINT'))
            ->assertOk()
            ->assertJsonCount(0, 'subjects');

        $this->actingAs($this->admin)
            ->get('/documents/arbre-data')
            ->assertOk()
            ->assertJsonMissing(['title' => $this->subject->title]);

        $this->actingAs($this->owner)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee($this->subject->title);
    }

    public function test_standard_subject_unaffected(): void
    {
        $standard = Subject::factory()->create([
            'user_id' => $this->owner->id,
            'access_level' => Subject::ACCESS_LEVEL_STANDARD,
            'status' => 'draft',
            'citizen_status' => 'draft',
            'public_status' => 'draft',
            'body' => 'SECRET_STANDARD',
        ]);

        $this->actingAs($this->admin)
            ->get(route('subjects.show', $standard->slug))
            ->assertOk()
            ->assertSee('SECRET_STANDARD');

        $this->actingAs($this->moderator)
            ->get(route('subjects.show', $standard->slug))
            ->assertOk();
    }
}
