<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vinyle;
use App\Models\Fond;

class TestStockMovement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:stock-movement';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test les mouvements de stock automatiques';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Test des mouvements de stock automatiques...');

        // Test création vinyle
        $this->info('\nCréation d\'un vinyle test avec stock 10...');
        $vinyle = Vinyle::factory()->create([
            'titre' => 'TEST - Movement',
            'reference' => 'TEST-MOV-'.time(),
            'stock' => 10,
            'prix' => 25.00,
        ]);
        $this->info('✅ Vinyle créé : ID ' . $vinyle->id);

        // Test mise à jour stock
        $this->info('\nMise à jour du stock à 15 (+5)...');
        $vinyle->update(['stock' => 15]);
        $this->info('✅ Stock mis à jour');

        // Test diminution stock
        $this->info('\nDiminution du stock à 8 (-7)...');
        $vinyle->update(['stock' => 8]);
        $this->info('✅ Stock diminué');

        // Cleanup
        $this->info('\nSuppression du vinyle test...');
        $vinyle->delete();
        $this->info('✅ Vinyle supprimé');

        $this->info('\n--- Vérification des mouvements ---');
        $mouvements = \App\Models\MouvementStock::where('produit_type', 'vinyle')
            ->where('reference', 'like', 'TEST-%')
            ->get();

        foreach ($mouvements as $mvt) {
            $this->line(sprintf(
                '%s | %s | %s | %d unités | %s',
                $mvt->date_mouvement->format('d/m/Y H:i'),
                strtoupper($mvt->type),
                $mvt->reference,
                $mvt->quantite,
                $mvt->notes
            ));
        }

        // Cleanup mouvements test
        $mouvements->each->delete();

        $this->info('\n✅ Test terminé !');
    }
}
