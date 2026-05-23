<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Space;

class SpaceSeeder extends Seeder
{
    public function run(): void
    {
        $spaces = [
            [
                'name' => 'Vie du village',
                'slug' => 'vie-du-village',
                'description' => 'Actualites, annonces et vie quotidienne au Rozier.', 
                'icon' => '🏘️',
                'display_order' => 1,
            ],
            [
                'name' => 'Memoire & Patrimoine',
                'slug' => 'memoire-et-patrimoine',
                'description' => 'Histoire locale, photos anciennes, traditions et temoignages.',
                'icon' => '📜',
                'display_order' => 2,
            ],
            [
                'name' => 'Nature & Environnement',
                'slug' => 'nature-et-environnement',
                'description' => 'Espaces verts, ecologie, randonnees et faune locale.',
                'icon' => '🌿',
                'display_order' => 3,
            ],
            [
                'name' => 'Urbanisme & Projets',
                'slug' => 'urbanisme-et-projets',
                'description' => 'Travaux, amenagements et evolution du bourg.',
                'icon' => '🏗️',
                'display_order' => 4,
            ],
            [
                'name' => 'Brocante & Entraide',
                'slug' => 'brocante-et-entraide',
                'description' => 'Petites annonces, dons et services entre voisins.',
                'icon' => '🤝',
                'display_order' => 5,
            ],
        ];
        foreach ($spaces as $space) {
            Space::create($space);
        }
    }
}
