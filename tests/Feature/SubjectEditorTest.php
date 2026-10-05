<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Subject;
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
        $response->assertSee('Liste numérotée');
        $response->assertSee('Citation');
        $response->assertSee('Tableau');
        $response->assertSee('Lien');
        $response->assertSee('Image');

        $response->assertSee('data-markdown-editor', false);
        $response->assertSee('name="body"', false);
        $response->assertSee('id="preview"', false);
        $response->assertSee('data-insert', false);
        $response->assertSee('aria-label="Barre d’outils Markdown"', false);
        $response->assertSee('aria-label="Aperçu du rendu Markdown"', false);
        $response->assertSee('Écrire + aperçu');
    }

    public function test_editor_javascript_bundle_contains_markdown_helpers(): void
    {
        $path = base_path('resources/js/subject-editor.js');
        $this->assertFileExists($path);

        $js = file_get_contents($path);
        $this->assertStringContainsString('function buildMarkdownRenderer', $js);
        $this->assertStringContainsString('function insertTextAtCursor', $js);
        $this->assertStringContainsString('function setupMarkdownEditors', $js);
        $this->assertStringContainsString('activeEditor', $js);
        $this->assertStringContainsString('Aucun contenu pour cette version', $js);
    }

    public function test_publication_actions_are_outside_the_editor_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create([
            'user_id' => $admin->id,
            'citizen_status' => 'draft',
            'public_status' => 'draft',
        ]);

        $html = $this->actingAs($admin)
            ->get(route('subjects.edit', $subject->slug))
            ->assertOk()
            ->getContent();

        $editorAction = strpos($html, route('subjects.update', $subject->slug));
        $editorFormStart = $editorAction === false ? false : strrpos(substr($html, 0, $editorAction), '<form');
        $editorFormEnd = $editorFormStart === false ? false : strpos($html, '</form>', $editorFormStart);
        $publicationFormStart = strpos($html, route('subjects.publish.citizen', $subject->slug));

        $this->assertNotFalse($editorFormStart);
        $this->assertNotFalse($editorFormEnd);
        $this->assertNotFalse($publicationFormStart);
        $this->assertLessThan($publicationFormStart, $editorFormEnd);
    }
}
