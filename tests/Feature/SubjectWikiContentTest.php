<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectWikiContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_body_stores_and_renders_markdown(): void
    {
        $user = User::factory()->create(['role' => 'citoyen']);
        $category = Category::factory()->create();
        $subCategory = SubCategory::factory()->create(['category_id' => $category->id]);

        $body = "# Titre\n\nIntro avec **gras**.\n\n> une citation\n\n- item\n\n| a | b |\n|---|---|\n| 1 | 2 |\n\n![legende](https://example.com/img.jpg)\n\n[lien](https://example.com)";

        $this->actingAs($user)
            ->post(route('subjects.store'), [
                'category_id' => $category->id,
                'sub_category_id' => $subCategory->id,
                'title' => 'Document markdown',
                'body' => $body,
                'citizen_body' => $body,
                'public_body' => $body,
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
