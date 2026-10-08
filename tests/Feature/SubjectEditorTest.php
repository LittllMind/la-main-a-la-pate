<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_displays_markdown_and_toolbar_buttons(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/sujets/creer');

        $response->assertOk();
        $response->assertSee('Markdown');
        $response->assertSee('Titre');
        $response->assertSee('Sous-titre');
        $response->assertSee('Gras');
        $response->assertSee('Italique');
        $response->assertSee('Liste');
        $response->assertSee('Citation');
        $response->assertSee('Tableau');
        $response->assertSee('Lien');

        $response->assertSee('data-markdown-editor', false);
        $response->assertSee('name="body"', false);
        $response->assertSee('id="preview"', false);
        $response->assertSee('data-insert', false);
    }

    public function test_subject_show_renders_mixed_nested_list_under_alpha(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $category = Category::factory()->create();
        $subCategory = SubCategory::factory()->create(['category_id' => $category->id]);

        $body = "- Alpha\n  1. Bravo\n  - Charlie\n- Delta";

        $subject = Subject::create([
            'user_id' => $admin->id,
            'theme' => 'Test',
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'title' => 'Test Alpha Bravo Charlie Delta',
            'slug' => 'test-alpha-bravo-charlie-delta',
            'body' => $body,
            'status' => 'draft',
        ]);

        $this->assertSame($body, $subject->fresh()->body);

        $html = $this->actingAs($admin)
            ->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->getContent();

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xpath = new \DOMXPath($doc);

        $mainLists = $xpath->query('//ul[li[contains(text(), "Alpha")]]');
        $this->assertCount(1, $mainLists, 'A single main unordered list must contain Alpha.');

        $mainList = $mainLists->item(0);
        $topLevelItems = $xpath->query('./li', $mainList);
        $this->assertCount(2, $topLevelItems, 'Main list must contain exactly two top-level items (Alpha and Delta).');

        $alphaItem = $topLevelItems->item(0);
        $this->assertStringContainsString('Alpha', $alphaItem->textContent);
        $deltaItem = $topLevelItems->item(1);
        $this->assertStringContainsString('Delta', $deltaItem->textContent);

        $orderedChildren = $xpath->query('./ol/li', $alphaItem);
        $this->assertCount(1, $orderedChildren, 'Alpha must contain a nested ordered list with one item (Bravo).');
        $this->assertStringContainsString('Bravo', $orderedChildren->item(0)->textContent);

        $unorderedChildren = $xpath->query('./ul/li', $alphaItem);
        $this->assertCount(1, $unorderedChildren, 'Alpha must contain a nested unordered list with one item (Charlie).');
        $this->assertStringContainsString('Charlie', $unorderedChildren->item(0)->textContent);
    }

    public function test_edit_form_exposes_unsaved_state_hooks_without_autosave(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create(['user_id' => $admin->id]);

        $html = $this->actingAs($admin)
            ->get(route('subjects.edit', $subject->slug))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-unsaved-indicator', $html);
        $this->assertStringContainsString('Modifications non enregistrées', $html);
        $this->assertStringContainsString('data-confirm-leave', $html);
        $this->assertStringContainsString('data-save-button', $html);
        $this->assertStringNotContainsString('data-autosave', $html);
    }
}
