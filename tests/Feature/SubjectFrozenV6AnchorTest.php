<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Reproduit le bug des ancres V6 : le body V6 gelé utilise des headings Markdown
 * sans syntaxe Pandoc, mais contient des liens interne vers #comprendre, #enjeux, ...
 * Le renderer doit générer ces IDs sans toucher au body.
 */
class SubjectFrozenV6AnchorTest extends TestCase
{
    use RefreshDatabase;

    private string $v6Body;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
        $this->v6Body = file_get_contents(
            '/home/aur-lien/Obsidian-Vault/LEX/08-publication/seraphotheque-v1/PUBLIC-V6-FINAL/01-SUJET/Comprendre-en-1-Minute-V6-FROZEN.md'
        );
    }

    private function seedSubject(): Subject
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
            'requires_setup' => false,
        ]);
        Category::factory()->create(['id' => 10, 'name' => 'Vie du village', 'slug' => 'vie-du-village']);
        SubCategory::factory()->create(['id' => 14, 'category_id' => 10, 'name' => 'Séraphothèque', 'slug' => 'seraphotheque']);

        return Subject::create([
            'user_id' => $admin->id,
            'category_id' => 10,
            'sub_category_id' => 14,
            'theme' => 'Vie du village',
            'title' => 'La Séraphothèque — Comprendre la situation',
            'slug' => 'seraphotheque-situation-2026',
            'body' => $this->v6Body,
            'citizen_body' => $this->v6Body,
            'public_body' => $this->v6Body,
            'status' => 'draft',
            'citizen_status' => 'draft',
            'public_status' => 'published',
            'public_is_listed' => false,
        ]);
    }

    private function assertV6Anchor(string $html, string $id): void
    {
        $this->assertStringContainsString('id="' . $id . '"', $html, "Ancre V6 #{$id} doit être rendue.");
    }

    /**
     * @test
     */
    public function frozen_v6_body_renders_all_canonical_anchors(): void
    {
        $subject = $this->seedSubject();

        $this->assertSame(
            '67a31398adb1e950fa5438f2c22c49b02452571bd431ebeed407c249c7d6675f',
            hash('sha256', $subject->public_body),
            'body V6 reste immutable.'
        );

        $html = $subject->renderBody();

        foreach ([
            'comprendre',
            'enjeux',
            'changements-2026',
            'chronologie',
            'positions',
            'desaccords',
            'questions-ouvertes',
            'documents',
            'lire-les-sources',
        ] as $id) {
            $this->assertV6Anchor($html, $id);
        }

        // Ensure there are no duplicate IDs for sections whose heading appears twice (e.g. "L'avenir du bâtiment")
        preg_match_all('/id="([^"]+)"/', $html, $matches);
        $this->assertSame($matches[1], array_unique($matches[1]), 'Aucun ID dupliqué.');
    }

    /**
     * @test
     */
    public function guest_view_has_no_dead_internal_links(): void
    {
        $subject = $this->seedSubject();

        $response = $this->get(route('subjects.show', $subject->slug));
        $response->assertOk();

        $html = $response->getContent();
        preg_match_all('/href="#([^"]+)"/', $html, $matches);
        foreach ($matches[1] as $anchor) {
            $this->assertStringContainsString('id="' . $anchor . '"', $html, "Ancre interne #{$anchor} doit exister.");
        }
    }

    /**
     * @test
     */
    public function heading_id_generation_handles_accents_and_collisions(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now(), 'requires_setup' => false]);
        Category::factory()->create(['id' => 10, 'name' => 'Vie du village', 'slug' => 'vie-du-village']);
        SubCategory::factory()->create(['id' => 14, 'category_id' => 10, 'name' => 'Séraphothèque', 'slug' => 'seraphotheque']);

        $subject = Subject::create([
            'user_id' => $admin->id,
            'category_id' => 10,
            'sub_category_id' => 14,
            'theme' => 'Vie du village',
            'title' => 'Collision test',
            'slug' => 'collision-test',
            'body' => "## Section\n\nA\n\n## Section\n\nB\n\n## Section spéciale !\n\nC",
            'citizen_body' => '',
            'public_body' => "## Section\n\nA\n\n## Section\n\nB\n\n## Section spéciale !\n\nC",
            'status' => 'draft',
            'citizen_status' => 'draft',
            'public_status' => 'published',
            'public_is_listed' => false,
        ]);

        $html = $subject->renderBody();
        $this->assertStringContainsString('id="section"', $html);
        $this->assertStringContainsString('id="section-2"', $html);
        $this->assertStringContainsString('id="section-speciale"', $html);
        preg_match_all('/id="([^"]+)"/', $html, $matches);
        $this->assertSame($matches[1], array_unique($matches[1]), 'Aucun ID dupliqué.');
    }
}
