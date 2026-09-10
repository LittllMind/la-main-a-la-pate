<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\RepresentationType;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\SubjectVersion;
use App\Models\User;
use App\Models\VisibilityLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubjectGuinguetteDocumentIngestionTest extends TestCase
{
    use RefreshDatabase;

    private const INSTRUCTION_SHA = '68cb1b4a1dd96b683c7e1756d368b62e484ce705644e8af9850983ff5510a0e8';
    private const CITIZEN_SHA = '5ffa64889b8214c5cd5bbb97e5eeb867187bbda93f1138e83949f5914ebc720e';

    private const EXPECTED_REFS = [
        'manual:guinguette:ta-2202355-2024-09-17',
        'manual:guinguette:cm-2019-09-27',
        'manual:guinguette:cm-2020-02-10',
        'manual:guinguette:cm-2021-06-07',
        'manual:guinguette:cm-2021-10-12',
        'manual:guinguette:cm-2022-06-02',
        'manual:guinguette:cm-2022-06-28',
        'manual:guinguette:cm-2023-03-29',
        'manual:guinguette:cm-2025-10-21',
        'manual:guinguette:cm-2026-02-11',
    ];

    private const EXPECTED_SHA = [
        'manual:guinguette:ta-2202355-2024-09-17' => 'cd54b90d7b61774f4be32136af164ecb485d7dee64b5ec40c3fae857b23c4315',
        'manual:guinguette:cm-2019-09-27' => 'bc0300fae3f0efb16997c49a8ec0284542452e647554efd413db04201245ef7c',
        'manual:guinguette:cm-2020-02-10' => '06ae6edbfafe7f880662ba2d18167138a38f883d074be94399d872177684ecff',
        'manual:guinguette:cm-2021-06-07' => 'ca2d2a258efae03d66698fae44d6f09bab84b841f0eaf83a8864f16c4f4de5f3',
        'manual:guinguette:cm-2021-10-12' => '346f3a1834000acacef3b7ded37b44f3327cb3bc806e15c199197f3b69ad202d',
        'manual:guinguette:cm-2022-06-02' => '3751d44961b5335d606f418a2df3ec55ff8a10567e989acced3eeeeccf885474',
        'manual:guinguette:cm-2022-06-28' => 'ead10c720144e0bd17d87dd507f4a5aabe4930c45fcd6c1340bd41d876f8ba7c',
        'manual:guinguette:cm-2023-03-29' => 'b50acfbc248f1aaa902ccec18edd407ddd9a857bc34d3516d9813e11e4ee2f84',
        'manual:guinguette:cm-2025-10-21' => '7b21e9f0cc7d35cb026071b8f28b7b6856d63370929d37fbe0ae0d423cacc371',
        'manual:guinguette:cm-2026-02-11' => 'bf83139dfe352ae24d9b318218bf008de4dc480df1aca6e6ae60b4c6318665ee',
    ];

    private function ingestGuinguetteWithDocs(): Subject
    {
        Storage::fake('documents');

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
            'requires_setup' => false,
        ]);

        $cat = Category::factory()->create(['id' => 6, 'name' => 'Patrimoine & Mémoire', 'slug' => 'patrimoine-memoire']);
        $sub = SubCategory::factory()->create(['id' => 21, 'category_id' => 6, 'name' => 'Foncier & cessions', 'slug' => 'foncier-cessions']);

        $base = "/home/aur-lien/Obsidian-Vault/LMLaP/Journal/2026-09-10-guinguette-staging";
        $body = file_get_contents($base.'/LMALP-GUINGUETTE-INSTRUCTION-V0.2.md');
        $citizen = file_get_contents($base.'/LMALP-GUINGUETTE-CITOYEN-V0.1.md');

        $subject = Subject::create([
            'user_id' => $admin->id,
            'theme' => 'Patrimoine & Mémoire',
            'category_id' => $cat->id,
            'sub_category_id' => $sub->id,
            'title' => 'La Guinguette / La Baraquita',
            'slug' => 'guinguette-urbanisme',
            'body' => $body,
            'citizen_body' => $citizen,
            'public_body' => null,
            'status' => 'draft',
            'citizen_status' => 'draft',
            'public_status' => 'draft',
            'public_is_listed' => false,
        ]);

        SubjectVersion::create([
            'subject_id' => $subject->id,
            'user_id' => $admin->id,
            'body' => $body,
            'citizen_body' => $citizen,
            'public_body' => null,
            'change_summary' => 'Ingestion initiale INSTRUCTION + CITOYEN',
        ]);

        // Ingest documents using the real seeder but with storage fake
        // Since the seeder uses app(DocumentStorageService) and stores on disk,
        // we need to use the real filesystem for files but can verify via DB.
        (new \Database\Seeders\GuinguetteDocumentSeeder())->run();

        return $subject->fresh();
    }

    // ============================================
    // 1. Comptage exact
    // ============================================
    // ============================================
    // 1. Comptage exact
    // ============================================
    public function test_exactly_ten_documents_for_subject_28(): void
    {
        $subject = $this->ingestGuinguetteWithDocs();
        $count = SubjectDocument::where('subject_id', $subject->id)->count();
        $this->assertEquals(10, $count, 'Subject #28 doit avoir exactement 10 documents.');
    }

    // ============================================
    // 2. Tous appartiennent à subject_id 28
    // ============================================
    public function test_all_documents_belong_to_subject_28(): void
    {
        $subject = $this->ingestGuinguetteWithDocs();
        $foreignSubjectIds = SubjectDocument::whereIn('source_reference', self::EXPECTED_REFS)
            ->pluck('subject_id')
            ->unique()
            ->all();
        $this->assertEquals([$subject->id], $foreignSubjectIds, 'Tous les documents attendus appartiennent au sujet #28.');
    }

    // ============================================
    // 3. source_reference unique
    // ============================================
    public function test_source_references_are_unique(): void
    {
        $this->ingestGuinguetteWithDocs();
        $duplicates = SubjectDocument::selectRaw('source_reference, COUNT(*) as cnt')
            ->whereIn('source_reference', self::EXPECTED_REFS)
            ->groupBy('source_reference')
            ->having('cnt', '>', 1)
            ->pluck('source_reference')
            ->all();
        $this->assertEmpty($duplicates, 'Aucune source_reference ne doit être dupliquée.');
    }

    // ============================================
    // 4. SHA-256 conforme
    // ============================================
    public function test_all_source_sha256_match_expected(): void
    {
        $this->ingestGuinguetteWithDocs();
        foreach (self::EXPECTED_SHA as $ref => $expectedSha) {
            $doc = SubjectDocument::where('source_reference', $ref)->first();
            $this->assertNotNull($doc, "Document {$ref} introuvable.");
            $this->assertEquals($expectedSha, $doc->source_sha256, "SHA mismatch pour {$ref}.");
        }
    }

    // ============================================
    // 5. Visibilité working
    // ============================================
    public function test_all_documents_are_working_visibility(): void
    {
        $this->ingestGuinguetteWithDocs();
        $subject = Subject::where('slug', 'guinguette-urbanisme')->first();
        $nonWorking = SubjectDocument::where('subject_id', $subject->id)
            ->where('visibility', '!=', VisibilityLevel::Working->value)
            ->get();
        $this->assertTrue($nonWorking->isEmpty(), "Tous les documents doivent \u00eatre visibility=working. Trouv\u00e9 : {$nonWorking->pluck('source_reference')->implode(', ')}");
    }

    // ============================================
    // 6. Guest ne voit aucun document
    // ============================================
    public function test_guest_sees_no_documents(): void
    {
        $subject = $this->ingestGuinguetteWithDocs();
        $guestVisible = SubjectDocument::where('subject_id', $subject->id)
            ->get()
            ->filter(fn ($doc) => $doc->visibleTo(null));
        $this->assertEmpty($guestVisible, 'Guest ne doit voir aucun document Working.');
    }

    // ============================================
    // 7. Citizen ne voit aucun document Working
    // ============================================
    public function test_citizen_sees_no_working_documents(): void
    {
        $subject = $this->ingestGuinguetteWithDocs();
        $citizen = User::factory()->create([
            'role' => 'citoyen',
            'email_verified_at' => now(),
            'requires_setup' => false,
        ]);
        $citizenVisible = SubjectDocument::where('subject_id', $subject->id)
            ->get()
            ->filter(fn ($doc) => $doc->visibleTo($citizen));
        $this->assertEmpty($citizenVisible, 'Citoyen ne doit voir aucun document Working.');
    }

    // ============================================
    // 8. Admin voit tous les documents
    // ============================================
    public function test_admin_sees_all_documents(): void
    {
        $subject = $this->ingestGuinguetteWithDocs();
        $admin = User::where('role', 'admin')->first();
        $adminVisible = SubjectDocument::where('subject_id', $subject->id)
            ->get()
            ->filter(fn ($doc) => $doc->visibleTo($admin));
        $this->assertCount(10, $adminVisible, 'Admin doit voir les 10 documents Working.');
    }

    // ============================================
    // 9. Aucun doublon du jugement TA
    // ============================================
    public function test_no_duplicate_jugement_ta(): void
    {
        $this->ingestGuinguetteWithDocs();
        $jugementDocs = SubjectDocument::where('source_reference', 'manual:guinguette:ta-2202355-2024-09-17')->get();
        $this->assertCount(1, $jugementDocs, 'Le jugement TA ne doit exister qu\'en un seul exemplaire.');
    }

    // ============================================
    // 10. Aucun placeholder artificiel créé
    // ============================================
    public function test_no_artificial_placeholder_documents(): void
    {
        $this->ingestGuinguetteWithDocs();
        $subject = Subject::where('slug', 'guinguette-urbanisme')->first();
        $unexpected = SubjectDocument::where('subject_id', $subject->id)
            ->whereNotIn('source_reference', self::EXPECTED_REFS)
            ->pluck('source_reference')
            ->all();
        $this->assertEmpty($unexpected, 'Aucun document avec source_reference inattendu ne doit exister.');
    }

    // ============================================
    // 11. Ordre documentaire conforme
    // ============================================
    public function test_document_order_matches_manifest(): void
    {
        $subject = $this->ingestGuinguetteWithDocs();
        $refsInDb = SubjectDocument::where('subject_id', $subject->id)
            ->orderBy('position')
            ->pluck('source_reference')
            ->all();
        $this->assertEquals(self::EXPECTED_REFS, $refsInDb, 'L\'ordre des documents doit correspondre au manifeste.');
    }

    // ============================================
    // 12. Bodies inchangés
    // ============================================
    public function test_body_sha_unchanged_after_ingestion(): void
    {
        $subject = $this->ingestGuinguetteWithDocs();
        $this->assertEquals(self::INSTRUCTION_SHA, hash('sha256', $subject->body));
    }

    public function test_citizen_body_sha_unchanged_after_ingestion(): void
    {
        $subject = $this->ingestGuinguetteWithDocs();
        $this->assertEquals(self::CITIZEN_SHA, hash('sha256', $subject->citizen_body));
    }

    public function test_public_body_remains_null(): void
    {
        $subject = $this->ingestGuinguetteWithDocs();
        $this->assertNull($subject->public_body);
    }

    // ============================================
    // 13. No DB records for 20 missing claims
    // ============================================
    public function test_no_db_records_for_missing_documents(): void
    {
        $this->ingestGuinguetteWithDocs();
        $missingRefs = [
            'manual:guinguette:pv-386-2022',
            'manual:guinguette:decision-dp-2022-03-10',
            'manual:guinguette:decision-dp-2022-03-17',
            'manual:guinguette:decision-pc-2022-05-10',
            'manual:guinguette:courrier-prefet-2024-02-02',
            'manual:guinguette:acte-delegation-ccmgc',
            'manual:guinguette:echange-superficies-b513-b655',
            'manual:guinguette:memoire-frank',
            'manual:guinguette:memoire-prefet',
            'manual:guinguette:memoire-commune',
            'manual:guinguette:requete-ta-2022-08-01',
            'manual:guinguette:cr-audience-2024-09-03',
            'manual:guinguette:avis-abf-2025',
            'manual:guinguette:avis-udap',
            'manual:guinguette:avis-drac',
            'manual:guinguette:decision-pc-2023',
            'manual:guinguette:decision-nouveau-permis-2025',
            'manual:guinguette:courrier-precontentieux-enard-bazire',
            'manual:guinguette:courrier-frank-2022-05-27',
            'manual:guinguette:courrier-prefet-classement',
        ];

        $subject = Subject::where('slug', 'guinguette-urbanisme')->first();
        foreach ($missingRefs as $ref) {
            $this->assertDatabaseMissing('subject_documents', [
                'subject_id' => $subject->id,
                'source_reference' => $ref,
            ]);
        }
    }
}
