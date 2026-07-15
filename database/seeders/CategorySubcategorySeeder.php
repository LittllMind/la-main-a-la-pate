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
                'name'           => 'Conseil municipal & Gouvernance',
                'slug'           => 'conseil-municipal-gouvernance',
                'color'          => '#93c5fd',
                'icon'           => '🏛️',
                'sub_categories' => [
                    ['name' => 'Conseils municipaux',                'color' => '#bfdbfe'],
                    ['name' => 'Élections & délibérations',       'color' => '#dbeafe'],
                    ['name' => 'Budget communal',                 'color' => '#eff6ff'],
                    ['name' => 'Droit de pétition & participation', 'color' => '#e0f2fe'],
                ],
            ],
            [
                'name'           => 'Patrimoine & Mémoire',
                'slug'           => 'patrimoine-memoire',
                'color'          => '#fca5a5',
                'icon'           => '🏡',
                'sub_categories' => [
                    ['name' => 'Monuments & lieux de mémoire', 'color' => '#fecaca'],
                    ['name' => 'Archives & généalogie',        'color' => '#fee2e2'],
                    ['name' => 'Personnages historiques',       'color' => '#fef2f2'],
                ],
            ],
            [
                'name'           => 'Éducation & Jeunesse',
                'slug'           => 'education-jeunesse',
                'color'          => '#86efac',
                'icon'           => '🏫',
                'sub_categories' => [
                    ['name' => 'Écoles & cantines',       'color' => '#bbf7d0'],
                    ['name' => 'Accueil périscolaire',   'color' => '#dcfce7'],
                    ['name' => 'Santé & protection enfance', 'color' => '#f0fdf4'],
                ],
            ],
            [
                'name'           => 'Infrastructures & Travaux',
                'slug'           => 'infrastructures-travaux',
                'color'          => '#fcd34d',
                'icon'           => '🏗️',
                'sub_categories' => [
                    ['name' => 'Ponts & voirie',         'color' => '#fde68a'],
                    ['name' => 'Bâtiments communaux',     'color' => '#fef3c7'],
                    ['name' => 'Eau, énergie & réseaux', 'color' => '#fffbeb'],
                ],
            ],
            [
                'name'           => 'Environnement & Cadre de vie',
                'slug'           => 'environnement-cadre-de-vie',
                'color'          => '#6ee7b7',
                'icon'           => '🌿',
                'sub_categories' => [
                    ['name' => 'Camping & espaces verts', 'color' => '#a7f3d0'],
                    ['name' => 'Eau, énergie & transition', 'color' => '#d1fae5'],
                    ['name' => 'Voirie, stationnement et espaces verts', 'color' => '#ecfdf5'],
                ],
            ],
            [
                'name'           => 'Vie du village & Actualités',
                'slug'           => 'vie-du-village-actualites',
                'color'          => '#c4b5fd',
                'icon'           => '📰',
                'sub_categories' => [
                    ['name' => 'Actualités',              'color' => '#ddd6fe'],
                    ['name' => 'Événements & fêtes',     'color' => '#ede9fe'],
                    ['name' => 'Séraphothèque',          'color' => '#f5f3ff'],
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
