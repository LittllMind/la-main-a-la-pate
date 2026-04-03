<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MouvementStock;
use App\Models\Vinyle;
use App\Models\Fond;
use App\Models\User;
use Illuminate\Support\Carbon;

class MouvementStockSeeder extends Seeder
{
    /**
     * Créer un historique de mouvements réaliste
     */
    public function run(): void
    {
        MouvementStock::truncate();
        
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $admin = User::first();
        }

        // ═══════════════════════════════════════════════════════════════
        // Mouvements sur les Fonds
        // ═══════════════════════════════════════════════════════════════
        $fonds = Fond::all();
        
        foreach ($fonds as $fond) {
            // Livraison initiale
            MouvementStock::create([
                'type' => 'entree',
                'produit_type' => $fond->type === 'miroir' ? 'miroir' : 'dore',
                'produit_id' => $fond->id,
                'quantite' => $fond->type === 'miroir' ? 50 : 30,
                'date_mouvement' => Carbon::parse('-30 days'),
                'user_id' => $admin->id,
                'reference' => 'LIV-2026-001',
                'notes' => "Livraison initiale fournisseur",
            ]);
            
            // Réapprovisionnement
            MouvementStock::create([
                'type' => 'entree',
                'produit_type' => $fond->type === 'miroir' ? 'miroir' : 'dore',
                'produit_id' => $fond->id,
                'quantite' => $fond->type === 'miroir' ? 50 : 70,
                'date_mouvement' => Carbon::parse('-7 days'),
                'user_id' => $admin->id,
                'reference' => 'LIV-2026-002',
                'notes' => "Réapprovisionnement",
            ]);
            
            // Consommation (utilisés pour vinyles)
            $conso = $fond->type === 'miroir' ? 100 : 100;
            MouvementStock::create([
                'type' => 'sortie',
                'produit_type' => $fond->type === 'miroir' ? 'miroir' : 'dore',
                'produit_id' => $fond->id,
                'quantite' => $conso,
                'date_mouvement' => Carbon::parse('-2 days'),
                'user_id' => $admin->id,
                'reference' => null,
                'notes' => "Utilisé pour production vinyles",
            ]);
        }

        // ═══════════════════════════════════════════════════════════════
        // Mouvements sur les Vinyles
        // ═══════════════════════════════════════════════════════════════
        $vinylesSamples = Vinyle::inRandomOrder()->limit(10)->get();
        
        foreach ($vinylesSamples as $index => $vinyle) {
            // Production (entrée)
            MouvementStock::create([
                'type' => 'entree',
                'produit_type' => 'vinyle',
                'produit_id' => $vinyle->id,
                'quantite' => $vinyle->quantite + 5,
                'date_mouvement' => Carbon::parse('-14 days')->addDays($index),
                'user_id' => $admin->id,
                'reference' => "PROD-2026-" . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'notes' => "Fabrication nouvelle série",
            ]);
            
            // Ventes (sorties aléatoires)
            if (rand(0, 1)) {
                $ventes = rand(1, 5);
                MouvementStock::create([
                    'type' => 'sortie',
                    'produit_type' => 'vinyle',
                    'produit_id' => $vinyle->id,
                    'quantite' => $ventes,
                    'date_mouvement' => Carbon::parse('-5 days')->addDays($index),
                    'user_id' => $admin->id,
                    'reference' => "CMD-2026-" . str_pad($index + 10, 3, '0', STR_PAD_LEFT),
                    'notes' => "Vente client",
                ]);
            }
        }
        
        // Mouvements récents (cette semaine)
        $vinylesRupture = Vinyle::where('quantite', '<=', 3)->get();
        foreach ($vinylesRupture->take(3) as $vinyle) {
            MouvementStock::create([
                'type' => 'entree',
                'produit_type' => 'vinyle',
                'produit_id' => $vinyle->id,
                'quantite' => 10,
                'date_mouvement' => Carbon::parse('-1 day'),
                'user_id' => $admin->id,
                'reference' => "REAP-" . $vinyle->reference,
                'notes' => "Réapprovisionnement stock faible",
            ]);
        }
    }
}