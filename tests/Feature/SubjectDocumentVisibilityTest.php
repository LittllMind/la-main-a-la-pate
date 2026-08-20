<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectDocumentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setupSubjectWithDocs(): Subject
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now(), 'requires_setup' => false]);
        \App\Models\Category::factory()->create(['id' => 10, 'name' => 'Vie du village', 'slug' => 'vie-du-village']);
        \App\Models\SubCategory::factory()->create(['id' => 14, 'category_id' => 10, 'name' => 'Séraphothèque', 'slug' => 'seraphotheque']);

        $subject = Subject::create([
            'user_id' => $admin->id,
            'category_id' => 10,
            'sub_category_id' => 14,
            'theme' => 'Vie du village',
            'title' => 'Test Séraphothèque',
            'slug' => 'test-visibility',
            'body' => '## Test',
            'citizen_body' => '## Test',
            'public_body' => '## Test',
            'status' => 'published',
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);

        // 1 Working doc (invisible to guest/citizen)
        SubjectDocument::create([
            'subject_id' => $subject->id,
            'filename' => 'doc.pdf',
            'stored_filename' => 'doc.pdf',
            'path' => 'test/doc.pdf',
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => 1000,
            'title' => 'Document Working',
            'visibility' => \App\Models\VisibilityLevel::Working->value,
            'position' => 1,
        ]);

        // 1 Citizen doc (visible to citizen + admin)
        SubjectDocument::create([
            'subject_id' => $subject->id,
            'filename' => 'citizen.pdf',
            'stored_filename' => 'citizen.pdf',
            'path' => 'test/citizen.pdf',
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => 1000,
            'title' => 'Document Citizen',
            'visibility' => \App\Models\VisibilityLevel::Citizen->value,
            'position' => 2,
        ]);

        // 1 Public doc (visible to all)
        SubjectDocument::create([
            'subject_id' => $subject->id,
            'filename' => 'public.pdf',
            'stored_filename' => 'public.pdf',
            'path' => 'test/public.pdf',
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => 1000,
            'title' => 'Document Public',
            'visibility' => \App\Models\VisibilityLevel::Public->value,
            'position' => 3,
        ]);

        return $subject->fresh();
    }

    public function test_guest_sees_only_public_documents_in_section(): void
    {
        $subject = $this->setupSubjectWithDocs();

        $response = $this->get(route('subjects.show', $subject->slug));
        $response->assertOk();

        $response->assertSee('Document Public');
        $response->assertDontSee('Document Citizen');
        $response->assertDontSee('Document Working');
    }

    public function test_citizen_sees_public_and_citizen_documents(): void
    {
        $subject = $this->setupSubjectWithDocs();
        $citizen = User::factory()->create(['role' => 'citoyen', 'email_verified_at' => now(), 'requires_setup' => false]);

        $response = $this->actingAs($citizen)->get(route('subjects.show', $subject->slug));
        $response->assertOk();

        $response->assertSee('Document Public');
        $response->assertSee('Document Citizen');
        $response->assertDontSee('Document Working');
    }

    public function test_admin_sees_all_documents(): void
    {
        $subject = $this->setupSubjectWithDocs();
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('subjects.show', $subject->slug));
        $response->assertOk();

        $response->assertSee('Document Public');
        $response->assertSee('Document Citizen');
        $response->assertSee('Document Working');
    }
}
