<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\VisibilityLevel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubjectPreviewTest extends TestCase
{
    protected function setUpPreviewSubject(): Subject
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
            'requires_setup' => false,
            'username' => 'admin_preview_' . uniqid(),
        ]);

        $subject = Subject::factory()->create([
            'user_id' => $admin->id,
            'title' => 'Sujet test preview',
            'body' => "# Travail\\n\\n" . 'WORKING_BODY_SECRET_MARKER',
            'citizen_body' => "# Citoyen\\n\\n" . 'CITIZEN_BODY_SECRET_MARKER',
            'public_body' => "# Public\\n\\n" . 'PUBLIC_BODY_PREVIEW_MARKER',
            'status' => 'draft',
            'citizen_status' => 'draft',
            'public_status' => 'draft',
        ]);

        Storage::fake('documents');

        $service = app(\App\Services\DocumentStorageService::class);

        foreach ([
            ['working_doc.pdf', VisibilityLevel::Working, 'WORKING_DOC_SECRET_MARKER'],
            ['citizen_doc.pdf', VisibilityLevel::Citizen, 'CITIZEN_DOC_SECRET_MARKER'],
            ['public_doc.pdf', VisibilityLevel::Public, 'PUBLIC_DOC_PREVIEW_MARKER'],
        ] as [$filename, $level, $title]) {
            $pdf = UploadedFile::fake()->createWithContent($filename, 500);
            $path = $service->storeEncrypted($subject->id, $pdf->getRealPath(), $filename);
            Storage::disk('documents')->put($path, 'encrypted-' . $filename);
            SubjectDocument::create([
                'subject_id' => $subject->id,
                'filename' => $filename,
                'stored_filename' => basename($path),
                'path' => $path,
                'disk' => 'documents',
                'mime_type' => 'application/pdf',
                'size' => 500,
                'title' => $title,
                'visibility' => $level->value,
                'source_reference' => 'SOURCE_REF_SECRET_' . $level->value,
            ]);
        }

        return $subject->fresh();
    }

    public function test_admin_can_preview_public()
    {
        $subject = $this->setUpPreviewSubject();
        $admin = User::find($subject->user_id);

        $this->actingAs($admin)
            ->get(route('subjects.preview', [$subject->slug, 'public']))
            ->assertOk()
            ->assertSee('PREVIEW_BANNER_PUBLIC')
            ->assertSee('PUBLIC_BODY_PREVIEW_MARKER')
            ->assertSee('PUBLIC_DOC_PREVIEW_MARKER')
            ->assertDontSee('CITIZEN_BODY_SECRET_MARKER')
            ->assertDontSee('WORKING_BODY_SECRET_MARKER')
            ->assertDontSee('CITIZEN_DOC_SECRET_MARKER')
            ->assertDontSee('WORKING_DOC_SECRET_MARKER')
            ->assertDontSee('SOURCE_REF_SECRET_public');
    }

    public function test_admin_can_preview_citizen()
    {
        $subject = $this->setUpPreviewSubject();
        $admin = User::find($subject->user_id);

        $this->actingAs($admin)
            ->get(route('subjects.preview', [$subject->slug, 'citizen']))
            ->assertOk()
            ->assertSee('PREVIEW_BANNER_CITOYEN')
            ->assertSee('CITIZEN_BODY_SECRET_MARKER')
            ->assertSee('PUBLIC_DOC_PREVIEW_MARKER')
            ->assertSee('CITIZEN_DOC_SECRET_MARKER')
            ->assertDontSee('WORKING_BODY_SECRET_MARKER')
            ->assertDontSee('WORKING_DOC_SECRET_MARKER')
            ->assertDontSee('SOURCE_REF_SECRET_citizen')
            ->assertDontSee('SOURCE_REF_SECRET_public');
    }

    public function test_guest_cannot_access_preview()
    {
        $subject = $this->setUpPreviewSubject();

        auth()->logout();
        $this->assertGuest();

        $this->get(route('subjects.preview', [$subject->slug, 'public']))
            ->assertRedirect('/');
    }

    public function test_citizen_without_edit_permission_cannot_access_preview()
    {
        $subject = $this->setUpPreviewSubject();
        $citizen = User::factory()->create([
            'role' => 'citoyen',
            'email_verified_at' => now(),
            'requires_setup' => false,
            'username' => 'citizen_preview_' . uniqid(),
        ]);

        $this->actingAs($citizen)
            ->get(route('subjects.preview', [$subject->slug, 'public']))
            ->assertForbidden();
    }

    public function test_invalid_preview_audience_rejected()
    {
        $subject = $this->setUpPreviewSubject();
        $admin = User::find($subject->user_id);

        $this->actingAs($admin)
            ->get(route('subjects.preview', [$subject->slug, 'super-public']))
            ->assertNotFound();
    }

    public function test_public_preview_can_show_draft_public_body()
    {
        $subject = $this->setUpPreviewSubject();
        $admin = User::find($subject->user_id);

        $this->assertEquals('draft', $subject->public_status);

        $this->actingAs($admin)
            ->get(route('subjects.preview', [$subject->slug, 'public']))
            ->assertOk()
            ->assertSee('Brouillon')
            ->assertSee('PUBLIC_BODY_PREVIEW_MARKER');

        // Real guest still sees nothing
        auth()->logout();
        $this->get(route('subjects.show', $subject->slug))
            ->assertNotFound();
    }

    public function test_citizen_preview_can_show_draft_citizen_body()
    {
        $subject = $this->setUpPreviewSubject();
        $admin = User::find($subject->user_id);

        $this->assertEquals('draft', $subject->citizen_status);

        $this->actingAs($admin)
            ->get(route('subjects.preview', [$subject->slug, 'citizen']))
            ->assertOk()
            ->assertSee('CITIZEN_BODY_SECRET_MARKER');
    }

    public function test_preview_does_not_change_any_data_or_session_role()
    {
        $subject = $this->setUpPreviewSubject();
        $admin = User::find($subject->user_id);

        $originalStatus = $subject->public_status;
        $originalCitizenStatus = $subject->citizen_status;
        $originalUpdatedAt = $subject->updated_at;

        $response = $this->actingAs($admin)
            ->get(route('subjects.preview', [$subject->slug, 'public']));

        $response->assertOk();

        $this->assertAuthenticatedAs($admin);
        $this->assertTrue(auth()->user()->isAdmin());

        $subject->refresh();
        $this->assertEquals($originalStatus, $subject->public_status);
        $this->assertEquals($originalCitizenStatus, $subject->citizen_status);
        $this->assertEquals($originalUpdatedAt, $subject->updated_at);
    }

    public function test_public_preview_matches_real_guest_view_when_published()
    {
        $subject = $this->setUpPreviewSubject();
        $admin = User::find($subject->user_id);

        $subject->update([
            'public_status' => 'published',
            'citizen_status' => 'published',
        ]);

        // Admin preview public
        $previewContent = $this->actingAs($admin)
            ->get(route('subjects.preview', [$subject->slug, 'public']))
            ->getContent();

        // Real guest show
        auth()->logout();
        $this->assertGuest();
        $publicContent = $this->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->getContent();

        // Body and public doc marker should appear in both
        $this->assertStringContainsString('PUBLIC_BODY_PREVIEW_MARKER', $previewContent);
        $this->assertStringContainsString('PUBLIC_BODY_PREVIEW_MARKER', $publicContent);
        $this->assertStringContainsString('PUBLIC_DOC_PREVIEW_MARKER', $previewContent);
        $this->assertStringContainsString('PUBLIC_DOC_PREVIEW_MARKER', $publicContent);

        // Preview contains banner, real page does not
        $this->assertStringContainsString('PREVIEW_BANNER_PUBLIC', $previewContent);
        $this->assertStringNotContainsString('PREVIEW_BANNER_PUBLIC', $publicContent);
    }
}
