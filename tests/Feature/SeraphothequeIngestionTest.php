<?php

namespace Tests\Feature;

use App\Console\Commands\Ingestion\SeraphothequeIngestion;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\User;
use App\Models\VisibilityLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Test d'ingestion du Subject Séraphothèque dans LMALP.
 *
 * Valide la commande app:seraphotheque-ingestion selon le manifest v1.1.
 * Ce test est isolé : il vérifie la logique de création et de visibilité,
 * pas le stockage des fichiers physiques de l'environnement de développement.
 */
class SeraphothequeIngestionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
    }

    private function seedEnvironment(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create([
            'id' => 10,
            'name' => 'Vie du village & Actualités',
            'slug' => 'vie-du-village-actualites',
        ]);
        $subCategory = SubCategory::factory()->create([
            'id' => 14,
            'category_id' => 10,
            'name' => 'Séraphothèque',
            'slug' => 'seraphotheque',
        ]);

        // Subjects historiques factices à préserver
        Category::factory()->create();
        SubCategory::factory()->create();
        $existing = Subject::factory()->count(3)->create();

        $pack = storage_path('testing/seraphotheque-pack');
        @mkdir($pack . '/archives-LEX/OPS-originaux-LEX/04-procedure', 0755, true);
        @mkdir($pack . '/archives-LEX/LEX-26-042', 0755, true);

        file_put_contents($pack . '/archives-LEX/OPS-originaux-LEX/04-procedure/sommation-huissier.pdf', "%PDF-1.4 fake\n");
        file_put_contents($pack . '/archives-LEX/LEX-26-042/recommande-AR.pdf', "%PDF-1.4 fake\n");
        file_put_contents($pack . '/archives-LEX/LEX-26-042/bail-boutique-2025.pdf', "%PDF-1.4 fake\n");
        file_put_contents($pack . '/archives-LEX/LEX-26-042/bail tisserand - el agri.pdf', "%PDF-1.4 fake\n");
        file_put_contents($pack . '/archives-LEX/LEX-26-042/-DELEGATION MAIRE.doc', "fake doc\n");

        $index = "# LA SÉRAPHOTHÈQUE — Comprendre la situation\n\n## 1. Comprendre en une minute\n\nTexte.\n\n## 5. La sommation du 24 avril 2026\n\n## 6. Ce que dit la mairie\n\n## 8. Les solutions proposées\n\n## 12. Approfondir\n";
        file_put_contents($pack . '/index.md', $index);
        file_put_contents($pack . '/fiche-d-sommation-24-avril-2026.md', "## Fiche sommation\n");
        file_put_contents($pack . '/fiche-e-mail-maire-14-mai-2026.md', "## Fiche email\n");
        file_put_contents($pack . '/fiche-h-demande-aot.md', "## Fiche AOT\n");
        file_put_contents($pack . '/chronologie.md', "## Chronologie\n");
        file_put_contents($pack . '/questions-ouvertes.md', "## Questions ouvertes\n");

        return [
            'admin' => $admin,
            'category' => $category,
            'subCategory' => $subCategory,
            'existing' => $existing,
            'pack' => $pack,
        ];
    }

    /** @test */
    public function it_creates_seraphotheque_subject_with_manifest_attributes(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        $exitCode = Artisan::call('app:seraphotheque-ingestion', [
            '--dry-run' => true,
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);
        $this->assertEquals(0, $exitCode);

        $exitCode = Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);
        $this->assertEquals(0, $exitCode);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();

        $this->assertEquals($admin->id, $subject->user_id);
        $this->assertEquals(10, $subject->category_id);
        $this->assertEquals(14, $subject->sub_category_id);
        $this->assertEquals('La Séraphothèque — Comprendre la situation', $subject->title);
        $this->assertEquals('seraphotheque-situation-2026', $subject->slug);
        $this->assertEquals('draft', $subject->status);
        $this->assertEquals('draft', $subject->citizen_status);
        $this->assertEquals('draft', $subject->public_status);

        $this->assertNotEmpty($subject->body);
        $this->assertNotEmpty($subject->citizen_body);
        $this->assertNotEmpty($subject->public_body);
        $this->assertStringContainsString('## Fiche — Sommation du 24 avril 2026', $subject->public_body);
        $this->assertStringContainsString('## Chronologie', $subject->public_body);
        $this->assertStringContainsString('## Questions ouvertes', $subject->public_body);
    }

    /** @test */
    public function it_attaches_only_working_documents_for_existing_assets(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();

        $publicDocs = $subject->documents->where('visibility', VisibilityLevel::Public->value);
        $citizenDocs = $subject->documents->where('visibility', VisibilityLevel::Citizen->value);

        $this->assertEquals(0, $publicDocs->count(), 'Aucun document Public ne doit être créé sans asset public.');
        $this->assertEquals(0, $citizenDocs->count(), 'Aucun document Citizen ne doit être créé sans asset public/citizen.');
    }

    /** @test */
    public function it_does_not_create_placeholders_for_missing_public_assets(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();

        $this->assertDatabaseMissing('subject_documents', [
            'subject_id' => $subject->id,
            'title' => 'Sommation du 24 avril 2026 — version expurgée',
        ]);

        $this->assertDatabaseMissing('subject_documents', [
            'subject_id' => $subject->id,
            'title' => 'Email d\'Arnaud Curvelier — 14 mai 2026 — version publique',
        ]);

        $this->assertDatabaseMissing('subject_documents', [
            'subject_id' => $subject->id,
            'title' => 'Demande d\'AOT du 16 juin 2026 — dossier publiable',
        ]);
    }

    /** @test */
    public function it_preserves_existing_subjects(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        $beforeIds = Subject::pluck('id')->toArray();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $afterIds = Subject::pluck('id')->toArray();

        foreach ($beforeIds as $id) {
            $this->assertContains($id, $afterIds, "Subject historique {$id} manquant.");
        }

        $this->assertEquals(count($beforeIds) + 1, count($afterIds));
    }

    /** @test */
    public function it_fails_without_pack_path(): void
    {
        $this->seedEnvironment();

        $exitCode = \Illuminate\Support\Facades\Artisan::call('app:seraphotheque-ingestion');

        $this->assertEquals(1, $exitCode);
        $this->assertDatabaseMissing('subjects', ['slug' => 'seraphotheque-situation-2026']);
    }
}
