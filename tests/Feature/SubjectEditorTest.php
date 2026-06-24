<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_assets_include_focus_and_formatblock_helpers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/sujets/creer');

        $response->assertOk();
        $response->assertSee('Titre principal');
        $response->assertSee('Sous-titre');
        $response->assertSee('Gras');
        $response->assertSee('Italique');
        $response->assertSee('Liste à puces');
        $response->assertSee('Lien');
        $response->assertSee('createLink');
    }

    public function test_editor_link_helper_is_present(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/sujets/creer');

        $response->assertSee('insertLink');
    }

    public function test_editor_initializes_active_state_helpers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/sujets/creer');

        $response->assertSee("queryCommandState");
        $response->assertSee("queryCommandValue");
        $response->assertSee("toolbar-active");
    }
}
