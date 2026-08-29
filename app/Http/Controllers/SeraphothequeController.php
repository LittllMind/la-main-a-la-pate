<?php

namespace App\Http\Controllers;

class SeraphothequeController extends Controller
{
    public function index()
    {
        return view('seraphotheque.index', [
            'hero' => $this->hero(),
            'univers' => $this->univers(),
            'values' => $this->values(),
            'artisanat' => $this->artisanat(),
            'favorites' => $this->favorites(),
            'about' => $this->about(),
            'civic' => $this->civic(),
            'social' => $this->social(),
            'practical' => $this->practical(),
        ]);
    }

    private function hero(): array
    {
        return [
            'title' => 'LA SÉRAPHOTHÈQUE',
            'subtitle' => 'Le Rozier · depuis 2022',
            'intro' => 'Friperie, brocante, bouquinerie, espace enfant et créations dans un même lieu vivant.',
            'location' => 'Au cœur du village du Rozier, en Lozère, au confluent des gorges du Tarn et de la Jonte.',
            'address' => '2 rue Louis Armand',
            'zip_city' => '48150 Le Rozier',
            'hours' => 'Ouvert à l\'année',
            'image' => [
                'desktop' => asset('images/seraphotheque/v1/hero-1920.webp'),
                'mobile' => asset('images/seraphotheque/v1/hero-1200.webp'),
                'alt' => 'Intérieur de la Séraphothèque : livres, bijoux, appareils, objets et espace enfant.',
            ],
        ];
    }

    private function univers(): array
    {
        return [
            'title' => 'Découvrez la Séraphothèque',
            'lead' => 'Quatre univers à parcourir librement, dans une boutique où les arrivages, trouvailles et objets changent en permanence.',
            'items' => [
                [
                    'id' => 'friperie',
                    'name' => 'FRIPERIE',
                    'title' => 'Des vêtements qui ont encore beaucoup à vivre.',
                    'text' => [
                        'Pièces pour adultes et enfants, accessoires et trouvailles choisies au fil des arrivages.',
                        'L’idée : prolonger l’usage, mélanger les styles et rendre la seconde main désirable.',
                    ],
                    'signature' => 'Seconde main · pièces singulières · petits prix',
                    'images' => [
                        ['src' => asset('images/seraphotheque/v1/friperie-6549-800.webp'), 'alt' => 'Friperie Séraphothèque : vêtements et accessoires de seconde main.', 'width' => 800, 'height' => 600],
                        ['src' => asset('images/seraphotheque/v1/friperie-7525-400.webp'), 'alt' => 'Détail de la friperie : pièces singulières.', 'width' => 400, 'height' => 300],
                        ['src' => asset('images/seraphotheque/v1/friperie-6958-400.webp'), 'alt' => 'Petits prix et trouvailles dans la friperie.', 'width' => 400, 'height' => 300],
                    ],
                ],
                [
                    'id' => 'brocante',
                    'name' => 'BROCANTE',
                    'title' => 'Chiner, fouiller, trouver.',
                    'text' => [
                        'Objets de seconde main, vaisselle ancienne, déco, outils, curiosités et trouvailles.',
                    ],
                    'signature' => 'Objets divers · vaisselle · curiosités',
                    'layout' => 'asymmetric',
                    'images' => [
                        ['src' => asset('images/seraphotheque/v1/brocante-7908-600.webp'), 'alt' => 'Brocante : objets anciens et curiosités.', 'width' => 600, 'height' => 450],
                        ['src' => asset('images/seraphotheque/v1/brocante-6439-400.webp'), 'alt' => 'Vaisselle et déco de seconde main.', 'width' => 400, 'height' => 300],
                        ['src' => asset('images/seraphotheque/v1/brocante-7562-400.webp'), 'alt' => 'Détail de la brocante.', 'width' => 400, 'height' => 300],
                        ['src' => asset('images/seraphotheque/v1/brocante-7003-400.webp'), 'alt' => 'Trouvailles et objets divers.', 'width' => 400, 'height' => 300],
                    ],
                ],
                [
                    'id' => 'bouquinerie',
                    'name' => 'BOUQUINERIE',
                    'title' => 'Des livres à feuilleter, à emporter, à offrir.',
                    'text' => [
                        'Romans, bandes dessinées, albums, revues, beaux livres et curiosités.',
                        'Des livres à parcourir sur place, à emporter, à offrir ou à redécouvrir.',
                    ],
                    'signature' => 'Romans · BD · albums · revues',
                    'images' => [
                        ['src' => asset('images/seraphotheque/v1/bouquinerie-7692-1200.webp'), 'alt' => 'Bouquinerie : rayons de livres.', 'width' => 1200, 'height' => 900],
                        ['src' => asset('images/seraphotheque/v1/bouquinerie-7877-600.webp'), 'alt' => 'Livres et beaux albums.', 'width' => 600, 'height' => 450],
                        ['src' => asset('images/seraphotheque/v1/bouquinerie-6939-600.webp'), 'alt' => 'Curiosités littéraires.', 'width' => 600, 'height' => 450],
                    ],
                ],
                [
                    'id' => 'enfant',
                    'name' => 'ESPACE ENFANT',
                    'title' => 'Un espace pensé pour les enfants et les familles.',
                    'text' => [
                        'Jeux, livres, jouets et objets de seconde main, dans un espace pensé pour les enfants et les familles.',
                    ],
                    'signature' => 'Jouets · livres · jeux · seconde main',
                    'images' => [
                        ['src' => asset('images/seraphotheque/v1/enfant-7611-800.webp'), 'alt' => 'Espace enfant : étagères avec jeux, livres et jouets.', 'width' => 800, 'height' => 600],
                        ['src' => asset('images/seraphotheque/v1/enfant-6230-400.webp'), 'alt' => 'Espace enfant : figurines et livres.', 'width' => 400, 'height' => 300],
                        ['src' => asset('images/seraphotheque/v1/enfant-7043-400.webp'), 'alt' => 'Jeux et objets pour enfants.', 'width' => 400, 'height' => 300],
                    ],
                ],
            ],
        ];
    }

