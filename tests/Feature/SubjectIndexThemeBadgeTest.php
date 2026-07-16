<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectIndexThemeBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_index_displays_subcategory_badge_with_color(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $category = Category::factory()->create([
            'name' => 'Patrimoine',
            'slug' => 'patrimoine',
            'color' => '#7f1d1d',
        ]);

        $subCategory = SubCategory::factory()->create([
            'category_id' => $category->id,
            'name' => 'Monuments',
            'slug' => 'monuments',
            'color' => '#dc2626',
        ]);

        $subject = Subject::factory()->create([
            'user_id' => $admin->id,
            'theme' => 'Patrimoine',
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'status' => 'published',
            'visibility' => 'citoyen',
        ]);

        $response = $this->actingAs($admin)->get(route('subjects.index'));

        $response->assertOk();
        $response->assertSee('Monuments', false);
        // Assert le style inline background-color contient la couleur hex du sous-thème
        $response->assertSee('background-color: ' . $subCategory->color, false);
    }
}
