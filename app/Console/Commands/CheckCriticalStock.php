<?php

namespace App\Console\Commands;

use App\Models\Fond;
use App\Models\StockAlert;
use App\Models\Vinyle;
use App\Mail\StockCritiqueQuotidien;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckCriticalStock extends Command
{
    protected $signature = 'stock:check-critical';
    protected $description = 'Vérifie les stocks critiques et crée/met à jour les alertes';

    public function handle(): int
    {
        $this->info('🔍 Vérification des stocks critiques...');

        $alertesCreees = 0;
        $alertesResolues = 0;

        // 1. Vérification des VINYLES
        $seuilVinyles = config('stock.seuils.vinyles');
        $vinylessCritiques = Vinyle::where('quantite', '<=', $seuilVinyles)->get();

        foreach ($vinylessCritiques as $vinyle) {
            $alerte = StockAlert::firstOrCreate(
                [
                    'alertable_type' => Vinyle::class,
                    'alertable_id' => $vinyle->id,
                    'statut' => 'actif',
                ],
                [
                    'quantite_actuelle' => $vinyle->quantite,
                    'seuil_alerte' => $seuilVinyles,
                ]
            );

            if ($alerte->wasRecentlyCreated) {
                $alertesCreees++;
                $this->warn("⚠️  Alerte créée : {$vinyle->nom} ({$vinyle->quantite} unités)");
            } else {
                // Mise à jour de la quantité actuelle
                $alerte->update(['quantite_actuelle' => $vinyle->quantite]);
            }
        }

        // Résoudre les alertes vinyles dont le stock est remonté
        $alertesVinylesResolues = StockAlert::actives()
            ->where('alertable_type', Vinyle::class)
            ->get()
            ->filter(function ($alerte) use ($seuilVinyles) {
                return $alerte->alertable->quantite > $seuilVinyles;
            });

        foreach ($alertesVinylesResolues as $alerte) {
            $alerte->marquerResolu();
            $alertesResolues++;
            $this->info("✅ Alerte résolue : {$alerte->alertable->nom}");
        }

        // 2. Vérification des FONDS
        $fondMiroir = Fond::where('type', 'miroir')->first();
        $fondDore = Fond::where('type', 'dore')->first();

        foreach ([$fondMiroir, $fondDore] as $fond) {
            if (!$fond) continue;

            $seuil = config("stock.seuils.fond_{$fond->type}");
            
            if ($fond->quantite <= $seuil) {
                $alerte = StockAlert::firstOrCreate(
                    [
                        'alertable_type' => Fond::class,
                        'alertable_id' => $fond->id,
                        'statut' => 'actif',
                    ],
                    [
                        'quantite_actuelle' => $fond->quantite,
                        'seuil_alerte' => $seuil,
                    ]
                );

                if ($alerte->wasRecentlyCreated) {
                    $alertesCreees++;
                    $this->warn("⚠️  Alerte créée : Fond {$fond->type} ({$fond->quantite} unités)");
                } else {
                    $alerte->update(['quantite_actuelle' => $fond->quantite]);
                }
            } else {
                // Résoudre si stock remonté
                StockAlert::actives()
                    ->where('alertable_type', Fond::class)
                    ->where('alertable_id', $fond->id)
                    ->get()
                    ->each(function ($alerte) use (&$alertesResolues) {
                        $alerte->marquerResolu();
                        $alertesResolues++;
                        $this->info("✅ Alerte résolue : Fond {$alerte->alertable->type}");
                    });
            }
        }

        // 3. Envoi de l'email si alertes actives
        $alertesActives = StockAlert::actives()->with('alertable')->get();

        if ($alertesActives->isNotEmpty()) {
            Mail::to(config('stock.notification_email'))
                ->send(new StockCritiqueQuotidien($alertesActives));

            StockAlert::actives()->update([
                'derniere_notification_envoyee' => now()
            ]);

            $this->info("📧 Email envoyé à " . config('stock.notification_email'));
        } else {
            $this->info("✅ Aucune alerte active, pas d'email envoyé");
        }

        $this->info("\n📊 Résumé : {$alertesCreees} créées, {$alertesResolues} résolues");

        return Command::SUCCESS;
    }
}
