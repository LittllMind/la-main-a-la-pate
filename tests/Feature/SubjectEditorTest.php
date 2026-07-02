<?php

namespace Tests\Feature;

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

    public function test_editor_javascript_bundle_contains_markdown_helpers(): void
    {
        $path = base_path('resources/js/subject-editor.js');
        $this->assertFileExists($path);

        $js = file_get_contents($path);
        $this->assertStringContainsString('function buildMarkdownRenderer', $js);
        $this->assertStringContainsString('function insertTextAtCursor', $js);
        $this->assertStringContainsString('function setupMarkdownEditors', $js);
    }
}
