<?php

namespace Tests\Feature\Fonds;

use Tests\TestCase;
use App\Models\Fond;
use App\Models\User;
use App\Models\MouvementStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\StockMovementService;

class FondStockMovementDebugTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function service_peut_creer_mouvement_stock_directement()
    {
        $admin = $this->adminUser();
        $fond = Fond::factory()->create(['quantite' => 10, 'type' => 'Test']);

        $this->actingAs($admin);

        $mvt = StockMovementService::incrementerFond($fond, 5, null, 'Test');

        $this->assertNotNull($mvt);
        // Le service mappe 'Test' vers 'pochette' (default)
        $this->assertEquals('pochette', $mvt->produit_type);
        $this->assertEquals('entree', $mvt->type);
        $this->assertEquals(5, $mvt->quantite);
    }

    /** @test */

    /** @test */
    public function debug_action_increment_avec_verification_detaillee()
    {
        $admin = $this->adminUser();
        $fond = Fond::factory()->create(['quantite' => 10, 'type' => 'Miroir']);

        // Vérifier qu'aucun mouvement n'existe
        $this->assertEquals(0, MouvementStock::count());

        $response = $this->actingAs($admin)
            ->patch(route('fonds.updateStock', $fond), [
                'action' => 'increment',
                'quantite' => 5
            ]);

        $response->assertRedirect(route('fonds.index'));

        // Afficher pour debug
        $count = MouvementStock::count();
        dump("Nombre de mouvements après action: {$count}");
        
        if ($count > 0) {
            $mvt = MouvementStock::first();
            dump("Premier mouvement:", $mvt->toArray());
        }

        // Le test: vérifier qu'il y a AU MOINS un mouvement
        $this->assertGreaterThan(0, $count, 'Aucun mouvement créé');
    }
}