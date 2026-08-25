<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\User;
use App\Models\VisibilityLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * QA mécanique du patch V7-A : human view, mapping, anchors, ACL, mobile, branding.
 */
class SubjectSeraphothequeV7ReviewTest extends TestCase
{
    use RefreshDatabase;

    private string $v6Pack;
    private string $v7Body;
    private string $v7Sha;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
        $this->v6Pack = '/home/aur-lien/Obsidian-Vault/LEX/08-publication/seraphotheque-v1/PUBLIC-V6-FINAL';
        $this->v7Body = file_get_contents('/home/aur-lien/Téléchargements/Comprendre-en-1-Minute-V7-REVIEW-FINAL.md');
        $this->v7Sha = '8f7d7b96d95d3a4695f3a658d46ddcb42db56e9a2a5548d0220a630c86973fdf';
    }

    private function seedUserAndTaxonomy(): User
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now(), 'requires_setup' => false]);
        Category::factory()->create(['id' => 10, 'name' => 'Vie du village', 'slug' => 'vie-du-village']);
        SubCategory::factory()->create(['id' => 14, 'category_id' => 10, 'name' => 'Séraphothèque', 'slug' => 'seraphotheque']);

        return $admin;
    }

    private function ingestV7Subject(): Subject
    {
        $admin = $this->seedUserAndTaxonomy();

        $exit = Artisan::call('app:seraphotheque-ingestion', [
            '--pack-path' => $this->v6Pack,
            '--user-id' => $admin->id,
            '--sync-bodies' => true,
        ]);
        $this->assertSame(0, $exit, 'Ingestion V6 corpus documentaire doit réussir.');

        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();

        // Remplace le public_body assemblé par V7 exact ; Working/Citizen restent V6 pour tester la préservation.
        $citizenBefore = $subject->citizen_body;
        $workingBefore = $subject->body;

        $subject->update([
            'public_body' => $this->v7Body,
            'public_status' => 'published',
            'public_published_at' => now(),
            'public_is_listed' => false,
            'theme' => 'Séraphothèque',
        ]);

        $this->assertSame($citizenBefore, $subject->fresh()->citizen_body, 'Citizen body doit rester inchangé.');
        $this->assertSame($workingBefore, $subject->fresh()->body, 'Working body doit rester inchangé.');

        return $subject->fresh();
    }

    private function guestShowResponse(Subject $subject): \Illuminate\Testing\TestResponse
    {
        Storage::fake('documents');

        return $this->get(route('subjects.show', $subject->slug));
    }

    /** @test */
    public function v7_source_authoritative_sha_matches(): void
    {
        $this->assertSame($this->v7Sha, hash('sha256', $this->v7Body), 'V7 source SHA contract.');
    }

    /** @test */
    public function guest_view_has_exactly_one_article_subject_document(): void
    {
        $subject = $this->ingestV7Subject();
        $response = $this->guestShowResponse($subject);

        $html = $response->baseResponse->getContent();
        preg_match_all('/<article[^>]*class="[^"]*subject-document[^"]*"[^>]*>/', $html, $matches);
        $this->assertCount(1, $matches[0], 'Un seul article.subject-document attendu.');
    }

    /** @test */
    public function v7_body_occurrence_is_exact_once(): void
    {
        $subject = $this->ingestV7Subject();
        $this->assertSame($this->v7Sha, hash('sha256', (string) $subject->public_body), 'public_body est V7 exact.');

        $response = $this->guestShowResponse($subject);
        $html = $response->baseResponse->getContent();

        // "Comprendre en une minute" apparaît dans le H2 puis dans le body : on vérifie qu'il n'est pas dupliqué inutilement.
        $this->assertEquals(1, substr_count($html, 'Comprendre en une minute'), 'Une seule occurrence HTML de la phrase canonique.');
    }

    /** @test */
    public function canonical_h2_ids_present_and_valid(): void
    {
        $subject = $this->ingestV7Subject();
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

        preg_match_all('/<h[23][^>]*id="([^"]+)"/', $html, $matches);
        $this->assertGreaterThan(0, count(array_filter($matches[1], fn ($m) => str_starts_with($matches[0][array_search($m, $matches[1])] ?? '', '<h3'))), 'Au moins un H3 avec id.');

        $this->assertStringNotContainsString('<h2id=', $html);
        $this->assertStringNotContainsString('<h3id=', $html);
    }

    /** @test */
    public function internal_links_target_existing_anchors(): void
    {
        $subject = $this->ingestV7Subject();
        $html = $this->guestShowResponse($subject)->baseResponse->getContent();

        preg_match_all('/<a[^>]*href="#([^"]+)"/', $html, $linkMatches);
        $ids = array_unique($linkMatches[1]);

        foreach ($ids as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $html, "Lien interne sans cible : #{$id}");
        }
    }

    /** @test */
    public function global_pdf_cta_absent_for_guest(): void
    {
        $subject = $this->ingestV7Subject();
        $response = $this->guestShowResponse($subject);

        $response->assertDontSee('Ouvrir le PDF');
        $response->assertDontSee('Télécharger le PDF');
        $response->assertDontSee('btn-pdf-show');
        $response->assertDontSee('btn-pdf-download');
    }

    /** @test */
    public function guest_back_link_points_to_seraphotheque(): void
    {
        $subject = $this->ingestV7Subject();
        $html = $this->guestShowResponse($subject)->baseResponse->getContent();

        $this->assertStringContainsString('"' . route('seraphotheque') . '"', $html);
        $this->assertStringContainsString('Retour à la Séraphothèque', $html);
    }

    /** @test */
    public function document_classification_is_deterministic_and_ordered(): void
    {
        $subject = $this->ingestV7Subject();

        $expectedGroups = [
            'SERAPH-DOC-0535' => 'primary',
            'SERAPH-DOC-0239' => 'primary',
            'SERAPH-DOC-0904' => 'primary',
            'SERAPH-DOC-CORRESPONDANCE-MAIRIE-2026' => 'primary',
            'SERAPH-DOC-1263' => 'primary',
            'SERAPH-DOC-0293' => 'primary',
            'SERAPH-DOC-0997' => 'positions',
            'Email-2026-07-01' => 'positions',
            'SERAPH-DOC-0486' => 'positions',
            'COMP-2025-2026' => 'synthesis',
            'SERAPH-DOC-PROFESSION-FOI' => 'context',
        ];

        $expectedOrder = [
            'SERAPH-DOC-0535',
            'SERAPH-DOC-0239',
            'SERAPH-DOC-0904',
            'SERAPH-DOC-CORRESPONDANCE-MAIRIE-2026',
            'SERAPH-DOC-1263',
            'SERAPH-DOC-0293',
            'SERAPH-DOC-0997',
            'Email-2026-07-01',
            'SERAPH-DOC-0486',
            'COMP-2025-2026',
            'SERAPH-DOC-PROFESSION-FOI',
        ];

        $docs = SubjectDocument::where('subject_id', $subject->id)
            ->where('visibility', VisibilityLevel::Public)
            ->get();

        $this->assertCount(11, $docs, '11 documents publics attendus.');

        foreach ($expectedGroups as $ref => $group) {
            $doc = $docs->first(fn ($d) => str_contains((string) $d->source_reference, $ref));
            $this->assertNotNull($doc, "Document manquant : {$ref}");
            $this->assertSame($group, $doc->seraphothequeGroup(), "Groupe incorrect pour {$ref}");
        }

        $orderedRefs = $docs
            ->sortBy(fn ($d) => $d->seraphothequeOrder())
            ->sortBy(fn ($d) => array_search($d->seraphothequeGroup(), ['primary', 'positions', 'synthesis', 'context', 'other']))
            ->map(fn ($d) => str_contains((string) $d->source_reference, 'SERAPH-DOC-0904') ? 'SERAPH-DOC-0904' : (
                str_contains((string) $d->source_reference, 'Email-2026-07-01') ? 'Email-2026-07-01' : (
                    str_contains((string) $d->source_reference, 'COMP-2025-2026') ? 'COMP-2025-2026' : (
                        str_contains((string) $d->source_reference, 'SERAPH-DOC-PROFESSION-FOI') ? 'SERAPH-DOC-PROFESSION-FOI' : (
                            str_contains((string) $d->source_reference, 'SERAPH-DOC-CORRESPONDANCE-MAIRIE-2026') ? 'SERAPH-DOC-CORRESPONDANCE-MAIRIE-2026' : \Illuminate\Support\Str::after((string) $d->source_reference, 'seraphotheque-pack:')
                        )
                    )
                )
            ))
            ->values()
            ->toArray();

        $this->assertSame($expectedOrder, $orderedRefs, 'Ordre canonique des documents.');
    }

    /** @test */
    public function semantic_mapping_0904_is_primary_profession_foi_is_context(): void
    {
        $subject = $this->ingestV7Subject();

        $doc0904 = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'like', '%SERAPH-DOC-0904%')
            ->first();
        $this->assertNotNull($doc0904);
        $this->assertSame('primary', $doc0904->seraphothequeGroup(), 'Sommation = PIÈCE PRINCIPALE.');

        $docFoi = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'like', '%SERAPH-DOC-PROFESSION-FOI%')
            ->first();
        $this->assertNotNull($docFoi);
        $this->assertSame('context', $docFoi->seraphothequeGroup(), 'Profession de foi = DOCUMENT DE CONTEXTE.');

        $docComp = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'like', '%COMP-2025-2026%')
            ->first();
        $this->assertNotNull($docComp);
        $this->assertSame('synthesis', $docComp->seraphothequeGroup(), 'COMP = SYNTHÈSE.');
    }

    /** @test */
    public function discussion_is_hidden_for_guest(): void
    {
        $subject = $this->ingestV7Subject();
        $response = $this->guestShowResponse($subject);

        $response->assertDontSee('Discussion');
        $response->assertDontSee('Commenter');
        $response->assertDontSee('Soyez le premier');
        $response->assertDontSee('id="comments"');
    }

    /** @test */
    public function branding_is_canonical_and_not_duplicated(): void
    {
        $subject = $this->ingestV7Subject();
        $html = $this->guestShowResponse($subject)->baseResponse->getContent();

        $this->assertStringContainsString('La Main à la Pâte', $html);
        $this->assertStringNotContainsString('La Main a la Pate', $html);

        // Pas de doublon dans la navbar : une seule occurrence textuelle.
        preg_match('/<nav\b.*?<\/nav>/s', $html, $navMatch);
        $this->assertSame(1, substr_count($navMatch[0] ?? '', 'La Main à la Pâte'), 'Une seule occurrence du brand dans la navbar.');

        // Title : une seule fois.
        preg_match('/<title>(.*?)<\/title>/s', $html, $titleMatch);
        $this->assertSame(1, substr_count($titleMatch[1] ?? '', 'La Main à la Pâte'), 'Une seule occurrence du brand dans <title>.');

        // Footer : h4 + copyright.
        preg_match('/<footer\b.*?<\/footer>/s', $html, $footerMatch);
        $this->assertSame(2, substr_count($footerMatch[0] ?? '', 'La Main à la Pâte'), 'Brand présent dans h4 et copyright du footer.');
    }

    /** @test */
    public function mobile_table_has_local_overflow_wrapper(): void
    {
        $subject = $this->ingestV7Subject();
        $html = $this->guestShowResponse($subject)->baseResponse->getContent();

        $this->assertStringContainsString('<div class="overflow-x-auto">', $html);
        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('Convention été 2025', $html);
        $this->assertStringContainsString('Projet été 2026', $html);
    }

    /** @test */
    public function fake_dates_can_be_null_and_hidden_in_ui(): void
    {
        $subject = $this->ingestV7Subject();
        $doc0239 = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'like', '%SERAPH-DOC-0239%')
            ->firstOrFail();

        $docCorr = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'like', '%SERAPH-DOC-CORRESPONDANCE-MAIRIE-2026%')
            ->firstOrFail();

        // Simule V7-B date fix : NULL pour ces deux documents.
        $doc0239->update(['document_date' => null]);
        $docCorr->update(['document_date' => null]);

        $html = $this->guestShowResponse($subject)->baseResponse->getContent();

        // On ne doit pas voir "Date :" près du titre de ces documents.
        // Vérification simple : aucune date affichée sous forme "01/01/2026" provenant d'une année seule.
        $this->assertStringNotContainsString('01/01/2026', $html);
        $this->assertStringNotContainsString('01/01/2025', $html);
    }

    /** @test */
    public function narrative_links_use_source_reference_anchors(): void
    {
        $subject = $this->ingestV7Subject();
        $html = $this->guestShowResponse($subject)->baseResponse->getContent();

        $expectedAnchors = [
            'doc-seraphotheque-pack-SERAPH-DOC-0535',
            'doc-seraphotheque-pack-SERAPH-DOC-0239',
            'doc-seraphotheque-pack-SERAPH-DOC-0904',
            'doc-seraphotheque-pack-SERAPH-DOC-1263',
            'doc-seraphotheque-pack-SERAPH-DOC-0293',
        ];

        foreach ($expectedAnchors as $anchor) {
            $this->assertStringContainsString('href="#' . $anchor . '"', $html, "Lien narratif manquant : #{$anchor}");
        }

        // Pas d'ID numérique hardcodé, de Drive URL, de chemin filesystem.
        $this->assertStringNotContainsString('drive.google.com', $html);
        $this->assertStringNotContainsString('/home/', $html);
        $this->assertStringNotContainsString('Obsidian-Vault', $html);
        $this->assertStringNotContainsString('storage/subjects/', $html);
    }

    /** @test */
    public function guest_cannot_access_document_routes_from_another_subject(): void
    {
        $subject = $this->ingestV7Subject();
        $other = Subject::factory()->create([
            'user_id' => $subject->user_id,
            'category_id' => 10,
            'sub_category_id' => 14,
            'public_status' => 'published',
            'public_body' => 'Autre sujet public.',
        ]);

        $doc = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'like', '%SERAPH-DOC-0904%')
            ->firstOrFail();

        // /voir
        $this->get(route('subjects.documents.view', [$other->slug, $doc->id]))
            ->assertNotFound();

        // /telecharger
        $this->get(route('subjects.documents.download', [$other->slug, $doc->id]))
            ->assertNotFound();

        // /email
        $emailDoc = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'like', '%SERAPH-DOC-1263%')
            ->firstOrFail();
        $this->get(route('subjects.documents.email', [$other->slug, $emailDoc->id]))
            ->assertNotFound();
    }

    /** @test */
    public function guest_can_view_public_email_as_html_not_json(): void
    {
        $subject = $this->ingestV7Subject();

        $docEmail = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'like', '%SERAPH-DOC-1263%')
            ->firstOrFail();

        $payload = json_encode([
            'date' => '2026-05-14',
            'subject' => 'Réponse du maire',
            'from' => 'maire@rozier.fr',
            'to' => 'contact@seraphotheque.fr',
            'body_text' => 'Voici la position de la commune.',
        ]);

        // Chiffre le payload comme le ferait l'ingestion pipeline.
        $tmp = tempnam(sys_get_temp_dir(), 'lmalp_email_');
        file_put_contents($tmp, $payload);
        $storage = new \App\Services\DocumentStorageService('documents');
        $encryptedPath = $storage->storeEncrypted($subject->id, $tmp, 'email-1263.json');
        unlink($tmp);
        $docEmail->update(['document_type' => 'email', 'mime_type' => 'application/json', 'path' => $encryptedPath]);

        $response = $this->get(route('subjects.documents.email', [$subject->slug, $docEmail->id]));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $response->assertSee('Réponse du maire');
        $response->assertSee('Voici la position de la commune.');
        $response->assertDontSee('"body_text"');
        $response->assertDontSee('": ');
    }

    /** @test */
    public function guest_view_and_download_routes_for_email_do_not_leak_json(): void
    {
        $subject = $this->ingestV7Subject();

        $docEmail = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'like', '%SERAPH-DOC-1263%')
            ->firstOrFail();
        $docEmail->update(['document_type' => 'email', 'mime_type' => 'application/json']);

        // On met un contenu binaire invalide pour s'assurer qu'aucune route ne l'expose brut.
        \Illuminate\Support\Facades\Storage::disk('documents')->put($docEmail->path, 'not-valid-json');

        $view = $this->get(route('subjects.documents.view', [$subject->slug, $docEmail->id]));
        $view->assertRedirect(route('subjects.documents.email', [$subject->slug, $docEmail->id]));

        $download = $this->get(route('subjects.documents.download', [$subject->slug, $docEmail->id]));
        $download->assertNotFound();
    }

    /** @test */
    public function acl_guest_allowed_citizen_and_working_blocked(): void
    {
        $subject = $this->ingestV7Subject();

        $this->get(route('subjects.show', $subject->slug))->assertOk();

        $citizen = User::factory()->create(['role' => 'citoyen', 'email_verified_at' => now(), 'requires_setup' => false]);
        $this->actingAs($citizen)->get(route('subjects.show', $subject->slug))->assertNotFound();

        $employe = User::factory()->create(['role' => 'employe', 'email_verified_at' => now(), 'requires_setup' => false]);
        $this->actingAs($employe)->get(route('subjects.show', $subject->slug))->assertNotFound();
    }

    /** @test */
    public function public_is_listed_is_false_and_original_fallback_none(): void
    {
        $subject = $this->ingestV7Subject();

        $this->assertFalse($subject->public_is_listed);
        $this->assertNull($subject->published_at);
    }

    /** @test */
    public function qa_mechanical_wording_contract(): void
    {
        $subject = $this->ingestV7Subject();
        $html = $this->guestShowResponse($subject)->baseResponse->getContent();

        // Présents
        $this->assertStringContainsString('Dossier documentaire — mis à jour le 24 août 2026', $html);
        $this->assertStringContainsString('Aurélien Tisserand est l’un des exploitants de La Séraphothèque', $html);
        $this->assertStringContainsString('Soutenir les commerçants qui restent ouverts à l’année', $html);

        // Absents
        $this->assertStringNotContainsString('Gel de PUBLIC-V1', $html);
        $this->assertStringNotContainsString('Dossier documentaire en cours d’enrichissement — version du 21 août 2026', $html);
    }
}
