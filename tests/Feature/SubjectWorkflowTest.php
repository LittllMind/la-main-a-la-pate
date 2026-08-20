<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_citizen_can_create_a_subject(): void
    {
        $category = Category::factory()->create();
        $subCategory = SubCategory::factory()->create(['category_id' => $category->id]);
        $user = User::factory()->create(['role' => 'citoyen']);

        $response = $this->actingAs($user)->post('/sujets', [
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'title' => 'Réouverture de la boutique',
            'body' => '<p>Document de travail sur la réouverture.</p>',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('subjects', [
            'title' => 'Réouverture de la boutique',
            'theme' => $category->name,
            'status' => 'draft',
            'user_id' => $user->id,
        ]);
    }

    public function test_guests_cannot_create_subjects(): void
    {
        $category = Category::factory()->create();
        $subCategory = SubCategory::factory()->create(['category_id' => $category->id]);

        $response = $this->post('/sujets', [
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'title' => 'Test',
            'body' => 'Test',
        ]);

        $response->assertRedirect('/');
    }

    public function test_subject_owner_can_edit_document(): void
    {
        $user = User::factory()->create();
        $subject = Subject::factory()->create(['user_id' => $user->id, 'status' => 'published']);

        $this->actingAs($user)->put("/sujets/{$subject->slug}", [
            'theme' => $subject->theme,
            'category_id' => $subject->category_id,
            'sub_category_id' => $subject->sub_category_id,
            'title' => 'Titre mis à jour',
            'body' => '<p>Nouveau contenu.</p>',
        ]);

        $this->assertDatabaseHas('subject_versions', [
            'subject_id' => $subject->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'title' => 'Titre mis à jour',
        ]);
    }

    public function test_other_user_cannot_edit_document(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $subject = Subject::factory()->create(['user_id' => $owner->id, 'status' => 'published']);

        $response = $this->actingAs($other)->put("/sujets/{$subject->slug}", [
            'theme' => $subject->theme,
            'category_id' => $subject->category_id,
            'sub_category_id' => $subject->sub_category_id,
            'title' => 'Hack',
            'body' => 'Hack',
        ]);

        $response->assertForbidden();
    }

    public function test_citizen_can_comment_subject(): void
    {
        $user = User::factory()->create();
        $subject = Subject::factory()->create(['status' => 'published']);

        $this->actingAs($user)->followingRedirects()->post("/sujets/{$subject->slug}/commentaires", [
            'body' => 'Ma contribution au sujet.',
        ]);

        $this->assertDatabaseHas('subject_comments', [
            'subject_id' => $subject->id,
            'user_id' => $user->id,
            'body' => 'Ma contribution au sujet.',
        ]);
    }

    public function test_subject_listing_displays_theme_and_title(): void
    {
        $subject = Subject::factory()->create(['status' => 'published']);

        $this->actingAs(User::factory()->create())->get('/sujets')
            ->assertOk()
            ->assertSee($subject->title)
            ->assertSee($subject->theme);
    }
}
