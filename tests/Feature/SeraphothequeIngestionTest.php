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
    public function it_does_not_duplicate_documents_on_rerun(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $countAfterFirst = $subject->documents()->count();
        $this->assertEquals(5, $countAfterFirst);

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject->refresh();
        $countAfterSecond = $subject->documents()->count();
        $this->assertEquals(5, $countAfterSecond, 'La relance identique ne doit pas dupliquer les documents.');
    }

    /** @test */
    public function it_fails_without_pack_path(): void
    {
        $this->seedEnvironment();

        $exitCode = \Illuminate\Support\Facades\Artisan::call('app:seraphotheque-ingestion');

        $this->assertEquals(1, $exitCode);
        $this->assertDatabaseMissing('subjects', ['slug' => 'seraphotheque-situation-2026']);
    }

    /** @test */
    public function force_preserves_manual_documents_and_does_not_touch_other_subjects(): void
    {
        ['admin' => $admin, 'pack' => $pack, 'existing' => $existing] = $this->seedEnvironment();

        // Ingestion initiale
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $otherSubject = $existing[0];

        // Document manuel sur le même Subject
        $manualDoc = \App\Models\SubjectDocument::create([
            'subject_id' => $subject->id,
            'filename' => 'manuel-document.pdf',
            'stored_filename' => 'manuel-document.pdf',
            'path' => 'test/manuel-document.pdf',
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => 1234,
            'title' => 'Document manuel hors pipeline',
            'visibility' => \App\Models\VisibilityLevel::Working->value,
            'source_reference' => 'manuel/hors-pipeline.pdf',
        ]);

        // Document sur un autre Subject
        $otherDoc = \App\Models\SubjectDocument::create([
            'subject_id' => $otherSubject->id,
            'filename' => 'other-document.pdf',
            'stored_filename' => 'other-document.pdf',
            'path' => 'test/other-document.pdf',
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => 5678,
            'title' => 'Document autre sujet',
            'visibility' => \App\Models\VisibilityLevel::Working->value,
            'source_reference' => 'other/subject.pdf',
        ]);

        Storage::disk('documents')->put('test/manuel-document.pdf', 'fake content');
        Storage::disk('documents')->put('test/other-document.pdf', 'fake other');

        $this->assertDatabaseHas('subject_documents', ['id' => $manualDoc->id]);
        $this->assertDatabaseHas('subject_documents', ['id' => $otherDoc->id]);

        // --force
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
            '--force' => true,
        ]);

        // Assertions
        $this->assertDatabaseHas('subject_documents', ['id' => $manualDoc->id]);
        $this->assertDatabaseHas('subject_documents', ['id' => $otherDoc->id]);
        $this->assertTrue(Storage::disk('documents')->exists('test/manuel-document.pdf'));
        $this->assertTrue(Storage::disk('documents')->exists('test/other-document.pdf'));

        $subject->refresh();
        $pipelineRefs = $subject->documents->pluck('source_reference')->toArray();
        $this->assertContains('seraphotheque-pack:archives-LEX/OPS-originaux-LEX/04-procedure/sommation-huissier.pdf', $pipelineRefs);
        $this->assertContains('manuel/hors-pipeline.pdf', $pipelineRefs);
    }

    /** @test */
    public function it_does_not_create_extra_files_on_identical_rerun(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $filesAfterFirst = Storage::disk('documents')->allFiles();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $filesAfterSecond = Storage::disk('documents')->allFiles();

        $this->assertEquals(count($filesAfterFirst), count($filesAfterSecond), 'Une relance identique ne doit pas créer de fichiers supplémentaires.');
    }

    /** @test */
    public function dry_run_does_not_create_database_records(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        $exitCode = Artisan::call('app:seraphotheque-ingestion', [
            '--dry-run' => true,
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertDatabaseMissing('subjects', ['slug' => 'seraphotheque-situation-2026']);
        $this->assertDatabaseMissing('subject_documents', ['title' => 'Sommation du 24 avril 2026 — originale']);
        $this->assertCount(0, Storage::disk('documents')->allFiles(), 'Aucun fichier ne doit être écrit en dry-run.');
    }

    /** @test */
    public function absent_asset_preserved_without_force(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        // Run 1 : tous les assets présents
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $this->assertDatabaseHas('subject_documents', [
            'subject_id' => $subject->id,
            'source_reference' => 'seraphotheque-pack:archives-LEX/LEX-26-042/recommande-AR.pdf',
        ]);

        // Retirer un asset du pack
        @unlink($pack . '/archives-LEX/LEX-26-042/recommande-AR.pdf');

        // Run 2 sans --force
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        // Le document doit persister
        $this->assertDatabaseHas('subject_documents', [
            'subject_id' => $subject->id,
            'source_reference' => 'seraphotheque-pack:archives-LEX/LEX-26-042/recommande-AR.pdf',
        ]);
    }

    /** @test */
    public function absent_asset_removed_with_force(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        // Run 1 : tous les assets présents
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();

        $this->assertDatabaseHas('subject_documents', [
            'subject_id' => $subject->id,
            'source_reference' => 'seraphotheque-pack:archives-LEX/LEX-26-042/recommande-AR.pdf',
        ]);

        // Retirer un asset du pack
        @unlink($pack . '/archives-LEX/LEX-26-042/recommande-AR.pdf');

        // Run 2 avec --force
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
            '--force' => true,
        ]);

        // Le document doit être supprimé
        $this->assertDatabaseMissing('subject_documents', [
            'subject_id' => $subject->id,
            'source_reference' => 'seraphotheque-pack:archives-LEX/LEX-26-042/recommande-AR.pdf',
        ]);
    }

    /** @test */
    public function no_source_reference_contains_local_paths(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $refs = $subject->documents->pluck('source_reference');

        foreach ($refs as $ref) {
            $this->assertStringNotContainsString('/home/', $ref, 'La source_reference ne doit pas contenir de chemin absolu /home/.');
            $this->assertStringNotContainsString('/tmp/', $ref, 'La source_reference ne doit pas contenir de chemin absolu /tmp/.');
            $this->assertStringNotContainsString('storage/framework/testing', $ref, 'La source_reference ne doit pas contenir de chemin de test temporaire.');
        }
    }

    /** @test */
    public function manual_document_with_shared_source_reference_is_not_appropriated(): void
    {
        ['admin' => $admin, 'pack' => $pack] = $this->seedEnvironment();

        // Créer Subject sans ingestion
        $adminUser = \App\Models\User::factory()->create(['role' => 'admin']);
        $subject = \App\Models\Subject::create([
            'slug' => 'seraphotheque-situation-2026',
            'user_id' => $adminUser->id,
            'category_id' => 10,
            'sub_category_id' => 14,
            'theme' => 'test',
            'title' => 'Test',
            'body' => 'test body',
            'citizen_body' => 'test citizen',
            'public_body' => 'test public',
            'status' => 'draft',
            'citizen_status' => 'draft',
            'public_status' => 'draft',
        ]);

        // Document MANUEL avec une source_reference du catalogue pipeline (SANS namespace)
        $manualDoc = \App\Models\SubjectDocument::create([
            'subject_id' => $subject->id,
            'filename' => 'manuel.pdf',
            'stored_filename' => 'manuel.pdf',
            'path' => 'test/manuel-preserve.pdf',
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => 9999,
            'title' => 'Document manuel collisionnel',
            'visibility' => \App\Models\VisibilityLevel::Working->value,
            'source_reference' => 'archives-LEX/LEX-26-042/recommande-AR.pdf',
        ]);

        Storage::disk('documents')->put('test/manuel-preserve.pdf', 'contenu-manuel');

        // Ingestion
        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $pack,
            '--user-id' => $admin->id,
        ]);

        $subject->refresh();

        // Le document manuel ne doit PAS être approprié (le titre doit rester manuel)
        $this->assertDatabaseHas('subject_documents', [
            'id' => $manualDoc->id,
            'title' => 'Document manuel collisionnel',
            'filename' => 'manuel.pdf',
            'size' => 9999,
        ]);
    }
}
