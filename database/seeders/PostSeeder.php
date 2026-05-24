<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Post;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $posts = [
            [
                'title' => 'Marché du Rozier ce dimanche',
                'slug' => 'marche-du-rozier-ce-dimanche',
                'category_id' => 1,
                'excerpt' => 'Rendez-vous ce dimanche pour le marché traditionnel de notre commune.',
                'content' => "Le marché du Rozier se tiendra ce dimanche de 8h à 13h sur la place de la mairie.\n\nAu programme : produits locaux, artisans du coin, et animations musicales. Venez nombreux pour soutenir nos commerçants et passer un bon moment entre voisins.",
                'status' => 'published',
                'published_at' => now()->subDays(2),
                'user_id' => 1,
            ],
            [
                'title' => 'Nouvelle déviation forestière',
                'slug' => 'nouvelle-derrivation-forestiere',
                'category_id' => 2,
                'excerpt' => 'La Route départementale sera fermée les nuits prochaines pour travaux.',
                'content' => "La RD 986 sera fermée à la circulation de nuit du lundi au vendredi pour des travaux de déviation forestière.\n\nUne déviation temporaire est mise en place via la route des Cévennes. Merci de prévoir vos déplacements en conséquence.",
                'status' => 'published',
                'published_at' => now()->subDays(5),
                'user_id' => 1,
            ],
            [
                'title' => 'La chapelle Saint-Étienne rénovée',
                'slug' => 'la-chapelle-saint-etienne-renovee',
                'category_id' => 3,
                'excerpt' => 'Après deux ans de travaux, la chapelle a retrouvé son éclat.',
                'content' => "C'est avec une grande fierté que nous inaugurons ce samedi la chapelle Saint-Étienne, entièrement rénovée grâce à la mobilisation des habitants et aux fonds collectés lors des soirées brocante.\n\nUne messe sera célébrée à 10h, suivie d'un pot offert par la municipalité.",
                'status' => 'published',
                'published_at' => now()->subDays(8),
                'user_id' => 1,
            ],
            [
                'title' => 'Alerte sécheresse : restrictions d\'eau',
                'slug' => 'alerte-secheresse-restrictions-eau',
                'category_id' => 4,
                'excerpt' => 'Le préfet a déclenché le niveau 3 de sécheresse pour notre secteur.',
                'content' => "Le niveau 3 de sécheresse est désormais activé sur le bassin versant du Rozier.\n\nRestrictions : arrosage des jardins interdit de 8h à 20h, lavage de voitures interdit, remplissage des piscines interdit. Merci de respecter ces mesures pour préserver nos ressources.",
                'status' => 'published',
                'published_at' => now()->subDays(1),
                'user_id' => 1,
            ],
            [
                'title' => 'Réunion du conseil municipal le 15 juin',
                'slug' => 'reunion-conseil-municipal-15-juin-v2',
                'category_id' => 1,
                'excerpt' => 'Ordre du jour : budget, urbanisme et entretien communal.',
                'content' => "La prochaine réunion du conseil municipal se tiendra le 15 juin à 20h à la salle des fêtes.\n\nL'ordre du jour comprend : vote du budget supplémentaire, présentation du plan d'urbanisme intercommunal, et point sur les entretiens des chemins ruraux.",
                'status' => 'draft',
                'published_at' => null,
                'user_id' => 1,
            ],
        ];

        foreach ($posts as $post) {
            Post::create($post);
        }
    }
}
