<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\User;
use App\Models\VisibilityLevel;
use App\Services\DocumentStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * QA mécanique du patch V8-A : body, correspondance, DGFIP, UX mobile, ACL.
 */
class SubjectSeraphothequeV8ReviewTest extends TestCase
{
    use RefreshDatabase;

    private string $v6Pack;
    private string $v8Body;
    private string $v8Sha;
    private string $v8CorrespondenceSha;
    private DocumentStorageService $storage;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
        Storage::fake('local');

        $this->v6Pack = '/home/aur-lien/Obsidian-Vault/LEX/08-publication/seraphotheque-v1/PUBLIC-V6-FINAL';
        $this->v8Body = file_get_contents('/home/aur-lien/Obsidian-Vault/LMLaP/Journal/2026-08-26-SERAPHOTHEQUE-V8-SOURCES/Comprendre-en-1-Minute-V8-REVIEW-FINAL.md');
        $this->v8Sha = 'dab086216c2fb8cc8034d4184d1acd2415be856a07c394752dcb1a32df1500fd';
        $this->v8CorrespondenceSha = '10e218073b92a3928577ababcd0c0ba0056c3bf54d80c97dbcf854074c7dfa72';
        $this->storage = new DocumentStorageService('documents');
    }

    private function seedUserAndTaxonomy(): User
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
            'requires_setup' => false,
        ]);

        Category::factory()->create(['id' => 10, 'name' => 'Vie du village', 'slug' => 'vie-du-village']);
        SubCategory::factory()->create(['id' => 14, 'category_id' => 10, 'name' => 'Séraphothèque', 'slug' => 'seraphotheque']);

        return $admin;
    }

    private function ingestV8Subject(): Subject
    {
        $admin = $this->seedUserAndTaxonomy();

        $exit = Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $this->v6Pack,
            '--user-id' => $admin->id,
            '--sync-bodies' => true,
        ]);
        $this->assertSame(0, $exit, 'Ingestion V6 corpus documentaire doit réussir.');

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();

        $citizenBefore = $subject->citizen_body;
        $workingBefore = $subject->body;

        $subject->update([
            'public_body' => $this->v8Body,
            'public_status' => 'published',
            'public_published_at' => now(),
            'public_is_listed' => false,
            'theme' => 'Séraphothèque',
        ]);

        $fresh = $subject->fresh();
        $this->assertSame($citizenBefore, $fresh->citizen_body, 'Citizen body doit rester inchangé.');
        $this->assertSame($workingBefore, $fresh->body, 'Working body doit rester inchangé.');

        // Matrice V8 : MERGE 2 cartes autonomes vers la correspondance.
        SubjectDocument::where('subject_id', $fresh->id)
            ->where(function ($q) {
                $q->where('source_reference', 'seraphotheque-pack:Email-2026-07-01')
                  ->orWhere('source_reference', 'seraphotheque-pack:SERAPH-DOC-1263');
            })
            ->update(['visibility' => VisibilityLevel::Working->value]);

        // Correspondance V8 : update fiche publique avec type DOSSIER DOCUMENTAIRE et contenu V8.
        $corrDoc = SubjectDocument::where('subject_id', $fresh->id)
            ->where('source_reference', 'seraphotheque-pack:SERAPH-DOC-CORRESPONDANCE-MAIRIE-2026')
            ->first();
        $this->assertNotNull($corrDoc, 'Document correspondance trouvé.');
        $corrContent = file_get_contents('/home/aur-lien/Obsidian-Vault/LMLaP/Journal/2026-08-26-SERAPHOTHEQUE-V8-SOURCES/CORRESPONDANCE-SERAPHOTHEQUE-MAIRIE-2026-V8_PUBLIC.md');
        $this->assertSame($this->v8CorrespondenceSha, hash('sha256', $corrContent), 'SHA correspondance V8.');

        $tmp = tempnam(sys_get_temp_dir(), 'corr_');
        file_put_contents($tmp, $corrContent);
        $stored = $this->storage->storeEncrypted($fresh->id, $tmp, 'CORRESPONDANCE-V8.md');
        unlink($tmp);

        $corrDoc->update([
            'title' => 'Correspondance avec la mairie — 31 mars au 1er juillet 2026 (V8)',
            'document_type' => 'dossier documentaire',
            'description' => 'Compilation chronologique des échanges pertinents des exploitants et de la mairie, avec provenance de chaque message.',
            'author' => 'Anna El Agri, Aurélien Tisserand, Mairie du Rozier',
            'filename' => 'CORRESPONDANCE-V8.md',
            'stored_filename' => basename($stored),
            'path' => $stored,
            'mime_type' => 'text/markdown',
            'size' => strlen($corrContent),
            'source_sha256' => $this->v8CorrespondenceSha,
        ]);

        // DGFIP 27 mai : ADD source primaire HTML.
        $dgfipPayload = json_encode([
            'date' => '2026-05-27',
            'subject' => 'Rejets Virements',
            'from' => 'DGFIP / SGC Saint-Affrique',
            'to' => 'Aurélien Tisserand',
            'body_text' => "Bonjour Mr TISSERAND\nAprès avoir fait le point avec la Commune du Rozier vous n'êtes plus que redevable d'un loyer de 40€/ mois, celui de 70€ s'étant arrêté au mois de mars.\nNous recevons ce jour la somme de 80€ et de 700€ de votre part, à quoi cela correspond t'il ?\nSachez que vous êtes à jour dans le paiement de vos loyers.\nMerci de me transmettre les factures qui vous ont conduit à faire ces versements.\nA défaut de réception, je rejetterai ces 2 versements.",
        ], JSON_UNESCAPED_UNICODE);

        $tmp2 = tempnam(sys_get_temp_dir(), 'dgfip_');
        file_put_contents($tmp2, $dgfipPayload);
        $storedDgfip = $this->storage->storeEncrypted($fresh->id, $tmp2, 'dgfip-rejets-virements-2026-05-27.json');
        unlink($tmp2);

        SubjectDocument::create([
            'subject_id' => $fresh->id,
            'user_id' => $admin->id,
            'title' => 'Trésor public — « Rejets Virements » — 27 mai 2026',
            'filename' => 'dgfip-rejets-virements-2026-05-27.html',
            'stored_filename' => basename($storedDgfip),
            'path' => $storedDgfip,
            'disk' => 'documents',
            'description' => 'Message du contrôleur des finances publiques relatif aux virements de 80 € et 700 €.',
            'category' => 'source',
            'visibility' => VisibilityLevel::Public->value,
            'document_date' => '2026-05-27',
            'document_type' => 'email',
            'author' => 'DGFIP / SGC Saint-Affrique',
            'recipient' => 'Aurélien Tisserand',
            'mime_type' => 'application/json',
            'size' => strlen($dgfipPayload),
            'source_reference' => 'gmail:19e686ddc7c6d792',
            'source_sha256' => hash('sha256', $dgfipPayload),
            'position' => 999,
        ]);

        return $fresh->fresh();
    }

    private function guestShowResponse(Subject $subject): \Illuminate\Testing\TestResponse
    {
        Storage::fake('documents');

        return $this->get(route('subjects.show', $subject->slug));
    }

    /** @test */
    public function v8_source_authoritative_sha_matches(): void
    {
        $this->assertSame($this->v8Sha, hash('sha256', $this->v8Body), 'V8 source SHA contract.');
    }

    /** @test */
    public function public_body_is_v8_exact_and_preserves_other_bodies(): void
    {
        $subject = $this->ingestV8Subject();
        $this->assertSame($this->v8Sha, hash('sha256', (string) $subject->public_body), 'public_body est V8 exact.');
        $this->assertNotNull($subject->body);
        $this->assertNotNull($subject->citizen_body);
        $this->assertNotSame($subject->public_body, $subject->body, 'Working body distinct du public.');
        $this->assertNotSame($subject->public_body, $subject->citizen_body, 'Citizen body distinct du public.');
    }

    /** @test */
    public function v8_body_occurrence_is_exact_once(): void
    {
        $subject = $this->ingestV8Subject();
        $html = $this->guestShowResponse($subject)->baseResponse->getContent();

        // "Dossier documentaire — mis à jour le 26 août 2026" est le lead V8.
        $this->assertEquals(1, substr_count($html, 'Dossier documentaire — mis à jour le 26 août 2026'), 'Lead V8 unique.');
    }

    /** @test */
    public function canonical_h2_ids_present_and_valid(): void
    {
        $subject = $this->ingestV8Subject();
        $html = $this->guestShowResponse($subject)->baseResponse->getContent();

        $expected = [
            'comprendre',
            'enjeux',
            'changements-2026',
            'chronologie',
            'positions',
            'desaccords',
            'questions-ouvertes',
            'documents',
            'lire-les-sources',
        ];

        foreach ($expected as $id) {
            $this->assertStringContainsString('<h2 id="' . $id . '"', $html, "H2 manquant : {$id}");
        }

        $this->assertStringNotContainsString('<h2id=', $html);
        $this->assertStringNotContainsString('<h3id=', $html);
    }

    /** @test */
    public function wording_contract_v8_facts(): void
    {
        $subject = $this->ingestV8Subject();
        $html = $this->guestShowResponse($subject)->baseResponse->getContent();

        // Présents
        $this->assertStringContainsString('Dossier documentaire — mis à jour le 26 août 2026', $html);
        $this->assertStringContainsString('31 mars 2026, à la veille du renouvellement prévu au 1er avril', $html);
        $this->assertStringContainsString('Point dossiers en cours', $html);
        $this->assertStringContainsString('DGFIP', $html);
        $this->assertStringContainsString('27 mai', $html);
        $this->assertStringContainsString('28 mai', $html);
        $this->assertStringContainsString('1er octobre 2023', $html);
        $this->assertStringContainsString('Soutenir les commerçants qui restent ouverts à l’année', $html);

        // Absents
        $this->assertStringNotContainsString('À propos de ce dossier', $html);
    }

    /** @test */
    public function no_draft_badge_and_no_global_pdf_cta_for_guest(): void
    {
        $subject = $this->ingestV8Subject();
        $response = $this->guestShowResponse($subject);

        $response->assertDontSee('Brouillon');
        $response->assertDontSee('Ouvrir le PDF');
        $response->assertDontSee('Télécharger le PDF');
        $response->assertDontSee('btn-pdf-show');
        $response->assertDontSee('btn-pdf-download');
    }

    /** @test */
    public function auth_public_route_serves_v8_not_draft_or_citizen(): void
    {
        $subject = $this->ingestV8Subject();

        $citizen = User::factory()->create([
            'role' => 'citoyen',
            'email_verified_at' => now(),
            'requires_setup' => false,
        ]);

        $this->actingAs($citizen)
            ->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('Dossier documentaire — mis à jour le 26 août 2026')
            ->assertSee('Point dossiers en cours');
    }

    /** @test */
    public function merged_cards_are_no_longer_public(): void
    {
        $subject = $this->ingestV8Subject();

        $merged = SubjectDocument::where('subject_id', $subject->id)
            ->where(function ($q) {
                $q->where('source_reference', 'seraphotheque-pack:Email-2026-07-01')
                  ->orWhere('source_reference', 'seraphotheque-pack:SERAPH-DOC-1263');
            })
            ->get();

        $this->assertCount(2, $merged);
        foreach ($merged as $doc) {
            $this->assertSame(VisibilityLevel::Working->value, $doc->visibility->value, "{$doc->source_reference} doit être en working.");
        }

        $html = $this->guestShowResponse($subject)->baseResponse->getContent();
        $this->assertStringNotContainsString('Information déplacement portants', $html);
        $this->assertStringNotContainsString('Email maire 14 mai 2026', $html);
    }

    /** @test */
    public function correspondance_v8_is_dossier_documentaire(): void
    {
        $subject = $this->ingestV8Subject();
        $html = $this->guestShowResponse($subject)->baseResponse->getContent();

        $this->assertStringContainsString('Correspondance avec la mairie — 31 mars au 1er juillet 2026 (V8)', $html);
        $this->assertStringContainsString('DOSSIER DOCUMENTAIRE', $html);
    }

    /** @test */
    public function sommation_0904_public_minimal_remains_accessible(): void
    {
        $subject = $this->ingestV8Subject();

        $doc = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'like', '%SERAPH-DOC-0904%')
            ->firstOrFail();

        $this->assertSame(VisibilityLevel::Public->value, $doc->visibility->value);
        $this->assertStringContainsString('SERAPH-DOC-0904', (string) $doc->source_reference);

        $this->get(route('subjects.documents.view', [$subject->slug, $doc->id]))
            ->assertOk();
    }

    /** @test */
    public function dgfip_27mai_email_renders_as_public_html(): void
    {
        $subject = $this->ingestV8Subject();

        $doc = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'gmail:19e686ddc7c6d792')
            ->firstOrFail();

        $response = $this->get(route('subjects.documents.email', [$subject->slug, $doc->id]));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $response->assertSee('DGFIP / SGC Saint-Affrique');
        $response->assertSee('Rejets Virements');
        $response->assertSee('40€/ mois');
        $response->assertSee('80€');
        $response->assertSee('700€');
        $response->assertDontSee('"body_text"');
        $response->assertDontSee('gmail:19e686ddc7c6d792');
    }

    /** @test */
    public function bordereau_dgfip_26mai_is_not_public(): void
    {
        $subject = $this->ingestV8Subject();

        $bordereau = SubjectDocument::where('subject_id', $subject->id)
            ->where(function ($q) {
                $q->where('source_reference', 'like', '%0774%')
                  ->orWhere('title', 'like', '%bordereau%')
                  ->orWhere('title', 'like', '%26 mai%');
            })
            ->first();

        if ($bordereau) {
            $html = $this->guestShowResponse($subject)->baseResponse->getContent();
            $this->assertStringNotContainsString($bordereau->title, $html);
        }

        $html = $this->guestShowResponse($subject)->baseResponse->getContent();
        $this->assertStringNotContainsString('Bordereau DGFIP', $html);
    }

    /** @test */
    public function public_is_listed_false_and_no_original_fallback(): void
    {
        $subject = $this->ingestV8Subject();

        $this->assertFalse($subject->public_is_listed);
        $this->assertNull($subject->published_at);
    }

    /** @test */
    public function discussion_is_hidden_for_guest(): void
    {
        $subject = $this->ingestV8Subject();
        $response = $this->guestShowResponse($subject);

        $response->assertDontSee('Discussion');
        $response->assertDontSee('Commenter');
        $response->assertDontSee('id="comments"');
    }

    /** @test */
    public function narrative_anchors_use_source_reference_and_no_leak(): void
    {
        $subject = $this->ingestV8Subject();
        $html = $this->guestShowResponse($subject)->baseResponse->getContent();

        $expectedIds = [
            'doc-seraphotheque-pack-SERAPH-DOC-0535',
            'doc-seraphotheque-pack-SERAPH-DOC-0239',
            'doc-seraphotheque-pack-SERAPH-DOC-0904',
            'doc-seraphotheque-pack-SERAPH-DOC-CORRESPONDANCE-MAIRIE-2026',
            'doc-seraphotheque-pack-SERAPH-DOC-0293',
            'doc-seraphotheque-pack-SERAPH-DOC-0997',
            'doc-seraphotheque-pack-SERAPH-DOC-0486',
            'doc-seraphotheque-pack-COMP-2025-2026',
            'doc-seraphotheque-pack-SERAPH-DOC-PROFESSION-FOI',
        ];

        foreach ($expectedIds as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $html, "Ancre documentaire manquante : #{$id}");
        }

        $this->assertStringNotContainsString('drive.google.com', $html);
        $this->assertStringNotContainsString('/home/', $html);
        $this->assertStringNotContainsString('Obsidian-Vault', $html);
        $this->assertStringNotContainsString('storage/subjects/', $html);
    }
}
