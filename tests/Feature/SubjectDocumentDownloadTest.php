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
        Storage::fake('subject_documents');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $user->id, 'status' => 'published']);

        $pdf = UploadedFile::fake()->create('source.pdf', 100, 'application/pdf');
        $stored = 'source-' . \Illuminate\Support\Str::random(6) . '.pdf';
        $path = "subject_documents/{$subject->id}/" . $stored;
        Storage::disk('subject_documents')->put($path, file_get_contents($pdf->getRealPath()));

        $document = SubjectDocument::create([
            'subject_id' => $subject->id,
            'filename' => 'source.pdf',
            'stored_filename' => $stored,
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 100 * 1024,
            'title' => 'Source PDF',
            'category' => 'source',
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
        ]);

        $this->actingAs($user)
            ->get(route('subjects.documents.download', [$subject->slug, $document->id]))
            ->assertNotFound();
    }
}
