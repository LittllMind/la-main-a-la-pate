<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\User;
use App\Models\VisibilityLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticatedDiscoveryDoctrineTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private SubCategory $subCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::factory()->create();
        $this->subCategory = SubCategory::factory()->create([
            'category_id' => $this->category->id,
        ]);
    }

    private function makeSubject(string $title, array $overrides = []): Subject
    {
        return Subject::factory()->create(array_merge([
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'title' => $title,
            'slug' => strtolower(str_replace(' ', '-', $title)),
            'status' => 'published',
            'citizen_status' => 'published',
            'public_status' => 'published',
            'public_is_listed' => false,
        ], $overrides));
    }

    public function test_authenticated_subject_surfaces_use_acl_and_active_status_not_public_listing(): void
    {
        $admin = User::factory()->admin()->create();
        $listed = $this->makeSubject('ACTIVE_LISTED_SUBJECT');
        $unlisted = $this->makeSubject('ACTIVE_UNLISTED_SUBJECT');
        $archived = $this->makeSubject('ARCHIVED_SUBJECT', [
            'status' => 'archived',
        ]);

        $this->actingAs($admin)
            ->get(route('subjects.index'))
            ->assertOk()
            ->assertSee($listed->title)
            ->assertSee($unlisted->title)
            ->assertDontSee($archived->title);

        $this->actingAs($admin)
            ->getJson(route('subjects.tree.data'))
            ->assertOk()
            ->assertJsonFragment(['title' => $listed->title])
            ->assertJsonFragment(['title' => $unlisted->title])
            ->assertJsonMissing(['title' => $archived->title]);

        $this->actingAs($admin)
            ->getJson(route('documents.tree.documents.data'))
            ->assertOk()
            ->assertJsonFragment(['title' => $listed->title])
            ->assertJsonFragment(['title' => $unlisted->title])
            ->assertJsonMissing(['title' => $archived->title]);
    }

    public function test_authenticated_subject_surfaces_still_exclude_subjects_outside_user_acl(): void
    {
        $citizen = User::factory()->create(['role' => 'citoyen']);
        $visible = $this->makeSubject('VISIBLE_UNLISTED_CITIZEN_SUBJECT');
        $inaccessible = $this->makeSubject('INACCESSIBLE_UNLISTED_SUBJECT', [
            'public_status' => 'draft',
            'citizen_status' => 'draft',
            'public_body' => null,
            'citizen_body' => null,
        ]);

        $this->actingAs($citizen)
            ->get(route('subjects.index'))
            ->assertOk()
            ->assertSee($visible->title)
            ->assertDontSee($inaccessible->title);

        $this->actingAs($citizen)
            ->getJson(route('subjects.tree.data'))
            ->assertOk()
            ->assertJsonFragment(['title' => $visible->title])
            ->assertJsonMissing(['title' => $inaccessible->title]);

        $this->actingAs($citizen)
            ->getJson(route('documents.tree.documents.data'))
            ->assertOk()
            ->assertJsonFragment(['title' => $visible->title])
            ->assertJsonMissing(['title' => $inaccessible->title]);
    }

    public function test_document_tree_applies_subject_activity_and_document_acl(): void
    {
        $citizen = User::factory()->create(['role' => 'citoyen']);
        $active = $this->makeSubject('ACTIVE_UNLISTED_WITH_DOCUMENT');
        $archived = $this->makeSubject('ARCHIVED_WITH_DOCUMENT', [
            'status' => 'archived',
        ]);

        SubjectDocument::factory()->citizen()->create([
            'subject_id' => $active->id,
            'title' => 'VISIBLE_CITIZEN_DOCUMENT',
        ]);
        SubjectDocument::factory()->working()->create([
            'subject_id' => $active->id,
            'title' => 'HIDDEN_WORKING_DOCUMENT',
        ]);
        SubjectDocument::factory()->citizen()->create([
            'subject_id' => $archived->id,
            'title' => 'HIDDEN_ARCHIVED_DOCUMENT',
        ]);

        $response = $this->actingAs($citizen)
            ->getJson(route('documents.tree.documents.data'))
            ->assertOk();

        $response->assertJsonFragment(['title' => $active->title])
            ->assertJsonFragment(['title' => 'VISIBLE_CITIZEN_DOCUMENT'])
            ->assertJsonMissing(['title' => 'HIDDEN_WORKING_DOCUMENT'])
            ->assertJsonMissing(['title' => $archived->title])
            ->assertJsonMissing(['title' => 'HIDDEN_ARCHIVED_DOCUMENT']);
    }

    public function test_authenticated_search_finds_active_unlisted_subject_but_not_archived_or_inaccessible(): void
    {
        $citizen = User::factory()->create(['role' => 'citoyen']);
        $active = $this->makeSubject('SEARCH_ACTIVE_UNLISTED_SUBJECT');
        $archived = $this->makeSubject('SEARCH_ARCHIVED_SUBJECT', [
            'status' => 'archived',
        ]);
        $inaccessible = $this->makeSubject('SEARCH_INACCESSIBLE_SUBJECT', [
            'public_status' => 'draft',
            'citizen_status' => 'draft',
            'public_body' => null,
            'citizen_body' => null,
        ]);

        $response = $this->actingAs($citizen)
            ->getJson('/recherche?q=SEARCH_');

        $response->assertOk()
            ->assertJsonFragment(['title' => $active->title])
            ->assertJsonMissing(['title' => $archived->title])
            ->assertJsonMissing(['title' => $inaccessible->title]);
    }

    public function test_guest_search_keeps_public_listing_restriction(): void
    {
        $listed = $this->makeSubject('GUEST_LISTED_PUBLIC_SUBJECT', [
            'public_is_listed' => true,
        ]);
        $unlisted = $this->makeSubject('GUEST_UNLISTED_PUBLIC_SUBJECT');

        $response = $this->getJson('/recherche?q=GUEST_');

        $response->assertOk()
            ->assertJsonFragment(['title' => $listed->title])
            ->assertJsonMissing(['title' => $unlisted->title]);
    }

    public function test_visible_to_never_filters_by_public_is_listed_and_excludes_archived(): void
    {
        $admin = User::factory()->admin()->create();

        $draftUnlisted = $this->makeSubject('Videoprotection Admin Draft Unlisted', [
            'status'         => 'draft',
            'citizen_status' => 'draft',
            'public_status'  => 'draft',
            'public_is_listed' => false,
        ]);

        $archived = $this->makeSubject('Auth Archived Unlisted', [
            'status' => 'archived',
        ]);

        $found = Subject::visibleTo($admin)
            ->where('status', '!=', 'archived')
            ->where('slug', $draftUnlisted->slug)
            ->first();

        $this->assertNotNull($found, 'Admin doit voir sujet draft unlisted');
        $this->assertFalse($found->public_is_listed);

        $archivedFound = Subject::visibleTo($admin)
            ->where('status', '!=', 'archived')
            ->where('slug', $archived->slug)
            ->first();

        $this->assertNull($archivedFound, 'Sujet archive doit etre exclu');
    }
}
