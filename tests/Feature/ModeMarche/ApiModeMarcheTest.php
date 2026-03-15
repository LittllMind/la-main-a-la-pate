<?php

namespace Tests\Feature\ModeMarche;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Vinyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests API Mode Marché (routes /api/marche/*)
 * Ces routes doivent TOUJOURS retourner du JSON, jamais de HTML Blade.
 */
class ApiModeMarcheTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: GET /api/marche/ventes-jour retourne toujours JSON
     * Le endpoint API ne doit jamais retourner de HTML Blade.
     */
    public function test_api_ventes_jour_returns_json_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Créer une vente marché
        $order = Order::factory()->create([
            'source' => 'marche',
            'statut' => 'payee',
            'total' => 50.00,
            'mode_paiement_marche' => 'cash',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/marche/ventes-jour');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJsonStructure([
            'ventes',
            'total_jour',
            'nb_ventes',
            'date',
        ]);
    }

    /**
     * Test: GET /api/marche/ventes-jour ne retourne que les ventes marché
     */
    public function test_api_ventes_jour_returns_only_marche_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Ventes marché (doivent apparaître)
        Order::factory()->count(2)->create([
            'source' => 'marche',
            'statut' => 'payee',
            'total' => 25.00,
        ]);
        
        // Commandes web (ne doivent PAS apparaître)
        Order::factory()->count(3)->create([
            'source' => 'web',
            'statut' => 'payee',
            'total' => 30.00,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/marche/ventes-jour');

        $response->assertStatus(200);
        $response->assertJsonFragment(['nb_ventes' => 2]);
        $response->assertJsonFragment(['total_jour' => 50.00]);
    }

    /**
     * Test: GET /api/marche/ventes-jour filtre par date
     */
    public function test_api_ventes_jour_filters_by_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $yesterday = now()->subDay()->toDateString();
        
        // Vente hier
        Order::factory()->create([
            'source' => 'marche',
            'statut' => 'payee',
            'total' => 40.00,
            'created_at' => now()->subDay(),
        ]);
        
        // Vente aujourd'hui
        Order::factory()->create([
            'source' => 'marche',
            'statut' => 'payee',
            'total' => 60.00,
        ]);

        $response = $this->actingAs($admin)->getJson("/api/marche/ventes-jour?date={$yesterday}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['total_jour' => 40.00]);
        $response->assertJsonFragment(['nb_ventes' => 1]);
    }

    /**
     * Test: GET /api/marche/ventes-jour inclut les détails des ventes
     */
    public function test_api_ventes_jour_includes_sale_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vinyle = Vinyle::factory()->create(['prix' => 15.00]);
        
        $order = Order::factory()->create([
            'source' => 'marche',
            'statut' => 'payee',
            'total' => 30.00,
            'mode_paiement_marche' => 'cb_terminal',
            'numero_commande' => 'CMD-MAR-001',
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'vinyle_id' => $vinyle->id,
            'quantite' => 2,
            'prix_unitaire' => 15.00,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/marche/ventes-jour');

        $response->assertStatus(200);
        $response->assertJsonFragment(['numero_commande' => 'CMD-MAR-001']);
        $response->assertJsonFragment(['mode_paiement' => 'carte']);
        $response->assertJsonFragment(['items_count' => 1]);
    }

    /**
     * Test: GET /api/marche/ventes-jour nécessite authentification
     */
    public function test_api_ventes_jour_requires_authentication(): void
    {
        $response = $this->getJson('/api/marche/ventes-jour');

        $response->assertStatus(401);
    }

    /**
     * Test: GET /api/marche/ventes-jour accessible aux employés
     */
    public function test_api_ventes_jour_accessible_to_employes(): void
    {
        $employe = User::factory()->create(['role' => 'employe']);
        
        Order::factory()->create([
            'source' => 'marche',
            'statut' => 'payee',
            'total' => 25.00,
        ]);

        $response = $this->actingAs($employe)->getJson('/api/marche/ventes-jour');

        $response->assertStatus(200);
        $response->assertJsonFragment(['nb_ventes' => 1]);
    }

    /**
     * Test: GET /api/marche/ventes-jour refusé aux clients
     */
    public function test_api_ventes_jour_denied_to_clients(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($client)->getJson('/api/marche/ventes-jour');

        $response->assertStatus(403);
    }

    /**
     * Test: POST /api/marche/{order}/cancel annule une vente marché
     */
    public function test_api_cancel_marche_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vinyle = Vinyle::factory()->create(['quantite' => 5]);
        
        $order = Order::factory()->create([
            'source' => 'marche',
            'statut' => 'payee',
            'total' => 50.00,
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'vinyle_id' => $vinyle->id,
            'quantite' => 2,
            'prix_unitaire' => 25.00,
        ]);

        $response = $this->actingAs($admin)->postJson("/api/marche/{$order->id}/cancel");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $order->refresh();
        $this->assertEquals('annulee', $order->statut);
        
        $vinyle->refresh();
        $this->assertEquals(7, $vinyle->quantite); // Stock restauré
    }

    /**
     * Test: POST /api/marche/{order}/cancel refusé pour commande web
     */
    public function test_api_cancel_refused_for_web_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $order = Order::factory()->create([
            'source' => 'web',
            'statut' => 'payee',
        ]);

        $response = $this->actingAs($admin)->postJson("/api/marche/{$order->id}/cancel");

        $response->assertStatus(403);
    }

    /**
     * Test: GET /api/marche/export retourne CSV
     */
    public function test_api_export_returns_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Order::factory()->create([
            'source' => 'marche',
            'statut' => 'payee',
            'total' => 100.00,
            'mode_paiement_marche' => 'cash',
            'numero_commande' => 'CMD-EXPORT-001',
        ]);

        $response = $this->actingAs($admin)->get('/api/marche/export?format=csv');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
