<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubjectDocumentDownloadTest extends TestCase
{
    public function test_document_download_returns_pdf_for_existing_file()
    {
        Storage::fake('documents');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $user->id, 'status' => 'published']);

        $service = app(\App\Services\DocumentStorageService::class);
        $pdf = UploadedFile::fake()->createWithContent('source.pdf', str_repeat('x', 1000));
        $path = $service->storeEncrypted($subject->id, $pdf->getRealPath(), 'source.pdf');

        $document = SubjectDocument::create([
            'subject_id' => $subject->id,
            'filename' => 'source.pdf',
            'stored_filename' => basename($path),
            'path' => $path,
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => 1000,
            'title' => 'Source PDF',
            'category' => 'source',
            'visibility' => \App\Models\VisibilityLevel::Citizen->value,
        ]);

        $this->actingAs($user)
            ->get(route('subjects.documents.download', [$subject->slug, $document->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_document_download_returns_404_for_missing_file()
    {
        Storage::fake('subject_documents');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $user->id, 'status' => 'published']);

        $document = SubjectDocument::create([
            'subject_id' => $subject->id,
            'filename' => 'missing.pdf',
            'stored_filename' => 'missing.pdf',
            'path' => 'subject_documents/99/missing.pdf',
            'mime_type' => 'application/pdf',
            'size' => 0,
            'title' => 'Missing',
            'category' => 'source',
            'visibility' => \App\Models\VisibilityLevel::Citizen->value,
        ]);

        $this->actingAs($user)
            ->get(route('subjects.documents.download', [$subject->slug, $document->id]))
            ->assertNotFound();
    }

    public function test_public_text_document_download_succeeds_for_guest_when_subject_is_published(): void
    {
        Storage::fake('documents');

        $service = app(\App\Services\DocumentStorageService::class);
        $plainText = "From: emetteur\nTo: destinataire\nSubject: Information\n\nCorps du message\n";
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('email.json', $plainText);
        $subject = Subject::factory()->create([
            'status' => 'published',
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);
        $path = $service->storeEncrypted($subject->id, $file->getRealPath(), 'email.json');

        $document = SubjectDocument::create([
            'subject_id' => $subject->id,
            'filename' => 'Email-2026-07-01_deplacement-portants.json',
            'stored_filename' => basename($path),
            'path' => $path,
            'disk' => 'documents',
            'mime_type' => 'text/plain',
            'size' => strlen($plainText),
            'title' => 'Information déplacement portants',
            'category' => 'source',
            'visibility' => \App\Models\VisibilityLevel::Public->value,
        ]);

        $response = $this->get(route('subjects.documents.download', [$subject->slug, $document->id]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        ob_start();
        $response->baseResponse->sendContent();
        $body = ob_get_clean();

        $this->assertSame($plainText, $body);
    }

    public function test_download_error_does_not_disclose_internal_data_to_guest(): void
    {
        Storage::fake('documents');

        $subject = Subject::factory()->create([
            'status' => 'published',
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);

        $document = SubjectDocument::create([
            'subject_id' => $subject->id,
            'filename' => 'Email-2026-07-01_deplacement-portants.json',
            'stored_filename' => 'secret.enc',
            'path' => 'be/27/secret.enc',
            'disk' => 'documents',
            'mime_type' => 'text/plain',
            'size' => 2000,
            'title' => 'Information déplacement portants',
            'category' => 'source',
            'visibility' => \App\Models\VisibilityLevel::Public->value,
        ]);

        // Simuler un stockage qui reconnait l'existence du fichier mais echoue au dechiffrement.
        $mockStorage = $this->createMock(\App\Services\DocumentStorageService::class);
        $mockStorage->method('exists')->willReturn(true);
        $mockStorage->method('decrypt')->willThrowException(
            new \RuntimeException('Decryption failed for secret path /home/REDACTED/documents/be/27/secret.enc')
        );
        $this->app->instance(\App\Services\DocumentStorageService::class, $mockStorage);

        config(['app.debug' => false]);

        $response = $this->get(route('subjects.documents.download', [$subject->slug, $document->id]));

        $response->assertStatus(500);
        $response->assertDontSee('/home/');
        $response->assertDontSee('secret.enc');
        $response->assertDontSee('Decryption failed');
        $response->assertDontSee('Corps du message');
    }

    public function test_citizen_document_is_not_accessible_to_guest(): void
    {
        Storage::fake('documents');

        $subject = Subject::factory()->create([
            'status' => 'published',
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);

        $document = SubjectDocument::create([
            'subject_id' => $subject->id,
            'filename' => 'citizen.pdf',
            'stored_filename' => 'citizen.enc',
            'path' => 'path/citizen.enc',
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => 1000,
            'title' => 'Document Citizen',
            'category' => 'source',
            'visibility' => \App\Models\VisibilityLevel::Citizen->value,
        ]);

        $this->get(route('subjects.documents.download', [$subject->slug, $document->id]))
            ->assertNotFound();
    }

    public function test_working_document_is_not_accessible_to_guest(): void
    {
        Storage::fake('documents');

        $subject = Subject::factory()->create([
            'status' => 'published',
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);

        $document = SubjectDocument::create([
            'subject_id' => $subject->id,
            'filename' => 'working.pdf',
            'stored_filename' => 'working.enc',
            'path' => 'path/working.enc',
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => 1000,
            'title' => 'Document Working',
            'category' => 'source',
            'visibility' => \App\Models\VisibilityLevel::Working->value,
        ]);

        $this->get(route('subjects.documents.download', [$subject->slug, $document->id]))
            ->assertNotFound();
    }

    private function storeDocument(Subject $subject, string $filename, string $content, string $mimeType, \App\Models\VisibilityLevel $visibility): SubjectDocument
    {
        $service = app(\App\Services\DocumentStorageService::class);
        $file = UploadedFile::fake()->createWithContent($filename, $content);
        $path = $service->storeEncrypted($subject->id, $file->getRealPath(), $filename);

        return SubjectDocument::create([
            'subject_id' => $subject->id,
            'filename' => $filename,
            'stored_filename' => basename($path),
            'path' => $path,
            'disk' => 'documents',
            'mime_type' => $mimeType,
            'size' => strlen($content),
            'title' => 'Document ' . $filename,
            'category' => 'source',
            'visibility' => $visibility->value,
        ]);
    }

    public function test_public_pdf_view_returns_inline_content_type(): void
    {
        Storage::fake('documents');

        $subject = Subject::factory()->create([
            'status' => 'published',
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);

        $document = $this->storeDocument($subject, 'source.pdf', "%PDF-1.4 fake\n", 'application/pdf', \App\Models\VisibilityLevel::Public);

        $response = $this->get(route('subjects.documents.view', [$subject->slug, $document->id]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="source.pdf"');
    }

    public function test_public_html_view_returns_text_html_inline(): void
    {
        Storage::fake('documents');

        $subject = Subject::factory()->create([
            'status' => 'published',
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);

        $html = "<!DOCTYPE html><html><body><h1>Statique</h1></body></html>";
        $document = $this->storeDocument($subject, 'source.html', $html, 'text/html', \App\Models\VisibilityLevel::Public);

        $response = $this->get(route('subjects.documents.view', [$subject->slug, $document->id]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'inline; filename="source.html"');

        ob_start();
        $response->baseResponse->sendContent();
        $body = ob_get_clean();

        $this->assertSame($html, $body);
    }

    public function test_public_png_view_returns_image_png_inline(): void
    {
        Storage::fake('documents');

        $subject = Subject::factory()->create([
            'status' => 'published',
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);

        $document = $this->storeDocument($subject, 'source.png', "PNGFAKEBYTES", 'image/png', \App\Models\VisibilityLevel::Public);

        $response = $this->get(route('subjects.documents.view', [$subject->slug, $document->id]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Content-Disposition', 'inline; filename="source.png"');
    }

    public function test_document_view_error_does_not_disclose_internal_data_to_guest(): void
    {
        Storage::fake('documents');

        $subject = Subject::factory()->create([
            'status' => 'published',
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);

        $document = SubjectDocument::create([
            'subject_id' => $subject->id,
            'filename' => 'public.pdf',
            'stored_filename' => 'secret.enc',
            'path' => 'be/27/secret.enc',
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => 2000,
            'title' => 'Document public',
            'category' => 'source',
            'visibility' => \App\Models\VisibilityLevel::Public->value,
        ]);

        $mockStorage = $this->createMock(\App\Services\DocumentStorageService::class);
        $mockStorage->method('exists')->willReturn(true);
        $mockStorage->method('decrypt')->willThrowException(
            new \RuntimeException('Decryption failed for secret path /home/REDACTED/documents/be/27/secret.enc')
        );
        $this->app->instance(\App\Services\DocumentStorageService::class, $mockStorage);

        config(['app.debug' => false]);

        $response = $this->get(route('subjects.documents.view', [$subject->slug, $document->id]));

        $response->assertStatus(500);
        $response->assertDontSee('/home/');
        $response->assertDontSee('secret.enc');
        $response->assertDontSee('Decryption failed');
    }

    public function test_missing_binary_document_hides_view_and_download_cta(): void
    {
        Storage::fake('documents');

        $subject = Subject::factory()->create([
            'status' => 'published',
            'citizen_status' => 'published',
            'public_status' => 'published',
            'public_body' => '## Public',
        ]);

        SubjectDocument::create([
            'subject_id' => $subject->id,
            'filename' => 'missing.pdf',
            'stored_filename' => 'missing.enc',
            'path' => 'path/missing.enc',
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => 0,
            'title' => 'Document sans binaire',
            'category' => 'source',
            'visibility' => \App\Models\VisibilityLevel::Public->value,
        ]);

        $this->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('Document sans binaire')
            ->assertDontSee('data-testid="btn-doc-view"')
            ->assertDontSee('data-testid="btn-doc-download"');
    }

    public function test_citizen_document_view_is_not_accessible_to_guest(): void
    {
        Storage::fake('documents');

        $subject = Subject::factory()->create([
            'status' => 'published',
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);

        $document = $this->storeDocument($subject, 'citizen.pdf', "%PDF-1.4\n", 'application/pdf', \App\Models\VisibilityLevel::Citizen);

        $this->get(route('subjects.documents.view', [$subject->slug, $document->id]))
            ->assertNotFound();
    }
}
