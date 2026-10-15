<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Subject;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectsTreeLandingUxTest extends TestCase
{
    use RefreshDatabase;

    public function test_connected_user_sees_intuitive_tree_landing()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $category = Category::factory()->create(['name' => 'Patrimoine & Mémoire', 'color' => '#d1b3ff', 'icon' => '🏛️']);
        $sub = SubCategory::factory()->create(['category_id' => $category->id, 'name' => 'Monuments']);

        $published = Subject::factory()->for($user)->create([
            'category_id'     => $category->id,
            'sub_category_id' => $sub->id,
            'status'          => 'published',
            'visibility'      => 'citoyen',
            'title'           => 'Le monument Albert Curvelier',
        ]);

        $draft = Subject::factory()->for($user)->create([
            'category_id'     => $category->id,
            'sub_category_id' => $sub->id,
            'status'          => 'draft',
            'visibility'      => 'citoyen',
            'title'           => 'Brouillon en cours',
        ]);

        $response = $this->actingAs($user)->get(route('subjects.tree.index'));
        $response->assertOk();
        $html = $response->getContent();

        // Filtres sticky
        $response->assertSee('Filtres');
        $response->assertSee('Tous');
        $response->assertSee('Publiés');
        $response->assertSee('Brouillons');

        // Recherche
        $response->assertSee('Rechercher un sujet');

        // Légende des statuts
        $response->assertSee('Publié');
        $response->assertSee('Brouillon');

        // Catégorie visible avec compteur
        $this->assertStringContainsString('Patrimoine & Mémoire', $html);
        $response->assertSee('2 sujets');

        // Sous-catégorie
        $response->assertSee('Monuments');

        // Titre des sujets
        $response->assertSee('Le monument Albert Curvelier');
        $response->assertSee('Brouillon en cours');

        // Métas
        $response->assertSee('doc');
    }
}
