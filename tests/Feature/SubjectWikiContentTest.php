<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectWikiContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_body_keeps_wiki_tags_after_sanitization(): void
    {
        $user = User::factory()->create(['role' => 'citoyen', 'password' => 'password']);
        $body = "<p>Intro</p>\n"
            . "<h2>Titre</h2>\n"
            . "<blockquote>une citation</blockquote>\n"
            . "<ul>\u003cli>item</li>\u003c/ul>\n"
            . "<table class='wiki-table'><tbody><tr><td>a</td><td>b</td>\u003c/tr>\u003c/tbody>\u003c/table>\n"
            . "<p><img src=\"https://example.com/img.jpg\" alt=\"test\"></p>\n"
            . "<p><a href='https://example.com' title='lien'>liens</a></p>";

        $this->actingAs($user)
            ->post(route('subjects.store'), [
                'theme' => 'Urbanisme',
                'title' => 'Document wiki',
                'body' => $body,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('subjects', [
            'title' => 'Document wiki',
        ]);

        $response = $this->actingAs($user)->get(route('subjects.show', 'document-wiki'));
        $response->assertOk();
        $response->assertSee('une citation');
        $response->assertSee('wiki-table');
        $response->assertSee('https://example.com/img.jpg');
        $response->assertSee('liens');
    }
}
