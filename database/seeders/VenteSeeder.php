<?php

namespace Database\Seeders;

use App\Models\Vente;
use App\Models\LigneVente;
use App\Models\Vinyle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VenteSeeder extends Seeder
{
    public function run(): void
    {
        // ⚠️ Adapter l'année si besoin (j'ai mis 2025 par défaut)
        $ventesData = [

            // 28/11
            [
                'date'          => '2025-11-28',
                'mode_paiement' => 'carte',
                'lignes'        => [
                    ['nom' => 'Mylène Farmer',          'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Bowie',                  'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Elvis',                  'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Bob Marley',             'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Guns N’ Roses',          'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Renaud',                 'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Nirvana',                'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    // Si dans ta BDD le nom est différent, adapte (ex: 'Red Hot')
                    ['nom' => 'Red Hot Chili Peppers',  'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Daft Punk',              'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                ],
            ],

            // 29/11
            [
                'date'          => '2025-11-29',
                'mode_paiement' => 'carte',
                'lignes'        => [
                    ['nom' => 'Iron Maiden',            'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Billie Eilish',          'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Blues Brothers',         'modele' => '',         'fond' => 'standard', 'quantite' => 1],

                    ['nom' => 'Rammstein',              'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Gorillaz',               'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'AC/DC',                  'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Elvis',                  'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Bob Marley',             'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Michael Jackson',        'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Pink Floyd',             'modele' => 'The Wall', 'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Eminem',                 'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Renaud',                 'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Beatles',                'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'AC/DC',                  'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'BTS',                    'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                ],
            ],

            // 29/11 especes
            [
                'date'          => '2025-11-29',
                'mode_paiement' => 'especes',
                'lignes'        => [
                    ['nom' => 'Beatles',                'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Mylène Farmer',          'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'The Weeknd',             'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Mylène Farmer',          'modele' => '',         'fond' => 'standard', 'quantite' => 1],


                ],
            ],

            // 30/11
            [
                'date'          => '2025-11-30',
                'mode_paiement' => 'carte',
                'lignes'        => [
                    ['nom' => 'Queen',                  'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Prince',                 'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => '2Pac',                   'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Michael Jackson',        'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Harley',                 'modele' => 'Guidon',   'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Bowie',                  'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Mylène Farmer',          'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Bad Bunny',              'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Daft Punk',              'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                ],
            ],

            // 30/11 especes
            [
                'date'          => '2025-11-30',
                'mode_paiement' => 'especes',
                'lignes'        => [
                    ['nom' => 'Snoop Dogg',             'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Johnny Hallyday',        'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'AC/DC',                  'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Guns N’ Roses',          'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                ],
            ],

            // 01/12
            [
                'date'          => '2025-12-01',
                'mode_paiement' => 'carte',
                'lignes'        => [
                    ['nom' => 'Mylène Farmer',          'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Indochine',              'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Jul',                    'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Daft Punk',              'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Gorillaz',               'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Arctic Monkeys',         'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Pink Floyd',             'modele' => 'Dark Side', 'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Nirvana',                'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Pink Floyd',             'modele' => 'Dark Side', 'fond' => 'standard', 'quantite' => 1],
                ],
            ],

            // 01/12 especes
            [
                'date'          => '2025-12-01',
                'mode_paiement' => 'especes',
                'lignes'        => [
                    ['nom' => 'Johnny Hallyday',        'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Rolling Stones',         'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Jul',                    'modele' => '',         'fond' => 'miroir',   'quantite' => 1],                    
                ],
            ],

            // 02/12
            [
                'date'          => '2025-12-02',
                'mode_paiement' => 'carte',
                'lignes'        => [
                    ['nom' => 'Gorillaz',               'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Johnny Hallyday',        'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Nirvana',                'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'AC/DC',                  'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Bowie',                  'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                ],
            ],

            

            // 03/12
            [
                'date'          => '2025-12-03',
                'mode_paiement' => 'carte',
                'lignes'        => [
                    ['nom' => 'NTM',                    'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'IAM',                    'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Gorillaz',               'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Arctic Monkeys',         'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Daft Punk',              'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    // Adapter si tu as 'Wu-Tang Clan' par exemple
                    ['nom' => 'Wu Tang',                'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Eminem',                 'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Linkin Park',            'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Shaka Ponk',             'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'AC/DC',                  'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Rammstein',              'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Billie Eilish',          'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                ],
            ],

            // 04/12
            [
                'date'          => '2025-12-04',
                'mode_paiement' => 'carte',
                'lignes'        => [
                    ['nom' => 'Daft Punk',              'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Johnny Hallyday',        'modele' => '',         'fond' => 'standard', 'quantite' => 1],
                    ['nom' => 'Kiss',                   'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Bowie',                  'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Orelsan',                'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Daft Punk',              'modele' => '',         'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Pink Floyd',             'modele' => 'The Wall', 'fond' => 'miroir',   'quantite' => 1],
                    ['nom' => 'Pink Floyd',             'modele' => 'Dark Side', 'fond' => 'standard', 'quantite' => 1],
                    ['nom' => '2Pac',                   'modele' => '',         'fond' => 'dore',     'quantite' => 1],
                ],
            ],

            
        ];

        // Suppléments de prix selon le fond
        $fondSupplements = [
            'standard' => 0,
            'miroir'   => 8,
            'dore'     => 13,
        ];

        DB::transaction(function () use ($ventesData, $fondSupplements) {

            foreach ($ventesData as $venteData) {

                // Création de la vente, total initial à 0 (on recalcule ensuite)
                $vente = Vente::create([
                    'date'          => $venteData['date'],
                    'mode_paiement' => $venteData['mode_paiement'],
                    'total'         => 0,
                ]);

                $totalVente = 0;

                foreach ($venteData['lignes'] as $ligneData) {

                    // Mapping des noms courts vers les noms d'artistes complets
                    $artisteMapping = [
                        'Mylène Farmer' => 'Mylène Farmer',
                        'Bowie' => 'David Bowie',
                        'Elvis' => 'Elvis',
                        'Bob Marley' => 'Bob Marley',
                        'Guns N\' Roses' => 'Guns N\' Roses',
                        'Renaud' => 'Renaud',
                        'Nirvana' => 'Nirvana',
                        'Red Hot Chili Peppers' => 'Red Hot Chili Peppers',
                        'Daft Punk' => 'Daft Punk',
                        'Iron Maiden' => 'Iron Maiden',
                        'Billie Eilish' => 'Billie Eilish',
                        'Blues Brothers' => 'Blues Brothers',
                        'Rammstein' => 'Rammstein',
                        'Gorillaz' => 'Gorillaz',
                        'AC/DC' => 'AC/DC',
                        'Michael Jackson' => 'Michael Jackson',
                        'Pink Floyd' => 'Pink Floyd',
                        'Eminem' => 'Eminem',
                        'Beatles' => 'The Beatles',
                        'BTS' => 'BTS',
                        'The Weeknd' => 'The Weeknd',
                        'Queen' => 'Queen',
                        'Prince' => 'Prince',
                        '2Pac' => '2Pac',
                        'Harley' => 'Harley',
                        'Bad Bunny' => 'Bad Bunny',
                        'Snoop Dogg' => 'Snoop Dogg',
                        'Johnny Hallyday' => 'Johnny Hallyday',
                        'Indochine' => 'Indochine',
                        'Jul' => 'Jul',
                        'Arctic Monkeys' => 'Arctic Monkeys',
                        'Rolling Stones' => 'The Rolling Stones',
                        'NTM' => 'NTM',
                        'IAM' => 'IAM',
                        'Wu Tang' => 'Wu-Tang Clan',
                        'Linkin Park' => 'Linkin Park',
                        'Shaka Ponk' => 'Shaka Ponk',
                        'Kiss' => 'Kiss',
                        'Orelsan' => 'Orelsan',
                    ];

                    $artisteNom = $artisteMapping[$ligneData['nom']] ?? $ligneData['nom'];
                    
                    // Détermine le modèle selon le fond
                    $modele = match($ligneData['fond']) {
                        'miroir' => 'Miroir Gold',
                        'dore' => 'Doré',
                        default => 'Standard',
                    };
                    
                    $vinyle = Vinyle::where('artiste', 'LIKE', '%' . $artisteNom . '%')
                        ->where('modele', $modele)
                        ->first();
                    
                    if (!$vinyle) {
                        $this->command->warn("Vinyle non trouvé: {$artisteNom} / {$modele}");
                        continue;
                    }

                    $fond     = $ligneData['fond'];              // 'standard', 'miroir', 'dore'
                    $quantite = $ligneData['quantite'] ?? 1;

                    // Prix base (27€ en BDD) + supplément selon le fond
                    $prixUnitaire = $vinyle->prix + ($fondSupplements[$fond] ?? 0);

                    $totalLigne = $prixUnitaire * $quantite;

                    LigneVente::create([
                        'vente_id'      => $vente->id,
                        'vinyle_id'     => $vinyle->id,
                        'quantite'      => $quantite,
                        'prix_unitaire' => $prixUnitaire,
                        'total'         => $totalLigne,
                        'fond'          => $fond,
                    ]);

                    // décrémentation du stock
                    $vinyle->decrement('quantite', $quantite);

                    $totalVente += $totalLigne;
                }

                // Mise à jour du total de la vente
                $vente->update(['total' => $totalVente]);
            }
        });
    }
}
