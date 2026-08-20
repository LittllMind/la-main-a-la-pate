<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\User;
use App\Models\VisibilityLevel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubjectDocumentVisibilityUploadTest extends TestCase
{
    private function storeDocument(array $data, User $admin, Subject $subject)
    {
        $pdf = UploadedFile::fake()->createWithContent('upload.pdf', 'PDF content example');

        return $this->actingAs($admin)
            ->from(route('subjects.documents.index', $subject->slug))
            ->post(route('subjects.documents.store', $subject->slug), array_merge([
                'file' => $pdf,
                'title' => 'Document test',
                'category' => 'source',
            ], $data));
    }

    public function test_upload_without_visibility_defaults_to_working()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);

        $this->storeDocument([], $admin, $subject)
            ->assertRedirect();

        $doc = SubjectDocument::where('subject_id', $subject->id)->first();
        $this->assertNotNull($doc);
        $this->assertEquals(VisibilityLevel::Working->value, $doc->visibility->value ?? $doc->visibility);
    }

    public function test_upload_with_working_visibility()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);

        $this->storeDocument(['visibility' => 'working'], $admin, $subject)
            ->assertRedirect();

        $doc = SubjectDocument::where('subject_id', $subject->id)->first();
        $this->assertEquals(VisibilityLevel::Working->value, $doc->visibility->value ?? $doc->visibility);
    }

    public function test_upload_with_citizen_visibility()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);

        $this->storeDocument(['visibility' => 'citizen'], $admin, $subject)
            ->assertRedirect();

        $doc = SubjectDocument::where('subject_id', $subject->id)->first();
        $this->assertEquals(VisibilityLevel::Citizen->value, $doc->visibility->value ?? $doc->visibility);
    }

    public function test_upload_with_public_visibility()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);

        $this->storeDocument(['visibility' => 'public'], $admin, $subject)
            ->assertRedirect();

        $doc = SubjectDocument::where('subject_id', $subject->id)->first();
        $this->assertEquals(VisibilityLevel::Public->value, $doc->visibility->value ?? $doc->visibility);
    }

    public function test_upload_with_invalid_visibility_is_rejected()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);

        $this->storeDocument(['visibility' => 'invalid'], $admin, $subject)
            ->assertSessionHasErrors('visibility');

        $this->assertEquals(0, SubjectDocument::where('subject_id', $subject->id)->count());
    }

    public function test_upload_with_owner_user_can_set_public_visibility()
    {
        Storage::fake('documents');

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $owner->id]);

        $pdf = UploadedFile::fake()->createWithContent('upload.pdf', 'PDF content example');

        $this->actingAs($owner)
            ->from(route('subjects.documents.index', $subject->slug))
            ->post(route('subjects.documents.store', $subject->slug), [
                'file' => $pdf,
                'title' => 'Doc public',
                'category' => 'source',
                'visibility' => 'public',
            ])
            ->assertRedirect();

        $doc = SubjectDocument::where('subject_id', $subject->id)->first();
        $this->assertEquals(VisibilityLevel::Public->value, $doc->visibility->value ?? $doc->visibility);
    }

    public function test_guest_cannot_upload_document()
    {
        Storage::fake('documents');

        $subject = Subject::factory()->create();
        $pdf = UploadedFile::fake()->createWithContent('upload.pdf', 'PDF content example');

        $this->post(route('subjects.documents.store', $subject->slug), [
            'file' => $pdf,
            'title' => 'Doc public',
            'category' => 'source',
            'visibility' => 'public',
        ])
            ->assertRedirect('/');

        $this->assertEquals(0, SubjectDocument::where('subject_id', $subject->id)->count());
    }

    public function test_uploaded_document_visibility_is_effective_for_admin()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create([
            'user_id' => $admin->id,
            'public_status' => 'published',
            'public_body' => 'Version publique.',
            'citizen_status' => 'published',
            'citizen_body' => 'Version citoyenne.',
        ]);

        SubjectDocument::factory()->for($subject)->public()
            ->create(['title' => 'PUBLIC_DOC_MARKER_7x9a', 'stored_filename' => 'public.enc', 'path' => 'subjects/1/public.enc']);
        SubjectDocument::factory()->for($subject)->citizen()
            ->create(['title' => 'CITIZEN_DOC_MARKER_3k2m', 'stored_filename' => 'citizen.enc', 'path' => 'subjects/1/citizen.enc']);
        SubjectDocument::factory()->for($subject)->working()
            ->create(['title' => 'WORKING_DOC_MARKER_5p1q', 'stored_filename' => 'working.enc', 'path' => 'subjects/1/working.enc']);

        $this->actingAs($admin)
            ->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('PUBLIC_DOC_MARKER_7x9a')
            ->assertSee('CITIZEN_DOC_MARKER_3k2m')
            ->assertSee('WORKING_DOC_MARKER_5p1q');
    }

    public function test_uploaded_document_visibility_is_effective_for_citizen()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $citizen = User::factory()->create(['role' => 'citoyen', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create([
            'user_id' => $admin->id,
            'public_status' => 'published',
            'public_body' => 'Version publique.',
            'citizen_status' => 'published',
            'citizen_body' => 'Version citoyenne.',
        ]);

        SubjectDocument::factory()->for($subject)->public()
            ->create(['title' => 'PUBLIC_DOC_MARKER_7x9a', 'stored_filename' => 'public.enc', 'path' => 'subjects/1/public.enc']);
        SubjectDocument::factory()->for($subject)->citizen()
            ->create(['title' => 'CITIZEN_DOC_MARKER_3k2m', 'stored_filename' => 'citizen.enc', 'path' => 'subjects/1/citizen.enc']);
        SubjectDocument::factory()->for($subject)->working()
            ->create(['title' => 'WORKING_DOC_MARKER_5p1q', 'stored_filename' => 'working.enc', 'path' => 'subjects/1/working.enc']);

        $this->actingAs($citizen)
            ->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('PUBLIC_DOC_MARKER_7x9a')
            ->assertSee('CITIZEN_DOC_MARKER_3k2m')
            ->assertDontSee('WORKING_DOC_MARKER_5p1q');
    }

    public function test_uploaded_document_visibility_is_effective_for_guest()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create([
            'user_id' => $admin->id,
            'public_status' => 'published',
            'public_body' => 'Version publique.',
            'citizen_status' => 'published',
            'citizen_body' => 'Version citoyenne.',
        ]);

        SubjectDocument::factory()->for($subject)->public()
            ->create(['title' => 'PUBLIC_DOC_MARKER_7x9a', 'stored_filename' => 'public.enc', 'path' => 'subjects/1/public.enc']);
        SubjectDocument::factory()->for($subject)->citizen()
            ->create(['title' => 'CITIZEN_DOC_MARKER_3k2m', 'stored_filename' => 'citizen.enc', 'path' => 'subjects/1/citizen.enc']);
        SubjectDocument::factory()->for($subject)->working()
            ->create(['title' => 'WORKING_DOC_MARKER_5p1q', 'stored_filename' => 'working.enc', 'path' => 'subjects/1/working.enc']);

        auth()->logout();

        $this->assertGuest();

        $this->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('PUBLIC_DOC_MARKER_7x9a')
            ->assertDontSee('CITIZEN_DOC_MARKER_3k2m')
            ->assertDontSee('WORKING_DOC_MARKER_5p1q');
    }

    public function test_document_update_can_change_visibility()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);
        $doc = SubjectDocument::factory()->for($subject)->working()->create();

        $this->actingAs($admin)
            ->from(route('subjects.documents.index', $subject->slug))
            ->patch(route('subjects.documents.update', [$subject->slug, $doc->id]), [
                'visibility' => 'public',
            ])
            ->assertRedirect();

        $doc->refresh();
        $this->assertEquals(VisibilityLevel::Public->value, $doc->visibility->value ?? $doc->visibility);
    }

    public function test_document_update_with_invalid_visibility_is_rejected()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);
        $doc = SubjectDocument::factory()->for($subject)->working()->create();

        $this->actingAs($admin)
            ->from(route('subjects.documents.index', $subject->slug))
            ->patch(route('subjects.documents.update', [$subject->slug, $doc->id]), [
                'visibility' => 'not-a-level',
            ])
            ->assertSessionHasErrors('visibility');

        $doc->refresh();
        $this->assertEquals(VisibilityLevel::Working->value, $doc->visibility->value ?? $doc->visibility);
    }

    public function test_owner_can_update_document_visibility()
    {
        Storage::fake('documents');

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $owner->id]);
        $doc = SubjectDocument::factory()->for($subject)->working()->create();

        $this->actingAs($owner)
            ->from(route('subjects.documents.index', $subject->slug))
            ->patch(route('subjects.documents.update', [$subject->slug, $doc->id]), [
                'visibility' => 'citizen',
            ])
            ->assertRedirect();

        $doc->refresh();
        $this->assertEquals(VisibilityLevel::Citizen->value, $doc->visibility->value ?? $doc->visibility);
    }

    public function test_non_authorized_user_cannot_change_visibility()
    {
        Storage::fake('documents');

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create(['email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $owner->id]);
        $doc = SubjectDocument::factory()->for($subject)->working()->create();

        $this->actingAs($other)
            ->from(route('subjects.documents.index', $subject->slug))
            ->patch(route('subjects.documents.update', [$subject->slug, $doc->id]), [
                'visibility' => 'public',
            ])
            ->assertForbidden();

        $doc->refresh();
        $this->assertEquals(VisibilityLevel::Working->value, $doc->visibility->value ?? $doc->visibility);
    }

    public function test_markdown_to_pdf_generated_document_defaults_to_working()
    {
        Storage::fake('documents');

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->from(route('subjects.documents.index', $subject->slug))
            ->post(route('subjects.documents.markdown-pdf', $subject->slug), [
                'title' => 'Test PDF',
                'markdown' => '# Hello',
                'category' => 'annexe',
                'visibility' => 'public',
            ])
            ->assertRedirect();

        $doc = SubjectDocument::where('subject_id', $subject->id)->first();
        $this->assertNotNull($doc);
        $this->assertEquals(VisibilityLevel::Working->value, $doc->visibility->value ?? $doc->visibility);
    }
}
