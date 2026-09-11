<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\GuinguetteDocumentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuinguetteTaxonomyGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_integration_rejects_guinguette_without_valid_subcategory(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create([
            'id' => 6,
            'name' => 'Patrimoine & Mémoire',
            'slug' => 'patrimoine-memoire',
        ]);

        Subject::create([
            'user_id' => $admin->id,
            'theme' => $category->name,
            'category_id' => $category->id,
            'sub_category_id' => null,
            'title' => 'La Guinguette / La Baraquita',
            'slug' => 'guinguette-urbanisme',
            'body' => 'Contenu de test',
            'status' => 'draft',
            'citizen_status' => 'draft',
            'public_status' => 'draft',
            'public_is_listed' => false,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Taxonomie invalide pour guinguette-urbanisme');

        (new GuinguetteDocumentSeeder())->run();
    }
}
