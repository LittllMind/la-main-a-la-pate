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
                'color'        => '#1e3a8a',
                'icon'         => '🏛️',
                'sub_categories' => [
                    ['name' => 'Conseils municipaux',        'color' => '#3b82f6'],
                    ['name' => 'Élections & délibérations',   'color' => '#60a5fa'],
                ],
            ],
            [
                'name'         => 'Patrimoine & Mémoire',
                'slug'         => 'patrimoine-memoire',
                'color'        => '#7f1d1d',
                'icon'         => '🏡',
                'sub_categories' => [
                    ['name' => 'Monuments & lieux de mémoire', 'color' => '#dc2626'],
                    ['name' => 'Archives & généalogie',        'color' => '#f87171'],
                ],
            ],
            [
                'name'         => 'Éducation & Jeunesse',
                'slug'         => 'education-jeunesse',
                'color'        => '#064e3b',
                'icon'         => '🏫',
                'sub_categories' => [
                    ['name' => 'Écoles & cantines',     'color' => '#10b981'],
                    ['name' => 'Accueil périscolaire',   'color' => '#34d399'],
                ],
            ],
            [
                'name'         => 'Infrastructures & Travaux',
                'slug'         => 'infrastructures-travaux',
                'color'        => '#92400e',
                'icon'         => '🏗️',
                'sub_categories' => [
                    ['name' => 'Ponts & voirie',      'color' => '#f59e0b'],
                    ['name' => 'Bâtiments communaux', 'color' => '#fbbf24'],
                ],
            ],
            [
                'name'         => 'Environnement & Cadre de vie',
                'slug'         => 'environnement-cadre-de-vie',
                'color'        => '#14532d',
                'icon'         => '🌿',
                'sub_categories' => [
                    ['name' => 'Camping & espaces verts', 'color' => '#22c55e'],
                    ['name' => 'Eau & énergie',           'color' => '#4ade80'],
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
