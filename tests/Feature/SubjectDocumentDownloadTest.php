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
}
