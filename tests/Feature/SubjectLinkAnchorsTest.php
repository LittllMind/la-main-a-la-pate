<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectLinkAnchorsTest extends TestCase
{
    use RefreshDatabase;

    protected function setupSubjectWithAnchors(): Subject
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now(), 'requires_setup' => false]);
        \App\Models\Category::factory()->create(['id' => 10, 'name' => 'Vie du village', 'slug' => 'vie-du-village']);
        \App\Models\SubCategory::factory()->create(['id' => 14, 'category_id' => 10, 'name' => 'Séraphothèque', 'slug' => 'seraphotheque']);

        return Subject::create([
            'user_id' => $admin->id,
            'category_id' => 10,
            'sub_category_id' => 14,
            'theme' => 'Vie du village',
            'title' => 'Test Séraphothèque',
            'slug' => 'test-seraphotheque-anchors',
            'body' => "## Chronologie {#chronologie}\n\nTexte.\n\n## Comparatif {#comparatif-2025-2026}\n\nTexte.",
            'citizen_body' => "## Chronologie {#chronologie}\n\nTexte.",
            'public_body' => "## Comparatif {#comparatif-2025-2026}\n\nTexte.",
            'status' => 'published',
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);
    }

    public function test_pandoc_heading_ids_are_rendered_as_html_ids(): void
    {
        $subject = $this->setupSubjectWithAnchors();

        $html = $subject->renderBody();

        $this->assertStringContainsString('id="comparatif-2025-2026"', $html);
        // But the raw {#...} should NOT appear in output
        $this->assertStringNotContainsString('{#comparatif-2025-2026}', $html);
        // Heading element itself must carry the id (Pandoc semantic)
        $this->assertStringContainsString('<h2 id="chronologie">Chronologie</h2>', $html);
    }

    public function test_heading_id_allows_anchor_navigation(): void
    {
        $subject = $this->setupSubjectWithAnchors();

        // Published subject — public view should render public_body with anchors
        $response = $this->get(route('subjects.show', $subject->slug));

        $response->assertOk();
        // public_body contains {#comparatif-2025-2026} only, not {#chronologie}
        $response->assertSee('id="comparatif-2025-2026"', false);
        // raw {#...} should never leak
        $response->assertDontSee('{#comparatif-2025-2026}', false);
    }
}
