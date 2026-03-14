<?php

namespace Tests\Feature\ModeMarche;

use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Vinyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VentesJourTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Créer une vente mode marché pour les tests
     */
    private function createMarcheOrder(array $overrides = []): Order
    {
        return Order::factory()->create(array_merge([
            'source' => 'marche',
            'statut' => 'payee',
            'mode_paiement_marche' => 'cb_terminal',
        ], $overrides));
    }

    /**
     * Test que l'employé peut voir l'historique des ventes du jour.
     */
    public function test_employe_can_view_sales_history_for_today(): void
    {
        $employe = User::factory()->create(['role' => 'employe']);
        
        // Créer 3 ventes mode marché d'aujourd'hui
        $this->createMarcheOrder(['created_at' => now()]);
        $this->createMarcheOrder(['created_at' => now()]);
        $this->createMarcheOrder(['created_at' => now()]);

        $response = $this->actingAs($employe)->get('/admin/marche/ventes-jour?view=json');

        $response->assertStatus(200);
        $response->assertJsonPath('nb_ventes', 3);
    }

    /**
     * Test que l'historique des ventes affiche le total correct du jour.
     */
    public function test_sales_history_shows_correct_total_amount(): void
    {
        $employe = User::factory()->create(['role' => 'employe']);
        
        // Créer des ventes avec des montants connus
        $this->createMarcheOrder(['total' => 25.00, 'created_at' => now()]);
        $this->createMarcheOrder(['total' => 35.00, 'created_at' => now()]);
        $this->createMarcheOrder(['total' => 40.00, 'created_at' => now()]);

        $response = $this->actingAs($employe)->get('/admin/marche/ventes-jour?view=json');

        $response->assertStatus(200);
        $this->assertEquals(100.00, $response->json('total_jour'), '', 0.01);
    }

    /**
     * Test que l'on peut filtrer par date.
     */
    public function test_sales_history_can_filter_by_date(): void
    {
        $employe = User::factory()->create(['role' => 'employe']);
        
        $yesterday = now()->subDay();
        $todayOrder = $this->createMarcheOrder(['total' => 50.00, 'created_at' => now()]);
        $yesterdayOrder = $this->createMarcheOrder(['total' => 30.00, 'created_at' => $yesterday]);

        // Filtrer par hier
        $response = $this->actingAs($employe)->get('/admin/marche/ventes-jour?date=' . $yesterday->format('Y-m-d') . '&view=json');

        $response->assertStatus(200);
        $ventes = $response->json('ventes');
        
        // Vérifier que la commande d'hier est présente, pas celle d'aujourd'hui
        $ids = collect($ventes)->pluck('id')->toArray();
        $this->assertContains($yesterdayOrder->id, $ids);
        $this->assertNotContains($todayOrder->id, $ids);
        
        $this->assertEquals(30.00, $response->json('total_jour'), '', 0.01);
    }

    /**
     * Test que l'historique inclut les détails des transactions.
     */
    public function test_sales_history_includes_transaction_details(): void
    {
        $employe = User::factory()->create(['role' => 'employe']);
        $vinyle = Vinyle::factory()->create(['prix' => 15.00]);
        
        $order = $this->createMarcheOrder([
            'total' => 30.00,
            'mode_paiement_marche' => 'cb_terminal',
            'created_at' => now(),
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'vinyle_id' => $vinyle->id,
            'quantite' => 2,
            'prix_unitaire' => 15.00,
            'total' => 30.00,
        ]);

        $response = $this->actingAs($employe)->get('/admin/marche/ventes-jour?view=json');

        $response->assertStatus(200);
        $response->assertJsonPath('nb_ventes', 1);
        
        // Vérifier que l'ID de la commande est dans la réponse
        $ventes = $response->json('ventes');
        $this->assertNotEmpty($ventes);
        $this->assertEquals($order->id, $ventes[0]['id']);
    }

    /**
     * Test que le client ne peut pas accéder à l'historique des ventes.
     * Le middleware redirige vers /login (302) ou kiosque (selon CheckRole)
     */
    public function test_client_cannot_access_sales_history(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($client)->get('/admin/marche/ventes-jour');

        // CheckRole redirige, donc on accepte 302 ou 403
        $this->assertTrue(in_array($response->getStatusCode(), [302, 403]));
    }

    /**
     * Test que l'admin peut aussi voir l'historique des ventes.
     */
    public function test_admin_can_access_sales_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $this->createMarcheOrder(['total' => 25.00, 'created_at' => now()]);

        $response = $this->actingAs($admin)->get('/admin/marche/ventes-jour?view=json');

        $response->assertStatus(200);
        $response->assertJsonPath('nb_ventes', 1);
    }

    /**
     * Test que les ventes sont triées par date décroissante.
     */
    public function test_sales_are_ordered_by_date_desc(): void
    {
        $employe = User::factory()->create(['role' => 'employe']);
        
        $earlyOrder = $this->createMarcheOrder([
            'created_at' => now()->subHours(2),
        ]);
        $lateOrder = $this->createMarcheOrder([
            'created_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($employe)->get('/admin/marche/ventes-jour?view=json');

        $response->assertStatus(200);
        $ventes = $response->json('ventes');
        
        // La plus récente doit être en premier
        $this->assertEquals($lateOrder->id, $ventes[0]['id']);
    }
}
