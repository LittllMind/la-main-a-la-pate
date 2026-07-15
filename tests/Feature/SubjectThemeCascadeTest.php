<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectThemeCascadeTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_lists_categories_and_exposes_subcategories_json(): void
    {
        $user = User::factory()->create(['role' => 'citoyen']);
        $category = Category::factory()->create();
        $subCategories = SubCategory::factory()->count(3)->create([
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)->get(route('subjects.create'));

        $response->assertOk();

        // Les categories sont presentes dans les options du select
        $response->assertSee('value="' . $category->id . '"', false);
        $response->assertSee($category->name);

        // Le JSON non echappe pour le JS cascade doit contenir les sous-categories liees
        $json = preg_replace("/^.*const categories = (.+?);.*$/s", '$1', $response->getContent());
        $this->assertStringContainsString('"id":' . $category->id, $json);
        foreach ($subCategories as $sub) {
            $this->assertStringContainsString('"id":' . $sub->id, $json);
            $this->assertStringContainsString('"name":"' . $sub->name . '"', $json);
        }
    }

    public function test_user_can_create_subject_with_category_and_subcategory(): void
    {
        $user = User::factory()->create(['role' => 'citoyen']);
        $category = Category::factory()->create();
        $subCategory = SubCategory::factory()->create([
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)->post(route('subjects.store'), [
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'title' => 'Test cascade thematique',
            'body' => '# Contenu\n\nun paragraphe.',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('subjects', [
            'title' => 'Test cascade thematique',
            'user_id' => $user->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'theme' => $category->name,
            'status' => 'draft',
        ]);
    }

    public function test_subcategory_must_belong_to_selected_category(): void
    {
        $user = User::factory()->create(['role' => 'citoyen']);
        $category = Category::factory()->create();
        $otherCategory = Category::factory()->create();
        $subCategory = SubCategory::factory()->create([
            'category_id' => $otherCategory->id,
        ]);

        $response = $this->actingAs($user)->post(route('subjects.store'), [
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'title' => 'Sujet invalide',
            'body' => 'corps',
        ]);

        $response->assertSessionHasErrors(['sub_category_id']);
    }

    public function test_old_subcategory_is_preserved_after_validation_error(): void
    {
        $user = User::factory()->create(['role' => 'citoyen']);
        $category = Category::factory()->create();
        $subCategory = SubCategory::factory()->create([
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)
            ->from(route('subjects.create'))
            ->post(route('subjects.store'), [
                'category_id' => $category->id,
                'sub_category_id' => $subCategory->id,
                'title' => '',
                'body' => '',
            ]);

        $response->assertRedirect(route('subjects.create'));
        $response->assertSessionHasInput(['category_id' => $category->id, 'sub_category_id' => $subCategory->id]);
    }
}
