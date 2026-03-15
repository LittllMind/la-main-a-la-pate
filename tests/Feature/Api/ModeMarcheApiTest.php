<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Vinyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests API pour Mode Marché (routes /api/marche/*)
 * T14 - Tests séparés des routes web (Option C)
 */
class ModeMarcheApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([\Database\Seeders\DatabaseSeeder::class]);
    }

    // ===========================================
    // T14.1 — HISTORIQUE VENTES JOUR (API)
    // ===========================================

    /** @test */
    public function employe_can_get_sales_history_via_api()
    {
        // Arrange
        $employe = User::factory()->create(['role' => 'employe']);
        
        Order::factory()->create([
            'source' => 'marche',
            'statut' => 'payee',
            'total' => 20,
            'created_at' => now(),
        ]);

        // Act
        $response = $this->actingAs($employe)
                         ->getJson('/api/marche/ventes-jour');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'ventes',
                    'total_jour',
                    'nb_ventes'
                ]);
    }

    /** @test */
    public function api_sales_history_shows_correct_total_amount()
    {
        // Arrange
        $employe = User::factory()->create(['role' => 'employe']);
        
        Order::factory()->create([
            'source' => 'marche',
            'total' => 50,
            'statut' => 'payee',
            'created_at' => now(),
        ]);
        
        Order::factory()->create([
            'source' => 'marche',
            'total' => 75,
            'statut' => 'payee',
            'created_at' => now(),
        ]);

        // Act
        $response = $this->actingAs($employe)
                         ->getJson('/api/marche/ventes-jour');

        // Assert
        $data = $response->assertStatus(200)->json();
        $this->assertEquals(125, $data['total_jour']);
        $this->assertEquals(2, $data['nb_ventes']);
    }

    /** @test */
    public function api_unauthenticated_user_cannot_access_sales_history()
    {
        // Act
        $response = $this->getJson('/api/marche/ventes-jour');

        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function api_client_cannot_access_sales_history()
    {
        // Arrange
        $client = User::factory()->create(['role' => 'client']);

        // Act
        $response = $this->actingAs($client)
                         ->getJson('/api/marche/ventes-jour');

        // Assert
        $response->assertStatus(403);
    }

    // ===========================================
    // T14.2 — ANNULATION VENTE (API)
    // ===========================================

    /** @test */
    public function api_employe_can_cancel_sale()
    {
        // Arrange
        $employe = User::factory()->create(['role' => 'employe']);
        $vinyle = Vinyle::factory()->create(['quantite' => 10]);
        
        $order = Order::factory()->create([
            'source' => 'marche',
            'statut' => 'payee',
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'vinyle_id' => $vinyle->id,
            'quantite' => 2,
        ]);

        // Act
        $response = $this->actingAs($employe)
                         ->postJson("/api/marche/{$order->id}/cancel");

        // Assert
        $response->assertStatus(200)
                ->assertJson(['success' => true]);
        
        $order->refresh();
        $this->assertEquals('annulee', $order->statut);
    }

    /** @test */
    public function api_cancelled_sale_triggers_restock()
    {
        // Arrange
        $employe = User::factory()->create(['role' => 'employe']);
        $vinyle = Vinyle::factory()->create(['quantite' => 10]);
        
        $order = Order::factory()->create([
            'source' => 'marche',
            'statut' => 'payee',
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'vinyle_id' => $vinyle->id,
            'quantite' => 3,
        ]);

        // Act
        $this->actingAs($employe)
             ->postJson("/api/marche/{$order->id}/cancel");

        // Assert
        $vinyle->refresh();
        $this->assertEquals(13, $vinyle->quantite); // 10 + 3 restockés
    }

    /** @test */
    public function api_client_cannot_cancel_sale()
    {
        // Arrange
        $client = User::factory()->create(['role' => 'client']);
        $order = Order::factory()->create([
            'source' => 'marche',
            'statut' => 'payee',
        ]);

        // Act
        $response = $this->actingAs($client)
                         ->postJson("/api/marche/{$order->id}/cancel");

        // Assert
        $response->assertStatus(403);
    }

    // ===========================================
    // T14.3 — EXPORT (API)
    // ===========================================

    /** @test */
    public function api_employe_can_export_daily_sales_as_csv()
    {
        // Arrange
        $employe = User::factory()->create(['role' => 'employe']);
        
        Order::factory()->create([
            'source' => 'marche',
            'total' => 50,
            'numero_commande' => 'MCH-2024-001',
            'created_at' => now(),
        ]);

        // Act
        $response = $this->actingAs($employe)
                         ->get('/api/marche/export?format=csv');

        // Assert
        $response->assertStatus(200)
                ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
