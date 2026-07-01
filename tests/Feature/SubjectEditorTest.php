<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_displays_wiki_toolbar_buttons(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/sujets/creer');

        $response->assertOk();
        $response->assertSee('Titre principal');
        $response->assertSee('Sous-titre');
        $response->assertSee('Gras');
        $response->assertSee('Italique');
        $response->assertSee('Liste à puces');
        $response->assertSee('Citation');
        $response->assertSee('Tableau');
        $response->assertSee('Image');
        $response->assertSee('Lien');

        // Les nouveaux boutons portent les bons identifiants data-cmd.
        $response->assertSee('insertQuote');
        $response->assertSee('insertTable');
        $response->assertSee('insertImage');
    }

    public function test_editor_assets_include_focus_and_formatblock_helpers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/sujets/creer');

        $response->assertSee('formatBlock');
        $response->assertSee('h2');
        $response->assertSee('contenteditable');
        $response->assertSee('editor-contenteditable');
    }

    public function test_editor_javascript_bundle_contains_wiki_helpers(): void
    {
        $path = base_path('resources/js/subject-editor.js');
        $this->assertFileExists($path);

        $js = file_get_contents($path);
        $this->assertStringContainsString('function insertLink()', $js);
        $this->assertStringContainsString('function insertImage()', $js);
        $this->assertStringContainsString('function insertTable()', $js);
        $this->assertStringContainsString('function insertQuote()', $js);
        $this->assertStringContainsString('queryCommandState', $js);
        $this->assertStringContainsString('queryCommandValue', $js);
        $this->assertStringContainsString('toolbar-active', $js);
    }
}
