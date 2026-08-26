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
 * QA mécanique du patch V9-A : body, correspondance, DGFIP, CTA, taxonomie, ACL.
 */
class SubjectSeraphothequeV9PreDiffusionTest extends TestCase
{
    use RefreshDatabase;

    private string $v6Pack;
    private string $v9Body;
    private string $v9BodySha;
    private string $v9Correspondence;
    private string $v9CorrespondenceSha;
    private DocumentStorageService $storage;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
        Storage::fake('local');

        $this->v6Pack = '/home/aur-lien/Obsidian-Vault/LEX/08-publication/seraphotheque-v1/PUBLIC-V6-FINAL';
        $this->v9Body = file_get_contents('/home/aur-lien/Obsidian-Vault/LMLaP/Journal/2026-08-26-SERAPHOTHEQUE-V9-SOURCES/Comprendre-en-1-Minute-V9-PRE-DIFFUSION.md');
        $this->v9BodySha = '42ab06a54188bc9ecfdad0977a8ad233585cccbf96bd8356e9d4eb2fb77b6cec';
        $this->v9Correspondence = file_get_contents('/home/aur-lien/Obsidian-Vault/LMLaP/Journal/2026-08-26-SERAPHOTHEQUE-V9-SOURCES/CORRESPONDANCE-SERAPHOTHEQUE-MAIRIE-2026-V9_PUBLIC.md');
        $this->v9CorrespondenceSha = 'dfe6b04799ab7786f394a21c52ca4112573635ef3132dae46b5d1e3363a4d792';
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

