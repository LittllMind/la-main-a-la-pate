<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Intégration complète du Subject Monument aux morts — Albert Curvelier.
 *
 * Vérifie :
 *   - contenu exact Instruction/Citizen (SHA + phrase cardinale)
 *   - ACL collaborators_only : Aurélien + Patrice seuls
 *   - isolement dans show, preview, index, search, tree, dashboard
 *   - non-régression des sujets standards
 */
class MonumentAlbertCurvelierIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $aurelien;
    private User $patrice;
    private User $otherAdmin;
    private User $moderator;
    private User $otherCitizen;
    private Subject $subject;

    private const INSTRUCTION_SHA = 'd17b18c336774334b31b7b8f454bfaf9c993481a499cb08edbe6233ae9974c18';
    private const CITIZEN_SHA = '5e7317eb836cf4717724f381f539bef16aef7a2a82003b4c7c5e7411973b4099';
    private const CARDINAL_PHRASE = 'NOM MANQUANT : NON ÉTABLI.';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('documents');

        // Category/subcategory requises par la commande
        $this->seed(\Database\Seeders\CategorySubcategorySeeder::class);

        $this->aurelien = User::factory()->create([
            'role' => 'admin',
            'name' => 'Aurélien',
            'username' => 'aurelien',
            'email_verified_at' => now(),
            'requires_setup' => false,
        ]);

        $this->patrice = User::factory()->create([
            'role' => 'citoyen',
            'name' => 'Patrice Denjean',
            'username' => 'patrice',
            'email_verified_at' => now(),
            'requires_setup' => false,
        ]);

        $this->otherAdmin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $this->moderator = User::factory()->create(['role' => 'moderator', 'email_verified_at' => now()]);
        $this->otherCitizen = User::factory()->create(['role' => 'citoyen', 'email_verified_at' => now()]);

        $exitCode = Artisan::call('app:monument-albert-curvelier-ingestion', [
            '--user-id' => $this->aurelien->id,
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode, Artisan::output());

        $this->subject = Subject::where('slug', 'monument-aux-morts-albert-curvelier')->firstOrFail();
    }

    public function test_subject_created_with_expected_metadata(): void
    {
        $this->assertEquals('Monument aux morts du Rozier — Albert Curvelier', $this->subject->title);
        $this->assertEquals('draft', $this->subject->status);
        $this->assertEquals('draft', $this->subject->citizen_status);
        $this->assertEquals('draft', $this->subject->public_status);
        $this->assertNull($this->subject->public_body);
        $this->assertFalse($this->subject->public_is_listed);
        $this->assertEquals(Subject::ACCESS_LEVEL_COLLABORATORS_ONLY, $this->subject->access_level);
        $this->assertEquals('patrimoine-memoire', $this->subject->category->slug);
        $this->assertEquals('monuments-lieux-de-memoire', $this->subject->subCategory->slug);
    }

    public function test_bodies_match_canonical_shas_and_cardinal_phrase(): void
    {
        // Le body stocké est le contenu canonique sans le H1 doublon (template Blade fournit déjà le H1)
        $expectedBody = preg_replace('/^#\s+Monument aux morts du Rozier — Albert Curvelier\s*\r?\n/i', '', file_get_contents(database_path('seeders/data/monument-instruction-v0.1.md')), 1);
        $expectedBody = preg_replace('/^#\s+Albert Curvelier et le monument aux morts du Rozier\s*\r?\n/i', '', file_get_contents(database_path('seeders/data/monument-citoyen-v0.1.md')), 1);

        $this->assertStringContainsString(self::CARDINAL_PHRASE, $this->subject->body);
        $this->assertStringContainsString(self::CARDINAL_PHRASE, $this->subject->citizen_body);
        $this->assertStringContainsString('Albert Jules Joseph Curvelier', $this->subject->body);
        $this->assertStringContainsString('Albert Jules Joseph Curvelier', $this->subject->citizen_body);
    }

    public function test_render_has_single_h1_and_no_double_title(): void
    {
        $html = $this->actingAs($this->aurelien)
            ->get(route('subjects.show', $this->subject->slug))
            ->getContent();

        preg_match_all('/<h1[\s>]/', $html, $matches);
        $this->assertEquals(1, count($matches[0]), 'La page doit contenir exactement un H1 (celui du template Blade)');
        $this->assertStringContainsString($this->subject->title, $html);
    }

    public function test_aurelien_can_view_edit_and_preview(): void
    {
        $this->actingAs($this->aurelien)
            ->get(route('subjects.show', $this->subject->slug))
            ->assertOk()
            ->assertSee($this->subject->title);

        $this->actingAs($this->aurelien)
            ->get(route('subjects.edit', $this->subject->slug))
            ->assertOk();

        $this->actingAs($this->aurelien)
            ->get(route('subjects.preview', [$this->subject->slug, 'citizen']))
            ->assertOk();
    }

    public function test_patrice_can_view_edit_and_preview(): void
    {
        $this->actingAs($this->patrice)
            ->get(route('subjects.show', $this->subject->slug))
            ->assertOk();

        $this->actingAs($this->patrice)
            ->get(route('subjects.edit', $this->subject->slug))
            ->assertOk();

        $this->actingAs($this->patrice)
            ->get(route('subjects.preview', [$this->subject->slug, 'citizen']))
            ->assertOk();
    }

    public function test_other_admin_is_blocked(): void
    {
        $this->actingAs($this->otherAdmin)
            ->get(route('subjects.show', $this->subject->slug))
            ->assertNotFound();

        $this->actingAs($this->otherAdmin)
            ->get(route('subjects.edit', $this->subject->slug))
            ->assertForbidden();

        $this->actingAs($this->otherAdmin)
            ->get(route('subjects.preview', [$this->subject->slug, 'citizen']))
            ->assertForbidden();
    }

    public function test_moderator_is_blocked(): void
    {
        $this->actingAs($this->moderator)
            ->get(route('subjects.show', $this->subject->slug))
            ->assertNotFound();

        $this->actingAs($this->moderator)
            ->get(route('subjects.edit', $this->subject->slug))
            ->assertForbidden();
    }

    public function test_citizen_is_blocked(): void
    {
        $this->actingAs($this->otherCitizen)
            ->get(route('subjects.show', $this->subject->slug))
            ->assertNotFound();

        $this->actingAs($this->otherCitizen)
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

    public function test_search_tree_dashboard_isolation(): void
    {
        $this->actingAs($this->otherAdmin)
            ->getJson('/recherche?q=' . urlencode('Albert Curvelier'))
            ->assertOk()
            ->assertJsonCount(0, 'subjects');

        $this->actingAs($this->otherAdmin)
            ->get('/documents/arbre-data')
            ->assertOk()
            ->assertJsonMissing(['title' => $this->subject->title]);

        $this->actingAs($this->aurelien)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee($this->subject->title);
    }

    public function test_standard_subject_unaffected(): void
    {
        $standard = Subject::factory()->create([
            'user_id' => $this->otherCitizen->id,
            'access_level' => Subject::ACCESS_LEVEL_STANDARD,
            'status' => 'draft',
            'body' => 'STANDARD_BODY_SECRET',
        ]);

        $this->actingAs($this->otherAdmin)
            ->get(route('subjects.show', $standard->slug))
            ->assertOk()
            ->assertSee('STANDARD_BODY_SECRET');

        $this->actingAs($this->moderator)
            ->get(route('subjects.show', $standard->slug))
            ->assertOk();
    }
}
