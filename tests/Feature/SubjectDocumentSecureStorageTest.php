<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\User;
use App\Services\DocumentStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubjectDocumentSecureStorageTest extends TestCase
{
    public function storeEncryptedDocument(Subject $subject, string $filename = 'source.pdf', int $contentSize = 5000): SubjectDocument
    {
        $service = app(DocumentStorageService::class);
        $pdf = UploadedFile::fake()->createWithContent($filename, str_repeat('x', $contentSize));
        $path = $service->storeEncrypted($subject->id, $pdf->getRealPath(), $filename);

        return SubjectDocument::create([
            'subject_id'      => $subject->id,
            'filename'        => $filename,
            'stored_filename' => basename($path),
            'path'            => $path,
            'disk'            => 'documents',
            'mime_type'       => 'application/pdf',
            'size'            => $contentSize,
            'title'           => 'Source PDF',
            'category'        => 'source',
            'visibility'      => \App\Models\VisibilityLevel::Citizen->value,
        ]);
    }

    public function test_authenticated_user_can_download_encrypted_document()
    {
        Storage::fake('documents');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $user->id, 'status' => 'published']);
        $document = $this->storeEncryptedDocument($subject);

        $response = $this->actingAs($user)
            ->get(route('subjects.documents.download', [$subject->slug, $document->id]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition');
    }

    public function test_guest_is_redirected_to_login_for_document_download()
    {
        Storage::fake('documents');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $user->id, 'status' => 'published']);
        $document = $this->storeEncryptedDocument($subject);

        $this->get(route('subjects.documents.download', [$subject->slug, $document->id]))
            ->assertRedirect('/login');
    }

    public function test_document_upload_encrypts_file()
    {
        Storage::fake('documents');

        $user = User::factory()->create(['email_verified_at' => now(), 'role' => 'admin']);
        $subject = Subject::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

        $pdf = UploadedFile::fake()->createWithContent('upload.pdf', str_repeat('PDF', 5 * 1024));

        $this->actingAs($user)
            ->from(route('subjects.documents.index', $subject->slug))
            ->post(route('subjects.documents.store', $subject->slug), [
                'file'     => $pdf,
                'title'    => 'Upload test',
                'category' => 'source',
            ])
            ->assertRedirect();

        $doc = SubjectDocument::where('subject_id', $subject->id)->first();
        $this->assertNotNull($doc);
        $this->assertEquals('documents', $doc->disk);
        $this->assertStringEndsWith('.enc', $doc->path);

        $service = app(DocumentStorageService::class);
        $this->assertTrue($service->exists($doc->path));

        $decrypted = $service->decrypt($doc->path);
        $this->assertGreaterThan(0, strlen($decrypted));
    }

    public function test_encrypted_document_cannot_be_read_from_public_path()
    {
        Storage::fake('documents');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $user->id, 'status' => 'published']);
        $document = $this->storeEncryptedDocument($subject);

        // Aucune URL publique ne doit pointer vers le fichier clair
        $this->assertNull($document->previewUrl());

        // La route application doit être protégée
        $this->get(route('subjects.documents.download', [$subject->slug, $document->id]))
            ->assertRedirect('/login');
    }
}
