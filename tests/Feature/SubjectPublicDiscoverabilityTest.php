<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contrat de découvrabilité publique des Subjects.
 *
 * public_status : publication effective pour l'audience Guest/Citoyen.
 * public_is_listed : apparence dans les surfaces générales de découverte.
 */
class SubjectPublicDiscoverabilityTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private SubCategory $subCategory;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->category = Category::factory()->create();
        $this->subCategory = SubCategory::factory()->create([
            'category_id' => $this->category->id,
        ]);
    }

    /**
     * Subject factory produces nullable boolean as string "1"/"0".
     * Cast it explicitly so MySQL boolean tinyint is set.
     */
    protected function makeSubject(array $overrides = []): Subject
    {
        $data = array_merge([
            'user_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'title' => 'Sujet test',
            'slug' => 'sujet-test-' . Subject::count(),
            'body' => 'Working',
            'citizen_body' => 'Citizen',
            'public_body' => 'Public',
            'status' => 'draft',
            'citizen_status' => 'draft',
            'public_status' => 'draft',
        ], $overrides);

        $data['public_is_listed'] = ($data['public_is_listed'] ?? true) ? 1 : 0;

        return Subject::factory()->create($data);
    }

    /** @test */
    public function listed_public_subject_appears_in_catalogue_and_search(): void
    {
        $citizen = User::factory()->create(['role' => 'citoyen']);

        $subject = $this->makeSubject([
            'title' => 'LISTED_PUBLIC_UNIQUE_42A',
            'slug' => 'listed-public-unique-42a',
            'public_status' => 'published',
            'public_is_listed' => true,
        ]);

        // Catalogue authentifié : les sujets publics sont visibles des citoyens.
        $this->actingAs($citizen)->get(route('subjects.index'))
            ->assertOk()
            ->assertSee($subject->title);

        // Recherche Guest : la search est publique.
        $this->get(route('search', ['q' => 'LISTED_PUBLIC_UNIQUE_42A']))
            ->assertOk()
            ->assertSee($subject->title);

        // Route directe Guest : OK.
        $this->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('Public');
    }

    /** @test */
    public function unlisted_public_subject_is_hidden_from_catalogue_and_search_but_direct_route_works(): void
    {
        $citizen = User::factory()->create(['role' => 'citoyen']);

        $subject = $this->makeSubject([
            'title' => 'UNLISTED_PUBLIC_UNIQUE_7B9',
            'slug' => 'unlisted-public-unique-7b9',
            'public_status' => 'published',
            'public_is_listed' => false,
        ]);

        // Catalogue authentifié : absent.
        $this->actingAs($citizen)->get(route('subjects.index'))
            ->assertOk()
            ->assertDontSee($subject->title);

        // Recherche Guest : absent.
        $searchResponse = $this->get(route('search', ['q' => 'UNLISTED_PUBLIC_UNIQUE_7B9']));
        $searchResponse->assertOk()->assertSee('Aucun resultat');
        $this->assertStringNotContainsString(
            route('subjects.show', $subject->slug, false),
            $searchResponse->getContent()
        );

        // Route directe Guest : OK.
        $this->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('Public')
            ->assertSee($subject->title);
    }

    /** @test */
    public function unlisted_public_subject_is_also_absent_from_category_counts_and_tree(): void
    {
        $subject = $this->makeSubject([
            'title' => 'UNLISTED_TREE_UNIQUE_C3D',
            'slug' => 'unlisted-tree-unique-c3d',
            'public_status' => 'published',
            'public_is_listed' => false,
        ]);

        $citizen = User::factory()->create(['role' => 'citoyen']);

        // Catalogue auth : authentifié mais pas admin ; l'ACL de l'auteur/citoyen ne change pas.
        $this->actingAs($citizen)->get(route('subjects.index'))
            ->assertOk()
            ->assertDontSee($subject->title);

        // Arbre sujets auth
        $this->actingAs($citizen)->getJson(route('subjects.tree.data'))
            ->assertOk()
            ->assertJsonMissing(['title' => $subject->title]);

        // Arbre documents auth
        $this->actingAs($citizen)->getJson(route('documents.tree.documents.data'))
            ->assertOk()
            ->assertJsonMissing(['title' => $subject->title]);

        // Accès direct Citoyen : OK (audience Public est accessible aux citoyens authentifiés)
        $this->actingAs($citizen)->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('Public');
    }

    /** @test */
    public function draft_subject_is_inaccessible_guest_regardless_of_listed_flag(): void
    {
        $listedDraft = $this->makeSubject([
            'title' => 'DRAFT_LISTED_UNIQUE_001',
            'slug' => 'draft-listed-unique-001',
            'public_status' => 'draft',
            'public_is_listed' => true,
        ]);

        $unlistedDraft = $this->makeSubject([
            'title' => 'DRAFT_UNLISTED_UNIQUE_002',
            'slug' => 'draft-unlisted-unique-002',
            'public_status' => 'draft',
            'public_is_listed' => false,
        ]);

        $this->get(route('subjects.index'))->assertRedirect('/')->assertDontSee($listedDraft->title);
        $this->get(route('subjects.index'))->assertRedirect('/')->assertDontSee($unlistedDraft->title);

        $this->get(route('subjects.show', $listedDraft->slug))->assertNotFound();
        $this->get(route('subjects.show', $unlistedDraft->slug))->assertNotFound();
    }

    /** @test */
    public function listed_flag_does_not_change_citizen_and_working_acl(): void
    {
        $subject = $this->makeSubject([
            'title' => 'ACL_UNIQUE_E5F',
            'slug' => 'acl-unique-e5f',
            'body' => 'WORKING_SECRET_E5F',
            'citizen_body' => 'CITIZEN_SECRET_E5F',
            'public_body' => 'PUBLIC_SECRET_E5F',
            'status' => 'draft',
            'citizen_status' => 'published',
            'public_status' => 'published',
            'public_is_listed' => false,
        ]);

        $owner = $this->admin;
        $citizen = User::factory()->create(['role' => 'citoyen']);
        $admin = User::factory()->create(['role' => 'admin']);

        // Propriétaire : body de travail
        $this->actingAs($owner)->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('WORKING_SECRET_E5F')
            ->assertDontSee('CITIZEN_SECRET_E5F')
            ->assertDontSee('PUBLIC_SECRET_E5F');

        // Citoyen : citizen_body
        $this->actingAs($citizen)->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('CITIZEN_SECRET_E5F')
            ->assertDontSee('WORKING_SECRET_E5F');

        // Admin : body de travail
        $this->actingAs($admin)->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('WORKING_SECRET_E5F')
            ->assertDontSee('CITIZEN_SECRET_E5F')
            ->assertDontSee('PUBLIC_SECRET_E5F');
    }

    /** @test */
    public function default_public_is_listed_is_true_and_preserves_existing_behaviour(): void
    {
        $subject = $this->makeSubject([
            'public_status' => 'published',
            'public_is_listed' => null,
        ]);

        $fresh = Subject::find($subject->id);
        $this->assertTrue($fresh->public_is_listed, 'public_is_listed doit être vrai par défaut.');
    }

    /** @test */
    public function sitemap_does_not_leak_unlisted_subject_title(): void
    {
        $subject = $this->makeSubject([
            'title' => 'UNLISTED_SITEMAP_X7Y',
            'slug' => 'unlisted-sitemap-x7y',
            'public_status' => 'published',
            'public_is_listed' => false,
        ]);

        $this->get(route('site.map'))
            ->assertOk()
            ->assertDontSee($subject->title);
    }

    /** @test */
    public function seraphotheque_cta_reaches_unlisted_public_subject(): void
    {
        $subject = $this->makeSubject([
            'title' => 'Seraphotheque situation 2026',
            'slug' => 'seraphotheque-situation-2026',
            'public_status' => 'published',
            'public_is_listed' => false,
        ]);

        $ctaPath = route('subjects.show', $subject->slug, false);

        $response = $this->get(route('seraphotheque'));
        $response->assertOk();
        $this->assertStringContainsString($ctaPath, $response->getContent());

        $this->get($ctaPath)
            ->assertOk()
            ->assertSee($subject->title)
            ->assertSee('Public');
    }
}
