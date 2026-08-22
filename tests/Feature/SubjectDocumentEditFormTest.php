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

class SubjectDocumentEditFormTest extends TestCase
{
    private function storeEncryptedDocument(Subject $subject, string $filename, int $size = 500, string $visibility = 'working'): SubjectDocument
    {
        $service = app(\App\Services\DocumentStorageService::class);
        $pdf = UploadedFile::fake()->createWithContent($filename, str_repeat('X', $size));
        $path = $service->storeEncrypted($subject->id, $pdf->getRealPath(), $filename);

        return SubjectDocument::create([
            'subject_id' => $subject->id,
            'filename' => $filename,
            'stored_filename' => basename($path),
            'path' => $path,
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => $size,
            'title' => str_replace('.pdf', '', $filename),
            'visibility' => VisibilityLevel::tryFrom($visibility)->value,
        ]);
    }

    public function test_admin_can_open_document_edit_form()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);
        $doc = $this->storeEncryptedDocument($subject, 'PUBLIC_DOWNLOAD_MARKER.pdf', 500, 'public');

        $this->actingAs($admin)
            ->get(route('subjects.documents.edit', [$subject->slug, $doc->id]))
            ->assertOk()
            ->assertSee('Modifier la fiche')
            ->assertSee($doc->title)
            ->assertSee('source_reference')
            ->assertSee('establishes')
            ->assertSee('limitations');
    }

    public function test_unauthorized_user_cannot_open_document_edit_form()
    {
        Storage::fake('documents');

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create(['email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $owner->id]);
        $doc = $this->storeEncryptedDocument($subject, 'doc.pdf', 500, 'public');

        $this->actingAs($other)
            ->get(route('subjects.documents.edit', [$subject->slug, $doc->id]))
            ->assertForbidden();
    }

    public function test_guest_cannot_open_document_edit_form()
    {
        Storage::fake('documents');

        $subject = Subject::factory()->create();
        $doc = $this->storeEncryptedDocument($subject, 'doc.pdf', 500, 'public');

        $this->get(route('subjects.documents.edit', [$subject->slug, $doc->id]))
            ->assertRedirect('/');
    }

    public function test_admin_can_update_all_document_metadata()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);
        $doc = $this->storeEncryptedDocument($subject, 'initial.pdf', 500, 'working');

        $this->actingAs($admin)
            ->from(route('subjects.documents.edit', [$subject->slug, $doc->id]))
            ->patch(route('subjects.documents.update', [$subject->slug, $doc->id]), [
                'title' => 'Corrected title',
                'description' => 'Corrected description',
                'category' => 'annexe',
                'visibility' => 'public',
                'document_date' => '2026-04-24',
                'document_type' => 'sommation',
                'author' => 'Corrected author',
                'recipient' => 'Corrected recipient',
                'source_reference' => 'Corrected internal ref',
                'representation_type' => 'scan',
                'redacted' => '1',
                'establishes' => 'Corrected establishes',
                'limitations' => 'Corrected limitations',
            ])
            ->assertRedirect(route('subjects.documents.index', $subject->slug));

        $doc->refresh();
        $this->assertEquals('Corrected title', $doc->title);
        $this->assertEquals('Corrected description', $doc->description);
        $this->assertEquals('annexe', $doc->category);
        $this->assertEquals(VisibilityLevel::Public, $doc->visibility);
        $this->assertEquals('2026-04-24', $doc->document_date->format('Y-m-d'));
        $this->assertEquals('sommation', $doc->document_type);
        $this->assertEquals('Corrected author', $doc->author);
        $this->assertEquals('Corrected recipient', $doc->recipient);
        $this->assertEquals('Corrected internal ref', $doc->source_reference);
        $this->assertEquals(RepresentationType::Scan, $doc->representation_type);
        $this->assertTrue($doc->redacted);
        $this->assertEquals('Corrected establishes', $doc->establishes);
        $this->assertEquals('Corrected limitations', $doc->limitations);
    }

    public function test_nullable_metadata_can_be_cleared()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);
        $doc = $this->storeEncryptedDocument($subject, 'filled.pdf', 500, 'public');
        $doc->update([
            'document_date' => '2026-01-01',
            'document_type' => 'type',
            'author' => 'author',
            'recipient' => 'recipient',
            'establishes' => 'establishes',
            'limitations' => 'limitations',
        ]);

        $this->actingAs($admin)
            ->from(route('subjects.documents.edit', [$subject->slug, $doc->id]))
            ->patch(route('subjects.documents.update', [$subject->slug, $doc->id]), [
                'document_date' => '',
                'document_type' => '',
                'author' => '',
                'recipient' => '',
                'establishes' => '',
                'limitations' => '',
            ])
            ->assertRedirect();

        $doc->refresh();
        $this->assertNull($doc->document_date);
        $this->assertNull($doc->document_type);
        $this->assertNull($doc->author);
        $this->assertNull($doc->recipient);
        $this->assertNull($doc->establishes);
        $this->assertNull($doc->limitations);
    }

    public function test_redacted_can_transition_true_to_false()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);
        $doc = $this->storeEncryptedDocument($subject, 'redacted.pdf', 500, 'public');
        $doc->update(['redacted' => true]);

        $this->actingAs($admin)
            ->from(route('subjects.documents.edit', [$subject->slug, $doc->id]))
            ->patch(route('subjects.documents.update', [$subject->slug, $doc->id]), [
                'redacted' => '0',
            ])
            ->assertRedirect();

        $doc->refresh();
        $this->assertFalse($doc->redacted);
    }

    public function test_invalid_visibility_rejected()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);
        $doc = $this->storeEncryptedDocument($subject, 'doc.pdf', 500, 'public');

        $this->actingAs($admin)
            ->from(route('subjects.documents.edit', [$subject->slug, $doc->id]))
            ->patch(route('subjects.documents.update', [$subject->slug, $doc->id]), [
                'visibility' => 'super-public',
            ])
            ->assertSessionHasErrors('visibility');
    }

    public function test_invalid_representation_type_rejected()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);
        $doc = $this->storeEncryptedDocument($subject, 'doc.pdf', 500, 'public');

        $this->actingAs($admin)
            ->from(route('subjects.documents.edit', [$subject->slug, $doc->id]))
            ->patch(route('subjects.documents.update', [$subject->slug, $doc->id]), [
                'representation_type' => 'forgery',
            ])
            ->assertSessionHasErrors('representation_type');
    }

    public function test_editing_metadata_does_not_replace_file()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);
        $doc = $this->storeEncryptedDocument($subject, 'stable.pdf', 500, 'public');

        $originalPath = $doc->path;
        $originalStoredFilename = $doc->stored_filename;

        $this->actingAs($admin)
            ->from(route('subjects.documents.edit', [$subject->slug, $doc->id]))
            ->patch(route('subjects.documents.update', [$subject->slug, $doc->id]), [
                'title' => 'New title',
            ])
            ->assertRedirect();

        $doc->refresh();
        $this->assertEquals('stable.pdf', $doc->filename);
        $this->assertEquals($originalStoredFilename, $doc->stored_filename);
        $this->assertEquals($originalPath, $doc->path);
    }

    public function test_download_visibility_matrix_is_enforced()
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

        $publicDoc = $this->storeEncryptedDocument($subject, 'PUBLIC_DOWNLOAD_MARKER.pdf', 500, 'public');
        $citizenDoc = $this->storeEncryptedDocument($subject, 'CITIZEN_DOWNLOAD_MARKER.pdf', 500, 'citizen');
        $workingDoc = $this->storeEncryptedDocument($subject, 'WORKING_DOWNLOAD_MARKER.pdf', 500, 'working');

        // Guest
        auth()->logout();
        $this->assertGuest();
        $this->get(route('subjects.documents.download', [$subject->slug, $publicDoc->id]))->assertOk();
        $this->get(route('subjects.documents.download', [$subject->slug, $citizenDoc->id]))->assertNotFound();
        $this->get(route('subjects.documents.download', [$subject->slug, $workingDoc->id]))->assertNotFound();

        // Citizen
        $this->actingAs($citizen);
        $this->get(route('subjects.documents.download', [$subject->slug, $publicDoc->id]))->assertOk();
        $this->get(route('subjects.documents.download', [$subject->slug, $citizenDoc->id]))->assertOk();
        $this->get(route('subjects.documents.download', [$subject->slug, $workingDoc->id]))->assertNotFound();

        // Admin
        $this->actingAs($admin);
        $this->get(route('subjects.documents.download', [$subject->slug, $publicDoc->id]))->assertOk();
        $this->get(route('subjects.documents.download', [$subject->slug, $citizenDoc->id]))->assertOk();
        $this->get(route('subjects.documents.download', [$subject->slug, $workingDoc->id]))->assertOk();
    }

    public function test_internal_source_reference_edit_form_not_accessible_to_non_authorized_users()
    {
        Storage::fake('documents');

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $citizen = User::factory()->create(['role' => 'citoyen', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create([
            'user_id' => $owner->id,
            'public_status' => 'published',
            'public_body' => 'Public body.',
            'citizen_status' => 'published',
            'citizen_body' => 'Citizen body.',
        ]);
        $doc = $this->storeEncryptedDocument($subject, 'doc.pdf', 500, 'public');
        $doc->update(['source_reference' => 'SECRET_SOURCE_REF_9x8y']);

        $this->actingAs($citizen)
            ->get(route('subjects.documents.edit', [$subject->slug, $doc->id]))
            ->assertForbidden();

        auth()->logout();
        $this->assertGuest();
        $this->get(route('subjects.documents.edit', [$subject->slug, $doc->id]))
            ->assertRedirect('/');
    }
}
