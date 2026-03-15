<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Vinyle;

class VinyleSeeder extends Seeder
{
    /**
     * Structure : REF | ARTISTE | MODELE | GENRE
     * REF format : TYPE-### (MIR = Miroir Gold, DOR = Doré, STD = Standard)
     * 
     * PRIX : Tous les vinyles à 27€ de base
     * Le fond (miroir/doré) est choisi à l'achat et ajoute un supplément
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Vinyle::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $vinyles = [
            // ═══════════════════════════════════════════════════════════════
            // MIROIR GOLD (prix de base 27€, +8€ si fond miroir choisi)
            // ═══════════════════════════════════════════════════════════════
            ['reference' => 'MIR-001', 'artiste' => 'David Bowie', 'modele' => 'Miroir Gold', 'genre' => 'Rock',    'style' => 'Glam Rock',      'prix' => 27, 'quantite' => 20],
            ['reference' => 'MIR-002', 'artiste' => 'Daft Punk',   'modele' => 'Miroir Gold', 'genre' => 'Électro', 'style' => 'French Touch',   'prix' => 27, 'quantite' => 40],
            ['reference' => 'MIR-003', 'artiste' => 'AC/DC',       'modele' => 'Miroir Gold', 'genre' => 'Rock',    'style' => 'Hard Rock',      'prix' => 27, 'quantite' => 36],
            ['reference' => 'MIR-004', 'artiste' => 'Queen',       'modele' => 'Miroir Gold', 'genre' => 'Rock',    'style' => 'Classic Rock',   'prix' => 27, 'quantite' => 28],
            ['reference' => 'MIR-005', 'artiste' => 'Pink Floyd',  'modele' => 'Miroir Gold', 'genre' => 'Rock',    'style' => 'Progressif',     'prix' => 27, 'quantite' => 19],
            ['reference' => 'MIR-006', 'artiste' => 'Metallica',   'modele' => 'Miroir Gold', 'genre' => 'Metal',   'style' => 'Thrash',         'prix' => 27, 'quantite' => 9],
            ['reference' => 'MIR-007', 'artiste' => 'Bob Marley',  'modele' => 'Miroir Gold', 'genre' => 'Reggae',  'style' => 'Roots',          'prix' => 27, 'quantite' => 28],
            ['reference' => 'MIR-008', 'artiste' => 'Nirvana',     'modele' => 'Miroir Gold', 'genre' => 'Grunge',  'style' => 'Alternative',    'prix' => 27, 'quantite' => 9],
            ['reference' => 'MIR-009', 'artiste' => 'Eminem',      'modele' => 'Miroir Gold', 'genre' => 'Rap',     'style' => 'Hip-Hop US',     'prix' => 27, 'quantite' => 30],
            ['reference' => 'MIR-010', 'artiste' => 'Gorillaz',    'modele' => 'Miroir Gold', 'genre' => 'Électro', 'style' => 'Trip-Hop',       'prix' => 27, 'quantite' => 19],
            ['reference' => 'MIR-011', 'artiste' => 'Mylène Farmer', 'modele' => 'Miroir Gold', 'genre' => 'Pop', 'style' => 'Chanson Française', 'prix' => 27, 'quantite' => 15],
            ['reference' => 'MIR-012', 'artiste' => 'Renaud', 'modele' => 'Miroir Gold', 'genre' => 'Chanson', 'style' => 'Chanson Française', 'prix' => 27, 'quantite' => 12],
            ['reference' => 'MIR-013', 'artiste' => 'Guns N\' Roses', 'modele' => 'Miroir Gold', 'genre' => 'Rock', 'style' => 'Hard Rock', 'prix' => 27, 'quantite' => 10],
            ['reference' => 'MIR-014', 'artiste' => 'Red Hot Chili Peppers', 'modele' => 'Miroir Gold', 'genre' => 'Rock', 'style' => 'Funk Rock', 'prix' => 27, 'quantite' => 10],
            ['reference' => 'MIR-015', 'artiste' => 'Iron Maiden', 'modele' => 'Miroir Gold', 'genre' => 'Metal', 'style' => 'Heavy Metal', 'prix' => 27, 'quantite' => 8],
            ['reference' => 'MIR-016', 'artiste' => 'Rammstein', 'modele' => 'Miroir Gold', 'genre' => 'Metal', 'style' => 'Industrial Metal', 'prix' => 27, 'quantite' => 10],
            ['reference' => 'MIR-017', 'artiste' => 'Prince', 'modele' => 'Miroir Gold', 'genre' => 'Pop', 'style' => 'Funk Rock', 'prix' => 27, 'quantite' => 10],
            ['reference' => 'MIR-018', 'artiste' => '2Pac', 'modele' => 'Miroir Gold', 'genre' => 'Rap', 'style' => 'West Coast Rap', 'prix' => 27, 'quantite' => 10],
            ['reference' => 'MIR-019', 'artiste' => 'Johnny Hallyday', 'modele' => 'Miroir Gold', 'genre' => 'Rock', 'style' => 'Rock Français', 'prix' => 27, 'quantite' => 15],
            ['reference' => 'MIR-020', 'artiste' => 'Indochine', 'modele' => 'Miroir Gold', 'genre' => 'Rock', 'style' => 'Rock Français', 'prix' => 27, 'quantite' => 12],
            ['reference' => 'MIR-021', 'artiste' => 'NTM', 'modele' => 'Miroir Gold', 'genre' => 'Rap', 'style' => 'Rap Français', 'prix' => 27, 'quantite' => 8],
            ['reference' => 'MIR-022', 'artiste' => 'IAM', 'modele' => 'Miroir Gold', 'genre' => 'Rap', 'style' => 'Rap Français', 'prix' => 27, 'quantite' => 8],
            ['reference' => 'MIR-023', 'artiste' => 'Wu-Tang Clan', 'modele' => 'Miroir Gold', 'genre' => 'Rap', 'style' => 'East Coast Rap', 'prix' => 27, 'quantite' => 8],
            ['reference' => 'MIR-024', 'artiste' => 'Linkin Park', 'modele' => 'Miroir Gold', 'genre' => 'Rock', 'style' => 'Nu Metal', 'prix' => 27, 'quantite' => 10],
            ['reference' => 'MIR-025', 'artiste' => 'Shaka Ponk', 'modele' => 'Miroir Gold', 'genre' => 'Rock', 'style' => 'Electro Rock', 'prix' => 27, 'quantite' => 10],
            ['reference' => 'MIR-026', 'artiste' => 'Kiss', 'modele' => 'Miroir Gold', 'genre' => 'Rock', 'style' => 'Hard Rock', 'prix' => 27, 'quantite' => 8],
            ['reference' => 'MIR-027', 'artiste' => 'Arctic Monkeys', 'modele' => 'Miroir Gold', 'genre' => 'Rock', 'style' => 'Indie Rock', 'prix' => 27, 'quantite' => 8],
            ['reference' => 'MIR-028', 'artiste' => 'Snoop Dogg', 'modele' => 'Miroir Gold', 'genre' => 'Rap', 'style' => 'West Coast Rap', 'prix' => 27, 'quantite' => 10],
            
            // ═══════════════════════════════════════════════════════════════
            // DORÉ (prix de base 27€, +13€ si fond doré choisi)
            // ═══════════════════════════════════════════════════════════════
            ['reference' => 'DOR-001', 'artiste' => 'The Beatles', 'modele' => 'Doré', 'genre' => 'Rock',     'style' => 'British Invasion', 'prix' => 27, 'quantite' => 3],
            ['reference' => 'DOR-002', 'artiste' => 'Elvis', 'modele' => 'Doré', 'genre' => 'Rock',     'style' => 'Rockabilly',       'prix' => 27, 'quantite' => 10],
            ['reference' => 'DOR-003', 'artiste' => 'The Rolling Stones', 'modele' => 'Doré', 'genre' => 'Rock',     'style' => 'Classic Rock',     'prix' => 27, 'quantite' => 20],
            ['reference' => 'DOR-004', 'artiste' => 'Led Zeppelin', 'modele' => 'Doré', 'genre' => 'Rock',     'style' => 'Hard Rock',        'prix' => 27, 'quantite' => 10],
            ['reference' => 'DOR-005', 'artiste' => 'Michael Jackson', 'modele' => 'Doré', 'genre' => 'Pop',      'style' => 'R&B/Soul',         'prix' => 27, 'quantite' => 10],
            ['reference' => 'DOR-006', 'artiste' => 'Miles Davis', 'modele' => 'Doré', 'genre' => 'Jazz',     'style' => 'Bebop',            'prix' => 27, 'quantite' => 8],
            ['reference' => 'DOR-007', 'artiste' => 'Johnny Cash', 'modele' => 'Doré', 'genre' => 'Country',  'style' => 'Folk',             'prix' => 27, 'quantite' => 7],
            ['reference' => 'DOR-008', 'artiste' => 'ABBA', 'modele' => 'Doré', 'genre' => 'Pop',      'style' => 'Disco',            'prix' => 27, 'quantite' => 6],
            ['reference' => 'DOR-009', 'artiste' => 'Ravi Shankar', 'modele' => 'Doré', 'genre' => 'World',    'style' => 'Classique Indien', 'prix' => 27, 'quantite' => 4],
            ['reference' => 'DOR-010', 'artiste' => 'Kraftwerk', 'modele' => 'Doré', 'genre' => 'Électro',  'style' => 'Krautrock', 'prix' => 27, 'quantite' => 5],
            ['reference' => 'DOR-011', 'artiste' => 'Mylène Farmer', 'modele' => 'Doré', 'genre' => 'Pop', 'style' => 'Chanson Française', 'prix' => 27, 'quantite' => 8],
            ['reference' => 'DOR-012', 'artiste' => 'Billie Eilish', 'modele' => 'Doré', 'genre' => 'Pop', 'style' => 'Electropop', 'prix' => 27, 'quantite' => 12],
            ['reference' => 'DOR-013', 'artiste' => 'BTS', 'modele' => 'Doré', 'genre' => 'K-Pop', 'style' => 'Pop', 'prix' => 27, 'quantite' => 15],
            ['reference' => 'DOR-014', 'artiste' => 'The Weeknd', 'modele' => 'Doré', 'genre' => 'R&B', 'style' => 'Pop', 'prix' => 27, 'quantite' => 10],
            ['reference' => 'DOR-015', 'artiste' => 'Bad Bunny', 'modele' => 'Doré', 'genre' => 'Reggaeton', 'style' => 'Latin Trap', 'prix' => 27, 'quantite' => 8],
            
            // ═══════════════════════════════════════════════════════════════
            // STANDARD (prix de base 27€, sans supplément)
            // ═══════════════════════════════════════════════════════════════
            ['reference' => 'STD-001', 'artiste' => 'Black Sabbath', 'modele' => 'Standard', 'genre' => 'Metal',       'style' => 'Doom Metal',      'prix' => 27, 'quantite' => 9],
            ['reference' => 'STD-002', 'artiste' => 'Blues Brothers', 'modele' => 'Standard', 'genre' => 'Blues',       'style' => 'Chicago Blues',   'prix' => 27, 'quantite' => 10],
            ['reference' => 'STD-003', 'artiste' => 'Dire Straits', 'modele' => 'Standard', 'genre' => 'Rock',        'style' => 'AOR',             'prix' => 27, 'quantite' => 4],
            ['reference' => 'STD-004', 'artiste' => 'Jul', 'modele' => 'Standard', 'genre' => 'Rap',         'style' => 'Rap Français',    'prix' => 27, 'quantite' => 41],
            ['reference' => 'STD-005', 'artiste' => 'PNL', 'modele' => 'Standard', 'genre' => 'Rap',         'style' => 'Cloud Rap',       'prix' => 27, 'quantite' => 5],
            ['reference' => 'STD-006', 'artiste' => 'Lana Del Rey', 'modele' => 'Standard', 'genre' => 'Pop',         'style' => 'Dream Pop',       'prix' => 27, 'quantite' => 10],
            ['reference' => 'STD-007', 'artiste' => 'Billie Eilish', 'modele' => 'Standard', 'genre' => 'Pop',         'style' => 'Electropop',      'prix' => 27, 'quantite' => 20],
            ['reference' => 'STD-008', 'artiste' => 'Taylor Swift', 'modele' => 'Standard', 'genre' => 'Pop',         'style' => 'Country Pop',     'prix' => 27, 'quantite' => 20],
            ['reference' => 'STD-009', 'artiste' => 'Arcade Fire', 'modele' => 'Standard', 'genre' => 'Rock',        'style' => 'Indie Rock',      'prix' => 27, 'quantite' => 12],
            ['reference' => 'STD-010', 'artiste' => 'Tame Impala', 'modele' => 'Standard', 'genre' => 'Rock',        'style' => 'Psychédélique',   'prix' => 27, 'quantite' => 15],
            ['reference' => 'STD-011', 'artiste' => 'Angèle', 'modele' => 'Standard', 'genre' => 'Pop',         'style' => 'Chanson Française', 'prix' => 27, 'quantite' => 18],
            ['reference' => 'STD-012', 'artiste' => 'Aya Nakamura', 'modele' => 'Standard', 'genre' => 'R&B',         'style' => 'Afropop',         'prix' => 27, 'quantite' => 22],
            ['reference' => 'STD-013', 'artiste' => 'Stromae', 'modele' => 'Standard', 'genre' => 'Électro',     'style' => 'Chanson Française', 'prix' => 27, 'quantite' => 16],
            ['reference' => 'STD-014', 'artiste' => 'Booba', 'modele' => 'Standard', 'genre' => 'Rap',         'style' => 'Rap Français',    'prix' => 27, 'quantite' => 14],
            ['reference' => 'STD-015', 'artiste' => 'Orelsan', 'modele' => 'Standard', 'genre' => 'Rap', 'style' => 'Rap Français', 'prix' => 27, 'quantite' => 6],
            ['reference' => 'STD-016', 'artiste' => 'Mylène Farmer', 'modele' => 'Standard', 'genre' => 'Pop', 'style' => 'Chanson Française', 'prix' => 27, 'quantite' => 20],
            ['reference' => 'STD-017', 'artiste' => 'Renaud', 'modele' => 'Standard', 'genre' => 'Chanson', 'style' => 'Chanson Française', 'prix' => 27, 'quantite' => 15],
            ['reference' => 'STD-018', 'artiste' => 'Guns N\' Roses', 'modele' => 'Standard', 'genre' => 'Rock', 'style' => 'Hard Rock', 'prix' => 27, 'quantite' => 10],
            ['reference' => 'STD-019', 'artiste' => 'Red Hot Chili Peppers', 'modele' => 'Standard', 'genre' => 'Rock', 'style' => 'Funk Rock', 'prix' => 27, 'quantite' => 10],
            ['reference' => 'STD-020', 'artiste' => 'Iron Maiden', 'modele' => 'Standard', 'genre' => 'Metal', 'style' => 'Heavy Metal', 'prix' => 27, 'quantite' => 8],
            ['reference' => 'STD-021', 'artiste' => 'Rammstein', 'modele' => 'Standard', 'genre' => 'Metal', 'style' => 'Industrial Metal', 'prix' => 27, 'quantite' => 10],
            ['reference' => 'STD-022', 'artiste' => 'Prince', 'modele' => 'Standard', 'genre' => 'Pop', 'style' => 'Funk Rock', 'prix' => 27, 'quantite' => 10],
            ['reference' => 'STD-023', 'artiste' => '2Pac', 'modele' => 'Standard', 'genre' => 'Rap', 'style' => 'West Coast Rap', 'prix' => 27, 'quantite' => 10],
            ['reference' => 'STD-024', 'artiste' => 'Johnny Hallyday', 'modele' => 'Standard', 'genre' => 'Rock', 'style' => 'Rock Français', 'prix' => 27, 'quantite' => 20],
            ['reference' => 'STD-025', 'artiste' => 'Indochine', 'modele' => 'Standard', 'genre' => 'Rock', 'style' => 'Rock Français', 'prix' => 27, 'quantite' => 15],
            ['reference' => 'STD-026', 'artiste' => 'NTM', 'modele' => 'Standard', 'genre' => 'Rap', 'style' => 'Rap Français', 'prix' => 27, 'quantite' => 10],
            ['reference' => 'STD-027', 'artiste' => 'IAM', 'modele' => 'Standard', 'genre' => 'Rap', 'style' => 'Rap Français', 'prix' => 27, 'quantite' => 10],
            ['reference' => 'STD-028', 'artiste' => 'Wu-Tang Clan', 'modele' => 'Standard', 'genre' => 'Rap', 'style' => 'East Coast Rap', 'prix' => 27, 'quantite' => 10],
            ['reference' => 'STD-029', 'artiste' => 'Linkin Park', 'modele' => 'Standard', 'genre' => 'Rock', 'style' => 'Nu Metal', 'prix' => 27, 'quantite' => 12],
            ['reference' => 'STD-030', 'artiste' => 'Shaka Ponk', 'modele' => 'Standard', 'genre' => 'Rock', 'style' => 'Electro Rock', 'prix' => 27, 'quantite' => 12],
            ['reference' => 'STD-031', 'artiste' => 'Kiss', 'modele' => 'Standard', 'genre' => 'Rock', 'style' => 'Hard Rock', 'prix' => 27, 'quantite' => 10],
            ['reference' => 'STD-032', 'artiste' => 'Arctic Monkeys', 'modele' => 'Standard', 'genre' => 'Rock', 'style' => 'Indie Rock', 'prix' => 27, 'quantite' => 12],
            ['reference' => 'STD-033', 'artiste' => 'Snoop Dogg', 'modele' => 'Standard', 'genre' => 'Rap', 'style' => 'West Coast Rap', 'prix' => 27, 'quantite' => 12],
            ['reference' => 'STD-034', 'artiste' => 'The Rolling Stones', 'modele' => 'Standard', 'genre' => 'Rock', 'style' => 'Classic Rock', 'prix' => 27, 'quantite' => 15],
            ['reference' => 'STD-035', 'artiste' => 'BTS', 'modele' => 'Standard', 'genre' => 'K-Pop', 'style' => 'Pop', 'prix' => 27, 'quantite' => 15],
            ['reference' => 'STD-036', 'artiste' => 'The Weeknd', 'modele' => 'Standard', 'genre' => 'R&B', 'style' => 'Pop', 'prix' => 27, 'quantite' => 10],
            ['reference' => 'STD-037', 'artiste' => 'Bad Bunny', 'modele' => 'Standard', 'genre' => 'Reggaeton', 'style' => 'Latin Trap', 'prix' => 27, 'quantite' => 8],
            
            // ═══════════════════════════════════════════════════════════════
            // NOUVEAUX VINYLES AVEC PLUSIEURS MODÈLES (ex: Pink Floyd)
            // ═══════════════════════════════════════════════════════════════
            ['reference' => 'STD-038', 'artiste' => 'Pink Floyd', 'modele' => 'The Dark Side of the Moon', 'genre' => 'Rock', 'style' => 'Progressif', 'prix' => 27, 'quantite' => 15],
            ['reference' => 'STD-039', 'artiste' => 'Pink Floyd', 'modele' => 'The Wall', 'genre' => 'Rock', 'style' => 'Progressif', 'prix' => 27, 'quantite' => 12],
            ['reference' => 'STD-040', 'artiste' => 'Pink Floyd', 'modele' => 'Wish You Were Here', 'genre' => 'Rock', 'style' => 'Progressif', 'prix' => 27, 'quantite' => 10],
        ];

        foreach ($vinyles as $data) {
            Vinyle::create($data);
        }
    }
}
