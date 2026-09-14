<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\SubCategory;
use App\Models\User;
use App\Services\DocumentStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTreeControllerTest extends TestCase
{
    public function test_authenticated_user_sees_accessible_subjects_in_documents_tree()
    {
        Storage::fake('documents');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::factory()->create();
        $sub = SubCategory::factory()->create(['category_id' => $category->id]);
        $subject = Subject::factory()->create([
            'category_id'     => $category->id,
            'sub_category_id' => $sub->id,
            'status'          => 'published',
            'visibility'      => 'citoyen',
        ]);

        $service = app(DocumentStorageService::class);
        $pdf = UploadedFile::fake()->createWithContent('doc.pdf', str_repeat('x', 500));
        $path = $service->storeEncrypted($subject->id, $pdf->getRealPath(), 'doc.pdf');
        SubjectDocument::create([
            'subject_id'      => $subject->id,
            'filename'        => 'doc.pdf',
            'stored_filename' => basename($path),
            'path'            => $path,
            'disk'            => 'documents',
            'mime_type'       => 'application/pdf',
            'size'            => 500,
            'title'           => 'Doc',
            'category'        => 'source',
            'visibility'      => \App\Models\VisibilityLevel::Citizen->value,
        ]);

        $response = $this->actingAs($user)->getJson(route('documents.tree.documents.data'));
        $response->assertOk();

        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals($category->name, $data[0]['name']);
        $this->assertCount(1, $data[0]['subCategories']);
        $this->assertArrayHasKey('documents', $data[0]['subCategories'][0]['subjects'][0]);
        $this->assertCount(1, $data[0]['subCategories'][0]['subjects'][0]['documents']);
    }

    public function test_authenticated_user_sees_accessible_subjects_in_subjects_tree()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::factory()->create();
        $sub = SubCategory::factory()->create(['category_id' => $category->id]);
        Subject::factory()->create([
            'category_id'     => $category->id,
            'sub_category_id' => $sub->id,
            'status'          => 'published',
            'visibility'      => 'citoyen',
        ]);

        $response = $this->actingAs($user)->getJson(route('subjects.tree.data'));
        $response->assertOk();

        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals($category->name, $data[0]['name']);
        $this->assertCount(1, $data[0]['subCategories']);
        $this->assertArrayNotHasKey('documents', $data[0]['subCategories'][0]['subjects'][0]);
        $this->assertArrayHasKey('doc_count', $data[0]['subCategories'][0]['subjects'][0]);
    }

    public function test_guest_cannot_access_tree_data()
    {
        $this->getJson(route('documents.tree.documents.data'))
            ->assertUnauthorized();
    }

    public function test_draft_subjects_hidden_from_non_collaborators()
    {
        $category = Category::factory()->create();
        $sub = SubCategory::factory()->create(['category_id' => $category->id]);
        $owner = User::factory()->create();
        $draft = Subject::factory()->for($owner)->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'sub_category_id' => $sub->id,
            'status' => 'draft',
            'citizen_status' => 'draft',
            'public_status' => 'draft',
            'citizen_body' => null,
            'public_body' => null,
        ]);

        $otherUser = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($otherUser)->getJson(route('documents.tree.documents.data'));
        $this->assertEmpty($response->json());
    }
}
