<?php

namespace App\Console\Commands;

use App\Models\StockAlert;
use App\Models\Vinyle;
use Illuminate\Console\Command;

class CheckStockAlerts extends Command
{
    protected $signature = 'stock:check-alerts {--notify : Envoyer une notification email}';

    protected $description = 'Vérifie les stocks et crée des alertes si nécessaire';

    public function handle()
    {
        $this->info('🔍 Vérification des stocks...');

        // Récupérer tous les vinyles sous le seuil d'alerte
        $lowStockVinyles = Vinyle::where('quantite', '<=', 0)
            ->orWhereRaw('quantite <= seuil_alerte')
            ->get();

        $created = 0;
        foreach ($lowStockVinyles as $vinyle) {
            // Vérifier si une alerte active existe déjà
            $existingAlert = StockAlert::where('alertable_type', Vinyle::class)
                ->where('alertable_id', $vinyle->id)
                ->where('statut', 'actif')
                ->exists();

            if (!$existingAlert) {
                StockAlert::create([
                    'alertable_type' => Vinyle::class,
                    'alertable_id' => $vinyle->id,
                    'quantite_actuelle' => $vinyle->quantite,
                    'seuil_alerte' => $vinyle->seuil_alerte ?? 1,
                    'statut' => 'actif',
                ]);

                $this->line("  ⚠️  Alerte créée : {$vinyle->nom}");
                $created++;
            }
        }

        $this->info("✅ {$created} alerte(s) créée(s)");
        
        // Afficher le résumé
        $totalActives = StockAlert::where('statut', 'actif')->count();
        $this->info("📊 Total alertes actives : {$totalActives}");

        return 0;
    }
}
