<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SivuSubjectCreationTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private SubCategory $subCategory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CategorySubcategorySeeder::class);
        $this->category = Category::where('slug', 'environnement-cadre-de-vie')->firstOrFail();
        $this->subCategory = SubCategory::where('slug', 'eau-energie-transition')->firstOrFail();
    }

    /** @test */
    public function it_creates_a_sivu_subject_with_citizen_body_and_documents(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $citizen = User::factory()->create(['role' => 'citoyen', 'email_verified_at' => now()]);

        $subject = Subject::create([
            'user_id' => $admin->id,
            'title' => 'SIVU Eau / Assainissement — Peyreleau / Le Rozier',
            'slug' => 'sivu-eau-assainissement',
            'theme' => 'Eau & Assainissement',
            'body' => 'Contenu instruction complet',
            'citizen_body' => 'Synthèse citoyenne',
            'public_body' => 'Aperçu public',
            'status' => 'draft',
            'visibility' => 'citoyen',
            'access_level' => 'standard',
            'citizen_status' => 'published',
            'public_status' => 'draft',
            'public_is_listed' => false,
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
        ]);

        SubjectDocument::create([
            'subject_id' => $subject->id,
            'user_id' => $admin->id,
            'filename' => 'DL_Dissolution_SIVU_Peyreleau_Le_Rozier.pdf',
            'stored_filename' => 'DL_Dissolution_SIVU_Peyreleau_Le_Rozier.pdf',
            'path' => 'subjects/sivu-eau-assainissement/DL_Dissolution_SIVU_Peyreleau_Le_Rozier.pdf',
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => 960691,
            'source_sha256' => '03289fdd09dd0fd9f50d3bc5d7eab1484d713be67b257c17f1c126c40c2b221e',
            'visibility' => 'citizen',
        ]);

        $this->assertDatabaseHas('subjects', [
            'slug' => 'sivu-eau-assainissement',
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'citizen_status' => 'published',
            'public_status' => 'draft',
        ]);

        $this->assertDatabaseHas('subject_documents', [
            'subject_id' => $subject->id,
            'source_sha256' => '03289fdd09dd0fd9f50d3bc5d7eab1484d713be67b257c17f1c126c40c2b221e',
        ]);

        
        $response = $this->actingAs($citizen)->get('/sujets/' . $subject->slug);
        $response->assertStatus(200);
        $response->assertSee('Synthèse citoyenne');
    }

    /** @test */
    public function guest_cannot_view_draft_sivu_subject(): void
    {
        $subject = Subject::create([
            'user_id' => User::factory()->create(['role' => 'admin'])->id,
            'title' => 'SIVU Eau / Assainissement',
            'slug' => 'sivu-eau-assainissement-test-guest',
            'theme' => 'Eau & Assainissement',
            'body' => 'Instruction',
            'citizen_body' => 'Citoyen',
            'public_body' => 'Public',
            'status' => 'draft',
            'visibility' => 'citoyen',
            'access_level' => 'standard',
            'citizen_status' => 'published',
            'public_status' => 'draft',
            'public_is_listed' => false,
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
        ]);

        $response = $this->get('/sujets/' . $subject->slug);
        $response->assertStatus(404);
    }
}
