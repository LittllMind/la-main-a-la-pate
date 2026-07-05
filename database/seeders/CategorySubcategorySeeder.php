<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Support\Str;

class CategorySubcategorySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'name'         => 'Conseil municipal & Gouvernance',
                'slug'         => 'conseil-municipal-gouvernance',
                'color'        => '#93c5fd', // pastel bleu
                'icon'         => '🏛️',
                'sub_categories' => [
                    ['name' => 'Conseils municipaux',        'color' => '#bfdbfe'],
                    ['name' => 'Élections & délibérations',   'color' => '#dbeafe'],
                ],
            ],
            [
                'name'         => 'Patrimoine & Mémoire',
                'slug'         => 'patrimoine-memoire',
                'color'        => '#fca5a5', // pastel rouge
                'icon'         => '🏡',
                'sub_categories' => [
                    ['name' => 'Monuments & lieux de mémoire', 'color' => '#fecaca'],
                    ['name' => 'Archives & généalogie',        'color' => '#fee2e2'],
                ],
            ],
            [
                'name'         => 'Éducation & Jeunesse',
                'slug'         => 'education-jeunesse',
                'color'        => '#86efac', // pastel vert
                'icon'         => '🏫',
                'sub_categories' => [
                    ['name' => 'Écoles & cantines',     'color' => '#bbf7d0'],
                    ['name' => 'Accueil périscolaire',   'color' => '#dcfce7'],
                ],
            ],
            [
                'name'         => 'Infrastructures & Travaux',
                'slug'         => 'infrastructures-travaux',
                'color'        => '#fcd34d', // pastel ambre
                'icon'         => '🏗️',
                'sub_categories' => [
                    ['name' => 'Ponts & voirie',      'color' => '#fde68a'],
                    ['name' => 'Bâtiments communaux', 'color' => '#fef3c7'],
                ],
            ],
            [
                'name'         => 'Environnement & Cadre de vie',
                'slug'         => 'environnement-cadre-de-vie',
                'color'        => '#6ee7b7', // pastel émeraude
                'icon'         => '🌿',
                'sub_categories' => [
                    ['name' => 'Camping & espaces verts', 'color' => '#a7f3d0'],
                    ['name' => 'Eau & énergie',           'color' => '#d1fae5'],
                ],
            ],
        ];

        foreach ($data as $cat) {
            $category = Category::create([
                'name'          => $cat['name'],
                'slug'          => $cat['slug'],
                'color'         => $cat['color'],
                'icon'          => $cat['icon'],
                'display_order' => 0,
            ]);

            foreach ($cat['sub_categories'] as $sub) {
                SubCategory::create([
                    'category_id' => $category->id,
                    'name'        => $sub['name'],
                    'slug'        => Str::slug($sub['name']),
                    'color'       => $sub['color'],
                ]);
            }
        }
    }
}
