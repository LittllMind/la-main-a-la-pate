<?php

namespace Tests\Feature;

use App\Models\RepresentationType;
use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\User;
use App\Models\VisibilityLevel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubjectDocumentMetadataTest extends TestCase
{
    private function uploadDocumentMetadataForm(User $user, Subject $subject, array $metadata): \Illuminate\Testing\TestResponse
    {
        Storage::fake('documents');

        $pdf = UploadedFile::fake()->createWithContent('meta.pdf', str_repeat('X', 500));

        return $this->actingAs($user)
            ->from(route('subjects.documents.index', $subject->slug))
            ->post(route('subjects.documents.store', $subject->slug), array_merge([
                'file' => $pdf,
                'title' => 'Document métadonnées',
                'category' => 'source',
                'visibility' => 'public',
            ], $metadata));
    }

    public function test_upload_persists_all_metadata_fields()
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);

        $this->uploadDocumentMetadataForm($admin, $subject, [
            'document_date' => '2026-04-24',
            'document_type' => 'sommation',
            'author' => 'Commune du Rozier',
            'recipient' => 'La Séraphothèque',
            'source_reference' => 'Archive LEX / 2026-04-24-sommation.pdf',
            'representation_type' => 'scan',
            'redacted' => '1',
            'establishes' => 'COMM_ESTABLISH_MARKER_91ab',
            'limitations' => 'COMM_LIMIT_MARKER_77cd',
        ])
            ->assertRedirect();

        $doc = SubjectDocument::where('subject_id', $subject->id)->first();
        $this->assertNotNull($doc);
        $this->assertEquals('2026-04-24', $doc->document_date->format('Y-m-d'));
        $this->assertEquals('sommation', $doc->document_type);
        $this->assertEquals('Commune du Rozier', $doc->author);
        $this->assertEquals('La Séraphothèque', $doc->recipient);
        $this->assertEquals('Archive LEX / 2026-04-24-sommation.pdf', $doc->source_reference);
        $this->assertEquals(RepresentationType::Scan, $doc->representation_type);
        $this->assertTrue($doc->redacted);
        $this->assertEquals('COMM_ESTABLISH_MARKER_91ab', $doc->establishes);
        $this->assertEquals('COMM_LIMIT_MARKER_77cd', $doc->limitations);
    }

    public function test_update_metadata()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);
        $doc = SubjectDocument::factory()->for($subject)->public()->create();

        $this->actingAs($admin)
            ->from(route('subjects.documents.index', $subject->slug))
            ->patch(route('subjects.documents.update', [$subject->slug, $doc->id]), [
                'title' => 'Sommation 24 avril 2026',
                'description' => 'Acte remis par commissaire de justice.',
                'document_date' => '2026-04-24',
                'document_type' => 'sommation',
                'author' => 'Commune du Rozier',
                'recipient' => 'La Séraphothèque',
                'source_reference' => 'Archive LEX / scan',
                'representation_type' => 'scan',
                'redacted' => '1',
                'establishes' => 'Demande de signature et retrait.',
                'limitations' => 'Ne constitue pas un titre exécutoire.',
                'visibility' => 'citizen',
            ])
            ->assertRedirect();

        $doc->refresh();
        $this->assertEquals('Sommation 24 avril 2026', $doc->title);
        $this->assertEquals('2026-04-24', $doc->document_date->format('Y-m-d'));
        $this->assertEquals('Acte remis par commissaire de justice.', $doc->description);
        $this->assertEquals('sommation', $doc->document_type);
        $this->assertEquals('Commune du Rozier', $doc->author);
        $this->assertEquals('La Séraphothèque', $doc->recipient);
        $this->assertEquals('Archive LEX / scan', $doc->source_reference);
        $this->assertEquals(RepresentationType::Scan, $doc->representation_type);
        $this->assertTrue($doc->redacted);
        $this->assertEquals('Demande de signature et retrait.', $doc->establishes);
        $this->assertEquals('Ne constitue pas un titre exécutoire.', $doc->limitations);
        $this->assertEquals(VisibilityLevel::Citizen, $doc->visibility);
    }

    public function test_invalid_representation_type_is_rejected()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);

        $this->uploadDocumentMetadataForm($admin, $subject, [
            'representation_type' => 'not-valid',
        ])
            ->assertSessionHasErrors('representation_type');

        $this->assertEquals(0, SubjectDocument::where('subject_id', $subject->id)->count());
    }

    public function test_legacy_nullable_document_remains_functional()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);
        $doc = SubjectDocument::factory()->for($subject)->working()->create([
            'title' => 'Legacy marker OLD_DOC_1122',
        ]);

        $this->actingAs($admin)
            ->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('OLD_DOC_1122');

        $doc->refresh();
        $this->assertNull($doc->document_date);
        $this->assertNull($doc->document_type);
        $this->assertNull($doc->representation_type);
        $this->assertFalse($doc->redacted);
        $this->assertNull($doc->establishes);
        $this->assertNull($doc->limitations);
    }

    public function test_redacted_can_be_toggled_true_then_false()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);
        $doc = SubjectDocument::factory()->for($subject)->working()->create();

        $this->actingAs($admin)
            ->patch(route('subjects.documents.update', [$subject->slug, $doc->id]), [
                'redacted' => '1',
            ])
            ->assertRedirect();
        $doc->refresh();
        $this->assertTrue($doc->redacted);

        $this->actingAs($admin)
            ->patch(route('subjects.documents.update', [$subject->slug, $doc->id]), [
                'redacted' => '0',
            ])
            ->assertRedirect();
        $doc->refresh();
        $this->assertFalse($doc->redacted);
    }

    public function test_guest_sees_only_public_metadata()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create([
            'user_id' => $admin->id,
            'public_status' => 'published',
            'public_body' => 'Public body.',
        ]);

        SubjectDocument::factory()->for($subject)->public()->create([
            'title' => 'PUB_MARKER_TITLE_8a2e',
            'author' => 'PUB_AUTHOR_4f1c',
            'establishes' => 'PUB_ESTABLISH_9d3b',
            'limitations' => 'PUB_LIMIT_6e7a',
            'source_reference' => 'PUB_SOURCE_REF_SECRET_1x9z',
            'redacted' => true,
        ]);

        SubjectDocument::factory()->for($subject)->citizen()->create([
            'title' => 'CIT_MARKER_TITLE_2b4n',
            'author' => 'CIT_AUTHOR_7h3k',
            'establishes' => 'CIT_ESTABLISH_5m8p',
            'limitations' => 'CIT_LIMIT_2q6w',
        ]);

        SubjectDocument::factory()->for($subject)->working()->create([
            'title' => 'WORK_MARKER_TITLE_9c1r',
            'author' => 'WORK_AUTHOR_3v7t',
            'establishes' => 'WORK_ESTABLISH_4n2s',
            'limitations' => 'WORK_LIMIT_8b5y',
        ]);

        auth()->logout();
        $this->assertGuest();

        $response = $this->get(route('subjects.show', $subject->slug));
        $response->assertOk()
            ->assertSee('PUB_MARKER_TITLE_8a2e')
            ->assertSee('PUB_AUTHOR_4f1c')
            ->assertSee('PUB_ESTABLISH_9d3b')
            ->assertSee('PUB_LIMIT_6e7a')
            ->assertSee('Version expurgée')
            ->assertDontSee('PUB_SOURCE_REF_SECRET_1x9z') // source_reference is internal
            ->assertDontSee('CIT_MARKER_TITLE_2b4n')
            ->assertDontSee('CIT_AUTHOR_7h3k')
            ->assertDontSee('CIT_ESTABLISH_5m8p')
            ->assertDontSee('WORK_MARKER_TITLE_9c1r')
            ->assertDontSee('WORK_AUTHOR_3v7t');
    }

    public function test_citizen_sees_public_and_citizen_metadata_not_working()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $citizen = User::factory()->create(['role' => 'citoyen', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create([
            'user_id' => $admin->id,
            'public_status' => 'published',
            'public_body' => 'Public body.',
            'citizen_status' => 'published',
            'citizen_body' => 'Citizen body.',
        ]);

        SubjectDocument::factory()->for($subject)->public()->create([
            'title' => 'PUB_MARKER_TITLE_8a2e',
            'author' => 'PUB_AUTHOR_4f1c',
            'establishes' => 'PUB_ESTABLISH_9d3b',
            'limitations' => 'PUB_LIMIT_6e7a',
        ]);

        SubjectDocument::factory()->for($subject)->citizen()->create([
            'title' => 'CIT_MARKER_TITLE_2b4n',
            'author' => 'CIT_AUTHOR_7h3k',
            'establishes' => 'CIT_ESTABLISH_5m8p',
            'limitations' => 'CIT_LIMIT_2q6w',
            'source_reference' => 'CIT_SOURCE_REF_SECRET_2y8w',
        ]);

        SubjectDocument::factory()->for($subject)->working()->create([
            'title' => 'WORK_MARKER_TITLE_9c1r',
            'author' => 'WORK_AUTHOR_3v7t',
            'establishes' => 'WORK_ESTABLISH_4n2s',
            'limitations' => 'WORK_LIMIT_8b5y',
            'source_reference' => 'WORK_SOURCE_REF_SECRET_3u5q',
        ]);

        $this->actingAs($citizen)
            ->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('PUB_MARKER_TITLE_8a2e')
            ->assertSee('PUB_AUTHOR_4f1c')
            ->assertSee('CIT_MARKER_TITLE_2b4n')
            ->assertSee('CIT_AUTHOR_7h3k')
            ->assertSee('CIT_ESTABLISH_5m8p')
            ->assertSee('CIT_LIMIT_2q6w')
            ->assertDontSee('WORK_MARKER_TITLE_9c1r')
            ->assertDontSee('WORK_AUTHOR_3v7t')
            ->assertDontSee('WORK_ESTABLISH_4n2s')
            ->assertDontSee('WORK_SOURCE_REF_SECRET_3u5q');
    }

    public function test_source_reference_never_leaks_to_guest()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create([
            'user_id' => $admin->id,
            'public_status' => 'published',
            'public_body' => 'Public body.',
        ]);

        SubjectDocument::factory()->for($subject)->public()->create([
            'title' => 'Public doc',
            'source_reference' => 'SECRET_SOURCE_REF_7j2k',
        ]);

        auth()->logout();
        $this->assertGuest();

        $this->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('Public doc')
            ->assertDontSee('SECRET_SOURCE_REF_7j2k');
    }

    public function test_redacted_badge_visible_in_public_view()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create([
            'user_id' => $admin->id,
            'public_status' => 'published',
            'public_body' => 'Public body.',
        ]);

        SubjectDocument::factory()->for($subject)->public()->create([
            'title' => 'REDACTED_BADGE_DOC_a7f3',
            'redacted' => true,
        ]);

        auth()->logout();

        $this->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('REDACTED_BADGE_DOC_a7f3')
            ->assertSee('data-redacted-badge');
    }

    public function test_empty_metadata_blocks_are_not_rendered_as_parasite()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create([
            'user_id' => $admin->id,
            'public_status' => 'published',
            'public_body' => 'Public body.',
        ]);

        SubjectDocument::factory()->for($subject)->public()->create([
            'title' => 'Minimal doc',
            'author' => null,
            'recipient' => null,
            'document_type' => null,
            'establishes' => null,
            'limitations' => null,
            'redacted' => false,
        ]);

        auth()->logout();

        $response = $this->get(route('subjects.show', $subject->slug));
        $response->assertOk()
            ->assertSee('Minimal doc')
            ->assertDontSee('Auteur')
            ->assertDontSee('Destinataire')
            ->assertDontSee('Nature')
            ->assertDontSee('Ce qu\u0027il établit')
            ->assertDontSee('Version expurgée');
    }
}
