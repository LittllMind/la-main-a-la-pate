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

    public function test_edit_form_has_separate_publish_and_hide_forms(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $subCategory = SubCategory::factory()->create(['category_id' => $category->id]);

        $subject = Subject::create([
            'user_id' => $admin->id,
            'theme' => 'Test',
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'title' => 'Test formulaires séparés',
            'slug' => 'test-formulaires-separes',
            'body' => 'Corps instruction.',
            'citizen_body' => 'Corps citoyen.',
            'public_body' => 'Corps public.',
            'status' => 'draft',
            'citizen_status' => 'draft',
            'public_status' => 'draft',
        ]);

        $html = $this->actingAs($admin)
            ->get(route('subjects.edit', $subject->slug))
            ->assertOk()
            ->getContent();

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xpath = new \DOMXPath($doc);

        // Il doit y avoir exactement un formulaire principal (#subject-form) qui contient les trois textarea.
        $mainForm = $xpath->query('//form[@id="subject-form"]')->item(0);
        $this->assertNotNull($mainForm, 'Le formulaire principal doit exister.');

        $mainTextareas = $xpath->query('.//textarea', $mainForm);
        $this->assertCount(3, $mainTextareas, 'Le formulaire principal doit contenir les trois champs Markdown.');

        // Les actions de publication/masquage ne doivent pas être dans le formulaire principal.
        $nestedActionForms = $xpath->query('.//form', $mainForm);
        $this->assertCount(0, $nestedActionForms, 'Aucun formulaire imbriqué dans le formulaire principal.');

        // Les formulaires d'audience doivent exister en dehors et avoir leurs propres boutons submit.
        $publishPublicForms = $xpath->query('//form[contains(@action, "publier-public")]');
        $this->assertGreaterThanOrEqual(1, $publishPublicForms->length, 'Formulaire Publier public absent.');

        $hidePublicForms = $xpath->query('//form[contains(@action, "masquer-public")]');
        $this->assertGreaterThanOrEqual(1, $hidePublicForms->length, 'Formulaire Masquer public absent.');

        $publishCitizenForms = $xpath->query('//form[contains(@action, "publier-citoyen")]');
        $this->assertGreaterThanOrEqual(1, $publishCitizenForms->length, 'Formulaire Publier citoyen absent.');

        $hideCitizenForms = $xpath->query('//form[contains(@action, "masquer-citoyen")]');
        $this->assertGreaterThanOrEqual(1, $hideCitizenForms->length, 'Formulaire Masquer citoyen absent.');

        // Les boutons d'action doivent appartenir à leurs formulaires respectifs (attribut form ou être descendants directs).
        $actionButtons = $xpath->query('//button[@form]');
        $this->assertGreaterThanOrEqual(1, $actionButtons->length, 'Les boutons d\'action doivent avoir un attribut form.');
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

    public function test_edit_form_uses_valid_audience_labels(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create([
            'user_id' => $admin->id,
            'citizen_body' => 'Contenu citoyen',
            'public_body' => 'Contenu public',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('subjects.edit', $subject->slug))
            ->assertOk();

        $response->assertSee('Instruction');
        $response->assertSee('Public connecté');
        $response->assertSee('Public déconnecté');

        $response->assertDontSee('Travail');
        $response->assertDontSee('Citoyen');
        $response->assertDontSee('>Public<'); // libellé "Public" seul, pas "Public déconnecté"
    }
}