    private function ingestV9Subject(): Subject
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
            'public_body' => $this->v9Body,
            'public_status' => 'published',
            'public_published_at' => now(),
            'public_is_listed' => false,
            'theme' => 'Séraphothèque',
        ]);

        $fresh = $subject->fresh();
        $this->assertSame($citizenBefore, $fresh->citizen_body, 'Citizen body doit rester inchangé.');
        $this->assertSame($workingBefore, $fresh->body, 'Working body doit rester inchangé.');

        // V9 merges : Email maire 14 mai + déplacement portants restent Working.
        // Email maire 3 avril devient Working (carte autonome retirée, source conservée).
        SubjectDocument::where('subject_id', $fresh->id)
            ->where(function ($q) {
                $q->where('source_reference', 'seraphotheque-pack:Email-2026-07-01')
                  ->orWhere('source_reference', 'seraphotheque-pack:SERAPH-DOC-1263')
                  ->orWhere('source_reference', 'seraphotheque-pack:Email-2026-04-03-PUBLIC');
            })
            ->update(['visibility' => VisibilityLevel::Working->value]);

        // Taxonomie publique V9.
        SubjectDocument::where('subject_id', $fresh->id)
            ->where('source_reference', 'seraphotheque-pack:SERAPH-DOC-0997')
            ->update(['document_type' => 'source primaire']);
        SubjectDocument::where('subject_id', $fresh->id)
            ->where('source_reference', 'seraphotheque-pack:SERAPH-DOC-0486')
            ->update(['document_type' => 'source primaire']);
        SubjectDocument::where('subject_id', $fresh->id)
            ->where('source_reference', 'seraphotheque-pack:COMP-2025-2026')
            ->update(['document_type' => 'comparatif documentaire']);
        SubjectDocument::where('subject_id', $fresh->id)
            ->where('source_reference', 'seraphotheque-pack:SERAPH-DOC-PROFESSION-FOI')
            ->update(['document_type' => 'document de contexte']);
        SubjectDocument::where('subject_id', $fresh->id)
            ->whereIn('source_reference', [
                'seraphotheque-pack:SERAPH-DOC-0535',
                'seraphotheque-pack:SERAPH-DOC-0239',
                'seraphotheque-pack:SERAPH-DOC-0904',
                'seraphotheque-pack:SERAPH-DOC-0293',
            ])
            ->update(['document_type' => 'source primaire']);

        // Correspondance V9 : remplace la représentation publique du document existant.
        $corrDoc = SubjectDocument::where('subject_id', $fresh->id)
            ->where('source_reference', 'seraphotheque-pack:SERAPH-DOC-CORRESPONDANCE-MAIRIE-2026')
            ->first();
        $this->assertNotNull($corrDoc, 'Document correspondance trouvé.');
        $this->assertSame($this->v9CorrespondenceSha, hash('sha256', $this->v9Correspondence), 'SHA correspondance V9.');

        $tmp = tempnam(sys_get_temp_dir(), 'corr_');
        file_put_contents($tmp, $this->v9Correspondence);
        $stored = $this->storage->storeEncrypted($fresh->id, $tmp, 'CORRESPONDANCE-V9.md');
        unlink($tmp);

        $corrDoc->update([
            'title' => 'Correspondance avec la mairie — 31 mars au 1er juillet 2026 (V9)',
            'document_type' => 'dossier documentaire',
            'description' => 'Compilation chronologique des échanges pertinents des exploitants et de la mairie, dans les deux sens et dans leur ordre.',
            'author' => 'Anna El Agri, Aurélien Tisserand, Mairie du Rozier',
            'filename' => 'CORRESPONDANCE-V9.md',
            'stored_filename' => basename($stored),
            'path' => $stored,
            'mime_type' => 'text/markdown',
            'size' => strlen($this->v9Correspondence),
            'source_sha256' => $this->v9CorrespondenceSha,
        ]);

        // DGFIP 27 mai : ADD source primaire avec représentation HTML humaine.
        $dgfipPayload = json_encode([
            'date' => '2026-05-27',
            'subject' => 'Rejets Virements',
            'from' => 'DGFIP / SGC Saint-Affrique',
            'to' => 'Aurélien Tisserand',
            'body_html' => '<p>Bonjour M. Tisserand,</p>'
                . '<p>Après avoir fait le point avec la Commune du Rozier, vous n\'êtes plus redevable que d\'un loyer de 40 €/mois, celui de 70 € s\'étant arrêté au mois de mars.</p>'
                . '<p>Nous recevons ce jour la somme de <strong>80 €</strong> et de <strong>700 €</strong> de votre part. À quoi cela correspond-t-il ?</p>'
                . '<p>Sachez que vous êtes à jour dans le paiement de vos loyers.</p>'
                . '<p>Merci de me transmettre les factures qui vous ont conduit à faire ces versements. À défaut de réception, je rejetterai ces 2 versements.</p>',
        ], JSON_UNESCAPED_UNICODE);

        $tmpDgfip = tempnam(sys_get_temp_dir(), 'dgfip_');
        file_put_contents($tmpDgfip, $dgfipPayload);
        $storedDgfip = $this->storage->storeEncrypted($fresh->id, $tmpDgfip, 'dgfip-rejets-virements-2026-05-27.json');
        unlink($tmpDgfip);

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
            'document_type' => 'source primaire',
            'author' => 'DGFIP / SGC Saint-Affrique',
            'recipient' => 'Aurélien Tisserand',
            'mime_type' => 'application/json',
            'size' => strlen($dgfipPayload),
            'source_reference' => 'seraphotheque-pack:Email-2026-05-27-DGFIP',
            'source_sha256' => hash('sha256', $dgfipPayload),
            'position' => 999,
        ]);

        // Email maire 3 avril : source conservée, carte publique retirée.
        $email3avrilHtml = file_get_contents('/home/aur-lien/Obsidian-Vault/LMLaP/03-SUJETS/seraphotheque/PUBLIC-V1-PATCH-02-STAGING/D-EMAIL-RENouvellement/email-renouvellement-conditions_2026-04-03_PUBLIC.html');
        $email3avrilPayload = json_encode([
            'date' => '2026-04-03',
            'subject' => 'Bail precaire',
            'from' => 'Arnaud CURVELIER',
            'to' => 'Aurélien TISSERAND, secrétariat mairie du Rozier, Anna EL AGRI',
            'body_html' => $email3avrilHtml,
        ], JSON_UNESCAPED_UNICODE);

        $tmp3 = tempnam(sys_get_temp_dir(), 'email3_');
        file_put_contents($tmp3, $email3avrilPayload);
        $storedEmail3 = $this->storage->storeEncrypted($fresh->id, $tmp3, 'email-renouvellement-conditions_2026-04-03_PUBLIC.html');
        unlink($tmp3);

        SubjectDocument::create([
            'subject_id' => $fresh->id,
            'user_id' => $admin->id,
            'title' => 'Email du maire — « Bail precaire » — 3 avril 2026',
            'filename' => 'email-renouvellement-conditions_2026-04-03_PUBLIC.html',
            'stored_filename' => basename($storedEmail3),
            'path' => $storedEmail3,
            'disk' => 'documents',
            'description' => 'Échange relatif au renouvellement et aux conditions du bail, 3 avril 2026.',
            'category' => 'source',
            'visibility' => VisibilityLevel::Working->value,
            'document_date' => '2026-04-03',
            'document_type' => 'source primaire',
            'author' => 'Arnaud CURVELIER',
            'recipient' => 'Aurélien TISSERAND, secrétariat mairie du Rozier, Anna EL AGRI',
            'mime_type' => 'application/json',
            'size' => strlen($email3avrilPayload),
            'source_reference' => 'seraphotheque-pack:Email-2026-04-03-PUBLIC',
            'source_sha256' => hash('sha256', $email3avrilPayload),
            'position' => 997,
        ]);

        return $fresh->fresh();
    }

    private function guestShowResponse(Subject $subject): \Illuminate\Testing\TestResponse
    {
        return $this->get(route('subjects.show', $subject->slug));
    }

    /** @test */
    public function v9_source_authoritative_sha_matches(): void
    {
        $this->assertSame($this->v9BodySha, hash('sha256', $this->v9Body), 'V9 source SHA contract.');
        $this->assertSame($this->v9CorrespondenceSha, hash('sha256', $this->v9Correspondence), 'V9 correspondence SHA contract.');
    }

    /** @test */
    public function public_body_is_v9_exact_and_preserves_other_bodies(): void
    {
        $subject = $this->ingestV9Subject();
        $this->assertSame($this->v9BodySha, hash('sha256', (string) $subject->public_body), 'public_body est V9 exact.');
        $this->assertNotNull($subject->body);
        $this->assertNotNull($subject->citizen_body);
        $this->assertNotSame($subject->public_body, $subject->body, 'Working body distinct du public.');
        $this->assertNotSame($subject->public_body, $subject->citizen_body, 'Citizen body distinct du public.');
    }

    /** @test */
    public function v9_body_occurrence_is_exact_once(): void
    {
        $subject = $this->ingestV9Subject();
        $html = $this->guestShowResponse($subject)->baseResponse->getContent();

        $this->assertEquals(1, substr_count($html, 'Dossier documentaire — mis à jour le 26 août 2026'), 'Lead V9 unique.');
    }

    /** @test */
    public function canonical_h2_ids_present_and_valid(): void
    {
        $subject = $this->ingestV9Subject();
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
        ];

        foreach ($expected as $id) {
            $this->assertStringContainsString('<h2 id="' . $id . '"', $html, "H2 manquant : {$id}");
        }

        $this->assertStringNotContainsString('<h2id=', $html);
        $this->assertStringNotContainsString('<h3id=', $html);
    }

    /** @test */
    public function wording_contract_v9_facts(): void
    {
        $subject = $this->ingestV9Subject();
        $html = $this->guestShowResponse($subject)->baseResponse->getContent();

        // Présents
        $this->assertStringContainsString('Dossier documentaire — mis à jour le 26 août 2026', $html);
        $this->assertStringContainsString('31 mars 2026, à la veille du renouvellement prévu au 1er avril', $html);
        $this->assertStringContainsString('Point dossiers en cours', $html);
        $this->assertStringContainsString('19 juin 2026', $html);
        $this->assertStringContainsString('DGFIP', $html);
        $this->assertStringContainsString('27 mai', $html);
        $this->assertStringContainsString('1er octobre 2023', $html);
        $this->assertStringContainsString('Soutenir les commerçants qui restent ouverts à l’année', $html);
        $this->assertStringContainsString('ancien local du Crédit Agricole', $html);

        // Absents
        $this->assertStringNotContainsString('À propos de ce dossier', $html);
    }

    /** @test */
    public function no_draft_badge_and_no_global_pdf_cta_for_guest(): void
    {
        $subject = $this->ingestV9Subject();
        $response = $this->guestShowResponse($subject);

        $response->assertDontSee('Brouillon');
        $response->assertDontSee('Ouvrir le PDF');
        $response->assertDontSee('Télécharger le PDF');
        $response->assertDontSee('btn-pdf-show');
        $response->assertDontSee('btn-pdf-download');
    }

    /** @test */
    public function auth_public_route_serves_v9_not_draft_or_citizen(): void
    {
        $subject = $this->ingestV9Subject();

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
    public function merged_email_14mai_and_1juillet_are_not_public(): void
    {
        $subject = $this->ingestV9Subject();

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
    public function email_3_avril_card_removed_but_source_preserved(): void
    {
        $subject = $this->ingestV9Subject();

        $emailDoc = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'seraphotheque-pack:Email-2026-04-03-PUBLIC')
            ->firstOrFail();

        $this->assertSame(VisibilityLevel::Working->value, $emailDoc->visibility->value, 'Email 3 avril devient Working.');

        $html = $this->guestShowResponse($subject)->baseResponse->getContent();
        $this->assertStringNotContainsString('Email du maire — « Bail precaire » — 3 avril 2026', $html);
        $this->assertStringNotContainsString('seraphotheque-pack:Email-2026-04-03-PUBLIC', $html);
    }

    /** @test */
    public function taxonomy_v9_public_cards_are_exact(): void
    {
        $subject = $this->ingestV9Subject();
        $html = $this->guestShowResponse($subject)->baseResponse->getContent();

        preg_match_all('/<span[^>]*uppercase[^>]*>([^<]+)<\/span>/', $html, $matches);
        $badgeCounts = array_count_values(array_map('trim', $matches[1]));

        $expected = [
            'SOURCE PRIMAIRE' => 7,
            'DOSSIER DOCUMENTAIRE' => 1,
            'COMPARATIF DOCUMENTAIRE' => 1,
            'DOCUMENT DE CONTEXTE' => 1,
        ];

        foreach ($expected as $label => $count) {
            $this->assertSame($count, $badgeCounts[$label] ?? 0, "Badge {$label} doit apparaître {$count} fois.");
        }

        $this->assertSame(0, $badgeCounts['SYNTHÈSE LMALP'] ?? 0, 'Aucun badge SYNTHÈSE LMALP dans les cartes publiques V9.');
        $this->assertSame(0, $badgeCounts['POSITION / DÉMARCHE'] ?? 0, 'Aucun badge POSITION / DÉMARCHE dans les cartes publiques V9.');
    }

    /** @test */
    public function correspondance_v9_serves_html_human_not_raw_markdown(): void
    {
        $subject = $this->ingestV9Subject();

        $corr = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'like', '%SERAPH-DOC-CORRESPONDANCE-MAIRIE-2026%')
            ->firstOrFail();

        $response = $this->get(route('subjects.documents.view', [$subject->slug, $corr->id]));
        $content = (string) $response->baseResponse->getContent();

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');

        // Source Markdown V9 ne doit pas être servi brut au Guest
        $this->assertStringNotContainsString('# Correspondance Séraphothèque', $content);
        $this->assertStringNotContainsString('**Provenance :**', $content);
        $this->assertStringNotContainsString('---', $content);

        // Représentation humaine présente
        $response->assertSee('Correspondance avec la mairie');
        $response->assertSee('31 mars 2026');
        $response->assertSee('Mairie du Rozier');
        $response->assertSee('Arnaud Curvelier');
        $response->assertSee('DOSSIER DOCUMENTAIRE');

        // V9 : note documentaire unique pour la séquence reconstituée
        $response->assertSee('Note documentaire');
        $response->assertSee('connus par une copie contemporaine');
    }

    /** @test */
    public function correspondance_v9_source_sha_preserved(): void
    {
        $subject = $this->ingestV9Subject();

        $corr = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'like', '%SERAPH-DOC-CORRESPONDANCE-MAIRIE-2026%')
            ->firstOrFail();

        $this->assertSame($this->v9CorrespondenceSha, $corr->source_sha256);

        $this->assertSame(
            $this->v9CorrespondenceSha,
            hash('sha256', $this->storage->decrypt($corr->path)),
            'Contenu chiffré correspondance V9 inchangé.'
        );
    }

    /** @test */
    public function dgfip_27mai_email_renders_as_public_html(): void
    {
        $subject = $this->ingestV9Subject();

        $doc = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'seraphotheque-pack:Email-2026-05-27-DGFIP')
            ->firstOrFail();

        $response = $this->get(route('subjects.documents.email', [$subject->slug, $doc->id]));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $response->assertSee('DGFIP / SGC Saint-Affrique');
        $response->assertSee('Rejets Virements');
        $response->assertSee('40 €/mois');
        $response->assertSee('80 €');
        $response->assertSee('700 €');
        $response->assertDontSee('"body_html"');
        $response->assertDontSee('seraphotheque-pack:Email-2026-05-27-DGFIP');
    }

    /** @test */
    public function bordereau_dgfip_26mai_is_not_public(): void
    {
        $subject = $this->ingestV9Subject();

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
    public function sommation_0904_public_minimal_remains_accessible(): void
    {
        $subject = $this->ingestV9Subject();

        $doc = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'like', '%SERAPH-DOC-0904%')
            ->firstOrFail();

        $this->assertSame(VisibilityLevel::Public->value, $doc->visibility->value);
        $this->assertStringContainsString('SERAPH-DOC-0904', (string) $doc->source_reference);

        $this->get(route('subjects.documents.view', [$subject->slug, $doc->id]))
            ->assertOk();
    }

    /** @test */
    public function public_is_listed_false_and_no_original_fallback(): void
    {
        $subject = $this->ingestV9Subject();

        $this->assertFalse($subject->public_is_listed);
        $this->assertNull($subject->published_at);
    }

    /** @test */
    public function discussion_is_hidden_for_guest(): void
    {
        $subject = $this->ingestV9Subject();
        $response = $this->guestShowResponse($subject);

        $response->assertDontSee('Discussion');
        $response->assertDontSee('Commenter');
        $response->assertDontSee('id="comments"');
    }

    /** @test */
    public function public_document_count_and_identity_are_locked_v9(): void
    {
        $subject = $this->ingestV9Subject();

        $expectedCount = 10;
        $expectedSourceReferences = [
            'seraphotheque-pack:SERAPH-DOC-0535',
            'seraphotheque-pack:SERAPH-DOC-0239',
            'seraphotheque-pack:SERAPH-DOC-0904',
            'seraphotheque-pack:SERAPH-DOC-0293',
            'seraphotheque-pack:SERAPH-DOC-0997',
            'seraphotheque-pack:SERAPH-DOC-0486',
            'seraphotheque-pack:COMP-2025-2026',
            'seraphotheque-pack:SERAPH-DOC-PROFESSION-FOI',
            'seraphotheque-pack:SERAPH-DOC-CORRESPONDANCE-MAIRIE-2026',
            'seraphotheque-pack:Email-2026-05-27-DGFIP',
        ];

        $publicDocs = SubjectDocument::where('subject_id', $subject->id)
            ->where('visibility', VisibilityLevel::Public)
            ->get();

        $this->assertCount($expectedCount, $publicDocs, "Nombre de documents publics doit être {$expectedCount}.");

        $actualRefs = $publicDocs->pluck('source_reference')->sort()->values()->toArray();
        $this->assertSame(
            collect($expectedSourceReferences)->sort()->values()->toArray(),
            $actualRefs,
            'Identité exacte des documents publics V9.'
        );
    }

    /** @test */
    public function working_document_count_and_identity_are_locked_v9(): void
    {
        $subject = $this->ingestV9Subject();

        $expectedRefs = [
            'seraphotheque-pack:SERAPH-DOC-1263',
            'seraphotheque-pack:Email-2026-07-01',
            'seraphotheque-pack:Email-2026-04-03-PUBLIC',
        ];

        $workingDocs = SubjectDocument::where('subject_id', $subject->id)
            ->where('visibility', VisibilityLevel::Working)
            ->get();

        $this->assertCount(3, $workingDocs, 'Exactement trois documents Working après MERGE V9.');

        $refs = $workingDocs->pluck('source_reference')->sort()->values()->toArray();
        $this->assertSame(
            collect($expectedRefs)->sort()->values()->toArray(),
            $refs,
            'Working = Email maire 14 mai + Info déplacement portants + Email 3 avril.'
        );
    }

    /** @test */
    public function narrative_anchors_use_source_reference_and_no_leak(): void
    {
        $subject = $this->ingestV9Subject();
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
            'doc-seraphotheque-pack-Email-2026-05-27-DGFIP',
        ];

        foreach ($expectedIds as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $html, "Ancre documentaire manquante : #{$id}");
        }

        $this->assertStringNotContainsString('drive.google.com', $html);
        $this->assertStringNotContainsString('/home/', $html);
        $this->assertStringNotContainsString('Obsidian-Vault', $html);
        $this->assertStringNotContainsString('storage/subjects/', $html);
    }

    /** @test */
    public function lmalp_and_roland_absent_from_public_corpus(): void
    {
        $subject = $this->ingestV9Subject();
        $bodyHtml = (string) $this->guestShowResponse($subject)->baseResponse->getContent();
        $corrHtml = (string) $this->get(route('subjects.documents.view', [
            $subject->slug,
            SubjectDocument::where('subject_id', $subject->id)
                ->where('source_reference', 'like', '%SERAPH-DOC-CORRESPONDANCE-MAIRIE-2026%')
                ->firstOrFail()->id,
        ]))->baseResponse->getContent();

        foreach (['LMALP', 'Roland', 'Roland Abadie'] as $needle) {
            // Branding global autorisé, corpus éditorial public interdit
            $bodyCount = substr_count(strtolower($bodyHtml), strtolower($needle));
            $corrCount = substr_count(strtolower($corrHtml), strtolower($needle));
            $this->assertSame(0, $bodyCount, "BODY ne doit pas contenir '{$needle}'.");
            $this->assertSame(0, $corrCount, "Correspondance ne doit pas contenir '{$needle}'.");
        }
    }

    /** @test */
    public function project_aot_label_is_clearly_non_adopted(): void
    {
        $subject = $this->ingestV9Subject();
        $html = $this->guestShowResponse($subject)->baseResponse->getContent();

        $this->assertStringContainsString('DOCUMENT PRODUIT PAR LES EXPLOITANTS', $html);
        $this->assertStringContainsString('non adopté', $html);
    }

    /** @test */
    public function narrative_ctas_are_all_linked_and_target_correct_documents(): void
    {
        $subject = $this->ingestV9Subject();
        $html = $this->guestShowResponse($subject)->baseResponse->getContent();

        $ctaMap = [
            'Convention d’été 2025' => 'doc-seraphotheque-pack-SERAPH-DOC-0535',
            'Projet de convention été 2026' => 'doc-seraphotheque-pack-SERAPH-DOC-0239',
            'Sommation du 24 avril 2026' => 'doc-seraphotheque-pack-SERAPH-DOC-0904',
            'Demande d’AOT du 16 juin 2026' => 'doc-seraphotheque-pack-SERAPH-DOC-0293',
            'Correspondance avec la mairie' => 'doc-seraphotheque-pack-SERAPH-DOC-CORRESPONDANCE-MAIRIE-2026',
            'Projet de délibération' => 'doc-seraphotheque-pack-SERAPH-DOC-0486',
            'Trésor public' => 'doc-seraphotheque-pack-Email-2026-05-27-DGFIP',
            'Profession de foi' => 'doc-seraphotheque-pack-SERAPH-DOC-PROFESSION-FOI',
        ];

        $linked = 0;
        foreach ($ctaMap as $section => $anchor) {
            $pattern = '/<h[1-6][^>]*>.*?' . preg_quote($section, '/') . '.*?<\/h[1-6]>.*?<a[^>]*href="#' . preg_quote($anchor, '/') . '"[^>]*>/s';
            if (preg_match($pattern, $html)) {
                $linked++;
            } else {
                $this->fail("CTA non raccordé pour {$section} (attendu #{$anchor})");
            }
        }

        $this->assertSame(8, $linked, '8 CTA raccordés.');
    }

    /** @test */
    public function cta_routes_are_publicly_reachable(): void
    {
        $subject = $this->ingestV9Subject();

        $ctaTargets = [
            ['source' => 'seraphotheque-pack:SERAPH-DOC-0535'],
            ['source' => 'seraphotheque-pack:SERAPH-DOC-0239'],
            ['source' => 'seraphotheque-pack:SERAPH-DOC-0904'],
            ['source' => 'seraphotheque-pack:SERAPH-DOC-0293'],
            ['source' => 'seraphotheque-pack:SERAPH-DOC-CORRESPONDANCE-MAIRIE-2026'],
            ['source' => 'seraphotheque-pack:SERAPH-DOC-0486'],
            ['source' => 'seraphotheque-pack:Email-2026-05-27-DGFIP'],
            ['source' => 'seraphotheque-pack:SERAPH-DOC-PROFESSION-FOI'],
        ];

        foreach ($ctaTargets as $target) {
            $doc = SubjectDocument::where('subject_id', $subject->id)
                ->where('source_reference', $target['source'])
                ->firstOrFail();

            $response = $this->get(route('subjects.documents.view', [$subject->slug, $doc->id]));

            if ($doc->isEmail()) {
                $response->assertRedirect();
                $this->get(route('subjects.documents.email', [$subject->slug, $doc->id]))->assertOk();
            } else {
                $response->assertOk();
            }
        }
    }

    /** @test */
    public function no_dead_or_drive_cta_in_public_body(): void
    {
        $subject = $this->ingestV9Subject();
        $html = $this->guestShowResponse($subject)->baseResponse->getContent();

        // Pas de liens vides, d'ancres mortes vers #documents, ni de lien Drive.
        $this->assertStringNotContainsString('href="#"', $html);
        $this->assertStringNotContainsString('drive.google.com', $html);
    }

    /** @test */
    public function document_card_markup_uses_responsive_layout_without_title_crush(): void
    {
        $subject = $this->ingestV9Subject();

        $doc = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'seraphotheque-pack:SERAPH-DOC-0535')
            ->firstOrFail();

        $tmp = tempnam(sys_get_temp_dir(), 'bail_');
        file_put_contents($tmp, 'fake stored file');
        $stored = $this->storage->storeEncrypted($subject->id, $tmp, $doc->filename);
        unlink($tmp);
        $doc->update([
            'path' => $stored,
            'stored_filename' => basename($stored),
        ]);

        $html = $this->get(route('subjects.show', $subject->slug))->baseResponse->getContent();

        $anchor = 'doc-seraphotheque-pack-SERAPH-DOC-0535';
        $this->assertStringContainsString('id="' . $anchor . '"', $html);

        preg_match('/<li[^>]*id="' . preg_quote($anchor, '/') . '"[^>]*>(.*?)\s*<\/li>/s', $html, $matches);
        $this->assertCount(2, $matches, 'Card HTML should be extractable.');
        $card = $matches[0];

        $this->assertStringContainsString('grid-cols-1', $card, 'Mobile column layout.');
        $this->assertStringContainsString('sm:grid-cols-[auto_minmax(0,1fr)_auto]', $card, 'Desktop three-column layout with shrinkable content.');
        $this->assertStringContainsString('min-w-0', $card, 'Content can shrink without overflow.');
        $this->assertStringContainsString('break-words', $card, 'Title wraps naturally.');
        $this->assertStringContainsString('sm:w-auto', $card, 'Actions are not fixed to full width on desktop.');
        $this->assertStringContainsString('data-testid="btn-doc-view"', $card, 'View action is visible.');
        $this->assertStringNotContainsString('truncate', $card, 'Title must not be truncated.');
    }
}
