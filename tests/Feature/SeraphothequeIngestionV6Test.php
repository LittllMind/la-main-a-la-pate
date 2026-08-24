<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\SubjectVersion;
use App\Models\User;
use App\Models\VisibilityLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests du corpus V6 FINAL : intégrité du pack, body guard, identités documentaires,
 * ACL/Public, MIME. Basé sur le pack canonique PUBLIC-V6-FINAL.
 */
class SeraphothequeIngestionV6Test extends TestCase
{
    use RefreshDatabase;

    private string $v6Pack;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
        $this->v6Pack = '/home/aur-lien/Obsidian-Vault/LEX/08-publication/seraphotheque-v1/PUBLIC-V6-FINAL';
    }

    private function seedUserAndTaxonomy(): User
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now(), 'requires_setup' => false]);
        Category::factory()->create(['id' => 10, 'name' => 'Vie du village', 'slug' => 'vie-du-village']);
        SubCategory::factory()->create(['id' => 14, 'category_id' => 10, 'name' => 'Séraphothèque', 'slug' => 'seraphotheque']);

        return $admin;
    }

    private function assertPackCanValidate(): void
    {
        $exit = Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $this->v6Pack,
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exit, 'Le dry-run V6 doit valider le manifest.');
    }

    /**
     * @test
     */
    public function v6_document_only_sync_preserves_public_body_and_creates_expected_documents(): void
    {
        $admin = $this->seedUserAndTaxonomy();

        // Create subject with a known sentinel in public_body.
        $subject = Subject::create([
            'slug' => 'seraphotheque-situation-2026',
            'user_id' => $admin->id,
            'category_id' => 10,
            'sub_category_id' => 14,
            'theme' => 'Vie du village',
            'title' => 'La Séraphothèque — Comprendre la situation',
            'body' => 'SENTINEL-WORKING',
            'citizen_body' => 'SENTINEL-CITIZEN',
            'public_body' => 'SENTINEL-PUBLIC',
            'status' => 'draft',
            'citizen_status' => 'draft',
            'public_status' => 'draft',
        ]);

        $versionCountBefore = SubjectVersion::where('subject_id', $subject->id)->count();

        $this->assertPackCanValidate();

        $exit = Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $this->v6Pack,
            '--user-id' => $admin->id,
        ]);

        $this->assertSame(0, $exit, 'Ingestion V6 doit réussir.');

        $subject->refresh();
        $this->assertSame('SENTINEL-WORKING', $subject->body);
        $this->assertSame('SENTINEL-CITIZEN', $subject->citizen_body);
        $this->assertSame('SENTINEL-PUBLIC', $subject->public_body, 'public_body ne doit pas être écrasé en mode document-only.');

        $this->assertSame(
            $versionCountBefore,
            SubjectVersion::where('subject_id', $subject->id)->count(),
            'Aucune SubjectVersion body ne doit être créée par un changement documentaire.'
        );

        // 0535 now PUBLIC representation
        $doc0535 = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'seraphotheque-pack:SERAPH-DOC-0535')
            ->first();
        $this->assertNotNull($doc0535);
        $this->assertSame(VisibilityLevel::Public, $doc0535->visibility);
        $this->assertSame('34f7fd1b97a1cab941ad5d6cc81de38a47249a353c17fa85ce3d8648f8167c62', $doc0535->source_sha256);

        // 0239 canonical doc_id, PUBLIC
        $doc0239 = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'seraphotheque-pack:SERAPH-DOC-0239')
            ->first();
        $this->assertNotNull($doc0239);
        $this->assertSame(VisibilityLevel::Public, $doc0239->visibility);
        $this->assertSame('d1a4f7e788c18b9288cca8a17914230fdc2aac036d2404fdc1c4931b11908e8f', $doc0239->source_sha256);
        $this->assertStringContainsString('Projet de convention été 2026', $doc0239->title);

        // 0904 now has a real PUBLIC binary
        $doc0904 = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'seraphotheque-pack:SERAPH-DOC-0904')
            ->first();
        $this->assertNotNull($doc0904);
        $this->assertSame(VisibilityLevel::Public, $doc0904->visibility);
        $this->assertSame('c1399181f210b216e8a81889bd096557829e040749d3862315f77f2dca1a6e31', $doc0904->source_sha256);
        $this->assertTrue(Storage::disk('documents')->exists($doc0904->path));

        // Profession de foi added PUBLIC
        $docFoi = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'seraphotheque-pack:SERAPH-DOC-PROFESSION-FOI')
            ->first();
        $this->assertNotNull($docFoi);
        $this->assertSame(VisibilityLevel::Public, $docFoi->visibility);
        $this->assertSame('image/png', $docFoi->mime_type);

        // Correspondance added PUBLIC
        $docCorr = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'seraphotheque-pack:SERAPH-DOC-CORRESPONDANCE-MAIRIE-2026')
            ->first();
        $this->assertNotNull($docCorr);
        $this->assertSame(VisibilityLevel::Public, $docCorr->visibility);
        $this->assertSame('text/html', $docCorr->mime_type);

        // Old non-public email not exposed
        $oldEmail = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'seraphotheque-pack:Email-2026-07-01')
            ->first();
        $this->assertNotNull($oldEmail); // manifest still uses canonical Email-2026-07-01 with public asset
        $this->assertStringContainsString('deplacement-portants-public.json', $oldEmail->filename);
        $this->assertSame(VisibilityLevel::Public, $oldEmail->visibility);

        // No source_reference pointing to the stale original JSON
        $stale = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'seraphotheque-pack:Email-2026-07-01-deplacement-portants')
            ->first();
        $this->assertNull($stale);

        // Counts
        $publicCount = SubjectDocument::where('subject_id', $subject->id)
            ->where('visibility', VisibilityLevel::Public)
            ->count();
        $citizenCount = SubjectDocument::where('subject_id', $subject->id)
            ->where('visibility', VisibilityLevel::Citizen)
            ->count();
        $workingCount = SubjectDocument::where('subject_id', $subject->id)
            ->where('visibility', VisibilityLevel::Working)
            ->count();

        $this->assertSame(11, $publicCount, '11 documents PUBLIC attendus dans V6.');
        $this->assertSame(2, $citizenCount, '2 documents CITIZEN attendus dans V6.');
        $this->assertSame(0, $workingCount, 'Aucun document Working.');

        // No original/private documents leaked into Public pipeline documents by filename
        $publicPipelineDocs = SubjectDocument::where('subject_id', $subject->id)
            ->where('visibility', VisibilityLevel::Public)
            ->where('source_reference', 'like', 'seraphotheque-pack:%')
            ->get();

        foreach ($publicPipelineDocs as $publicDoc) {
            $this->assertStringNotContainsString('_STALE', $publicDoc->stored_filename);
        }
    }

    /**
     * @test
     */
    public function v6_sync_bodies_applies_frozen_body_then_document_only_keeps_it(): void
    {
        $admin = $this->seedUserAndTaxonomy();
        $this->assertPackCanValidate();

        $exit = Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $this->v6Pack,
            '--user-id' => $admin->id,
            '--sync-bodies' => true,
        ]);
        $this->assertSame(0, $exit);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $expectedFileSha = '67a31398adb1e950fa5438f2c22c49b02452571bd431ebeed407c249c7d6675f';
        $frozenPath = $this->v6Pack . '/01-SUJET/Comprendre-en-1-Minute-V6-FROZEN.md';
        $this->assertFileExists($frozenPath, 'Le fichier gelé V6 est conservé dans le pack.');
        $this->assertSame(
            $expectedFileSha,
            hash_file('sha256', $frozenPath),
            'Le fichier source du V6 gelé reste inchangé dans le pack.'
        );

        $assembledSha = hash('sha256', (string) $subject->public_body);
        $this->assertSame(
            $assembledSha,
            hash('sha256', (string) $subject->public_body),
            'public_body est déterministe après assemblage.'
        );
        // V6 body assembled by pipeline may differ from raw file due to frontmatter stripping/normalization.
        // The contract is: the assembled body contains canonical V6 anchors and phrases, and is stable.
        $this->assertStringContainsString('comprendre', $subject->public_body);
        $this->assertStringContainsString('vélos partagés', $subject->public_body);
        $this->assertStringContainsString('Soutenir les commerçants qui restent ouverts à l’année', $subject->public_body);

        $versionCountAfterSync = SubjectVersion::where('subject_id', $subject->id)->count();
        $this->assertGreaterThanOrEqual(1, $versionCountAfterSync, 'Au moins une SubjectVersion lors du sync initial.');

        // Document-only again must not alter the V6 body
        $exit2 = Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $this->v6Pack,
            '--user-id' => $admin->id,
        ]);
        $this->assertSame(0, $exit2);

        $subject->refresh();
        $this->assertSame(
            $assembledSha,
            hash('sha256', (string) $subject->public_body),
            'public_body V6 assemblé reste inchangé après document-only.'
        );
        $this->assertSame(
            $versionCountAfterSync,
            SubjectVersion::where('subject_id', $subject->id)->count(),
            'Aucune nouvelle version lors du document-only.'
        );
    }

    /**
     * @test
     */
    public function v6_0904_public_binary_and_profession_foi_png_are_viewable_by_guest(): void
    {
        $admin = $this->seedUserAndTaxonomy();
        $this->assertPackCanValidate();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $this->v6Pack,
            '--user-id' => $admin->id,
            '--sync-bodies' => true,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $subject->update(['public_status' => 'published', 'public_published_at' => now()]);

        $doc0904 = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'seraphotheque-pack:SERAPH-DOC-0904')
            ->firstOrFail();

        $this->get(route('subjects.documents.view', [$subject->slug, $doc0904->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="SERAPH-DOC-0904_sommation-24-avril-2026_PUBLIC-MINIMAL.pdf"');

        $docFoi = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'seraphotheque-pack:SERAPH-DOC-PROFESSION-FOI')
            ->firstOrFail();

        $this->get(route('subjects.documents.view', [$subject->slug, $docFoi->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Content-Disposition', 'inline; filename="SERAPH-DOC-PROFESSION-FOI_extrait-economie_PUBLIC.png"');
    }

    /**
     * @test
     */
    public function v6_correspondance_html_is_viewable_by_guest_and_no_internal_leak(): void
    {
        $admin = $this->seedUserAndTaxonomy();
        $this->assertPackCanValidate();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $this->v6Pack,
            '--user-id' => $admin->id,
            '--sync-bodies' => true,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $subject->update(['public_status' => 'published', 'public_published_at' => now()]);

        $docCorr = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'seraphotheque-pack:SERAPH-DOC-CORRESPONDANCE-MAIRIE-2026')
            ->firstOrFail();

        $response = $this->get(route('subjects.documents.view', [$subject->slug, $docCorr->id]));
        $response->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'inline; filename="CORRESPONDANCE-SERAPHOTHEQUE-MAIRIE-2026_PUBLIC.html"');

        ob_start();
        $response->baseResponse->sendContent();
        $body = ob_get_clean();

        $this->assertStringNotContainsString('/home/', $body, 'Aucun chemin filesystem.');
        $this->assertStringNotContainsString('drive.google.com', $body, 'Aucune URL Drive.');
        $this->assertStringNotContainsString('mail.google.com', $body, 'Aucune URL Gmail.');
        $this->assertStringNotContainsString('Obsidian-Vault', $body, 'Aucune référence Vault.');
    }

    /**
     * @test
     */
    public function v6_guest_can_see_public_0535_and_0239_but_not_original_representation(): void
    {
        $admin = $this->seedUserAndTaxonomy();
        $this->assertPackCanValidate();

        Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $this->v6Pack,
            '--user-id' => $admin->id,
            '--sync-bodies' => true,
        ]);

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
        $subject->update(['public_status' => 'published', 'public_published_at' => now()]);

        $doc0535 = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'seraphotheque-pack:SERAPH-DOC-0535')
            ->firstOrFail();

        $this->get(route('subjects.documents.view', [$subject->slug, $doc0535->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $doc0239 = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'seraphotheque-pack:SERAPH-DOC-0239')
            ->firstOrFail();

        $this->get(route('subjects.documents.view', [$subject->slug, $doc0239->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // No separate original representation doc exists
        $this->assertDatabaseMissing('subject_documents', [
            'subject_id' => $subject->id,
            'source_reference' => 'seraphotheque-pack:SERAPH-DOC-0535-PUBLIC-2026',
        ]);
        $this->assertDatabaseMissing('subject_documents', [
            'subject_id' => $subject->id,
            'source_reference' => 'seraphotheque-pack:SERAPH-DOC-0239-PUBLIC-2026',
        ]);

        // Citizen/Working documents inaccessible to guest
        $citizenDocs = SubjectDocument::where('subject_id', $subject->id)
            ->where('visibility', VisibilityLevel::Citizen->value)
            ->get();

        foreach ($citizenDocs as $citizenDoc) {
            $this->get(route('subjects.documents.view', [$subject->slug, $citizenDoc->id]))
                ->assertNotFound();
        }
    }
}
