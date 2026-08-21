<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectDocumentsAnchorTest extends TestCase
{
    use RefreshDatabase;

    protected function setup(): void
    {
        parent::setup();

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
            'requires_setup' => false,
        ]);

        \App\Models\Category::factory()->create(['id' => 10, 'name' => 'Vie du village', 'slug' => 'vie-du-village']);
        \App\Models\SubCategory::factory()->create(['id' => 14, 'category_id' => 10, 'name' => 'Séraphothèque', 'slug' => 'seraphotheque']);
    }

    /**
     * Guest body contient un lien #documents mais aucun doc public ne lui est visible.
     * Le hunk UX doit supprimer ce lien pour éviter un lien mort.
     */
    public function test_guest_does_not_see_documents_anchor_when_no_public_doc(): void
    {
        $admin = User::where('role', 'admin')->first();

        $subject = Subject::create([
            'user_id' => $admin->id,
            'category_id' => 10,
            'sub_category_id' => 14,
            'theme' => 'Vie du village',
            'title' => 'Test Anchor',
            'slug' => 'test-anchor',
            'body' => "## Sources\n\n[Voir les pièces](#documents)",
            'citizen_body' => "## Sources\n\n[Voir les pièces](#documents)",
            'public_body' => "## Sources\n\n[Voir les pièces](#documents)",
            'status' => 'published',
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);

        // Créer un document Working (invisible au public)
        SubjectDocument::create([
            'subject_id' => $subject->id,
            'filename' => 'working.pdf',
            'stored_filename' => 'working.pdf',
            'path' => 'test/working.pdf',
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => 1000,
            'title' => 'Doc Working',
            'visibility' => \App\Models\VisibilityLevel::Working->value,
            'position' => 1,
        ]);

        // Simuler SHOW controller : préfiltrer body + documents par audience (guest = null)
        $guest = null;
        $subject->body = $subject->bodyFor($guest);
        $subject->load(['documents' => fn ($query) => $query->visibleTo($guest)]);

        $html = $subject->renderBody();

        // Sans le hunk, le lien reste et pointe vers une section inexistante.
        $this->assertStringNotContainsString('href="#documents"', $html);
    }

    /**
     * Cas contrôle : un document public existe, le lien #documents DOIT rester.
     */
    public function test_guest_sees_documents_anchor_when_public_doc_exists(): void
    {
        $admin = User::where('role', 'admin')->first();

        $subject = Subject::create([
            'user_id' => $admin->id,
            'category_id' => 10,
            'sub_category_id' => 14,
            'theme' => 'Vie du village',
            'title' => 'Test Anchor Public',
            'slug' => 'test-anchor-public',
            'body' => "## Sources\n\n[Voir les pièces](#documents)",
            'citizen_body' => "## Sources\n\n[Voir les pièces](#documents)",
            'public_body' => "## Sources\n\n[Voir les pièces](#documents)",
            'status' => 'published',
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);

        SubjectDocument::create([
            'subject_id' => $subject->id,
            'filename' => 'public.pdf',
            'stored_filename' => 'public.pdf',
            'path' => 'test/public.pdf',
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => 1000,
            'title' => 'Doc Public',
            'visibility' => \App\Models\VisibilityLevel::Public->value,
            'position' => 1,
        ]);

        $guest = null;
        $subject->body = $subject->bodyFor($guest);
        $subject->load(['documents' => fn ($query) => $query->visibleTo($guest)]);

        $html = $subject->renderBody();

        $this->assertStringContainsString('href="#documents"', $html);
    }
}
