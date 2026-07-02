<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectWikiContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_body_stores_and_renders_markdown(): void
    {
        $user = User::factory()->create(['role' => 'citoyen']);
        $body = "# Titre\n\nIntro avec **gras**.\n\n\u003e une citation\n\n- item\n\n| a | b |\n|---|---|\n| 1 | 2 |\n\n![legende](https://example.com/img.jpg)\n\n[lien](https://example.com)";

        $this->actingAs($user)
            ->post(route('subjects.store'), [
                'theme' => 'Urbanisme',
                'title' => 'Document markdown',
                'body' => $body,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('subjects', [
            'title' => 'Document markdown',
            'body' => $body,
        ]);

        $response = $this->actingAs($user)->get(route('subjects.show', 'document-markdown'));
        $response->assertOk();
        $response->assertSee('Titre');
        $response->assertSee('une citation');
        $response->assertSee('https://example.com/img.jpg');
        $response->assertSee('lien');
    }
}