    private function values(): array
    {
        return [
            'title' => 'Nos engagements',
            'items' => [
                ['name' => 'RÉEMPLOI', 'text' => 'Nous récupérons, trions et valorisons des objets du quotidien.'],
                ['name' => 'CRÉATIONS LOCALES', 'text' => 'Artisanat, savoir-faire et créations d’ici mises à l’honneur.'],
                ['name' => 'PROXIMITÉ', 'text' => 'Un commerce de village, ouvert à l’année.'],
                ['name' => 'ÉCOLOGIQUE', 'text' => 'Moins d’achat neuf, plus de seconde vie, moins de gaspillage.'],
            ],
        ];
    }

    private function artisanat(): array
    {
        return [
            'title' => 'Artisanat & créations',
            'text' => 'La Séraphothèque accueille aussi des créations, objets artisanaux et petites séries, avec une place particulière donnée aux savoir-faire locaux et aux pièces singulières.',
            'images' => [
                ['src' => asset('images/seraphotheque/v1/artisanat-01-600.webp'), 'alt' => 'Créations et objets artisanaux.', 'width' => 600, 'height' => 450],
                ['src' => asset('images/seraphotheque/v1/artisanat-02-600.webp'), 'alt' => 'Petites séries et savoir-faire local.', 'width' => 600, 'height' => 450],
            ],
        ];
    }

    private function favorites(): array
    {
        return [
            'title' => 'Coups de cœur du moment',
            'items' => [
                [
                    'image' => asset('images/seraphotheque/v1/favori-01-600.webp'),
                    'alt' => 'Coup de cœur : sélection du moment.',
                    'title' => 'Sélection de curiosités',
                    'description' => 'Trouvailles choisies cette semaine dans la boutique.',
                ],
                [
                    'image' => asset('images/seraphotheque/v1/favori-02-600.webp'),
                    'alt' => 'Coup de cœur : livres et albums.',
                    'title' => 'Livres et albums',
                    'description' => 'Nouveaux arrivages à découvrir en bouquinerie.',
                ],
                [
                    'image' => asset('images/seraphotheque/v1/favori-03-600.webp'),
                    'alt' => 'Coup de cœur : espace enfant.',
                    'title' => 'Espace enfant',
                    'description' => 'Jeux et livres pour les familles du village.',
                ],
            ],
        ];
    }

    private function about(): array
    {
        return [
            'title' => 'Depuis 2022',
            'text' => [
                'Installée dans l’ancienne école du Rozier, la Séraphothèque s’est construite au fil des années comme un commerce de seconde main, mais aussi comme un lieu de rencontre ouvert toute l’année.',
                'Friperie, brocante, bouquinerie, espace enfant, objets récupérés et créations locales s’y côtoient dans un même lieu, animé par l’envie de transmettre, de partager et de faire vivre le village.',
            ],
            'image' => [
                'src' => asset('images/seraphotheque/v1/depuis-2022-1200.webp'),
                'alt' => 'Intérieur chaleureux de la Séraphothèque.',
                'width' => 1200,
                'height' => 900,
            ],
        ];
    }

    private function civic(): array
    {
        return [
            'title' => 'La situation du local en 2026',
            'text' => 'L’avenir du local est aujourd’hui incertain. Nous avons rassemblé les échanges, démarches et documents disponibles dans un dossier public permettant à chacun de comprendre la situation.',
            'cta_primary' => [
                'label' => 'Comprendre la situation',
                'url' => route('subjects.show', 'seraphotheque-situation-2026'),
            ],
            'cta_secondary' => [
                'label' => 'Signer la pétition',
                'url' => 'https://www.change.org/p/pour-le-maintien-de-la-s%C3%A9raphoth%C3%A8que-au-c%C5%93ur-du-rozier-48150',
            ],
        ];
    }

    private function social(): array
    {
        return [
            'instagram' => [
                'label' => 'Instagram',
                'url' => 'https://www.instagram.com/seraphotheque/',
                'handle' => '@seraphotheque',
            ],
            'facebook' => [
                'label' => 'Facebook',
                'url' => 'https://www.facebook.com/seraphotheque/',
                'handle' => 'La Séraphothèque | Le Rozier',
            ],
        ];
    }

    private function practical(): array
    {
        return [
            'title' => 'Infos pratiques',
            'address' => '2 rue Louis Armand',
            'zip_city' => '48150 Le Rozier',
            'hours' => 'Ouvert à l\'année',
        ];
    }
}
