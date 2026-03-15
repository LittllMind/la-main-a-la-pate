<?php

namespace Tests\Feature\ModeMarche;

use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Vinyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AnnulationVenteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Créer une commande mode marché pour les tests
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
     * Test: Un employé peut annuler une vente récente
     * T14.2.1
     */
    public function test_employe_can_cancel_sale_within_time_limit(): void
    {
        // Arrange: Créer un employé et une vente récente
        $employe = User::factory()->create(['role' => 'employe']);
        $vinyle = Vinyle::factory()->create(['quantite' => 10]);
        
        $order = $this->createMarcheOrder([
            'created_at' => now()->subMinutes(5),
            'total' => 50.00,
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'vinyle_id' => $vinyle->id,
            'quantite' => 2,
            'prix_unitaire' => 25.00,
            'total' => 50.00,
        ]);

        // Act: L'employé annule la vente
        $response = $this->actingAs($employe)
            ->postJson(route('marche.cancel', $order));

        // Assert: La vente est annulée avec succès
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
        
        $order->refresh();
        $this->assertEquals('annulee', $order->statut);
        $this->assertNotNull($order->annulee_at);
    }

    /**
     * Test: L'annulation d'une vente restock automatiquement les articles
     * T14.2.2
     */
    public function test_cancelled_sale_triggers_restock(): void
    {
        // Arrange: Créer une vente avec stock décrémenté
        $employe = User::factory()->create(['role' => 'employe']);
        $vinyle = Vinyle::factory()->create(['quantite' => 10]);
        
        $order = $this->createMarcheOrder([
            'created_at' => now()->subMinutes(5),
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'vinyle_id' => $vinyle->id,
            'quantite' => 3,
        ]);
        
        // Simuler la décrémentation du stock (comme lors d'une vraie vente)
        $vinyle->quantite = 7; // 10 - 3
        $vinyle->save();
        
        // Act: Annuler la vente
        $this->actingAs($employe)
            ->postJson(route('marche.cancel', $order));

        // Assert: Le stock est restauré
        $vinyle->refresh();
        $this->assertEquals(10, $vinyle->quantite);
    }

    /**
     * Test: Un client ne peut pas annuler une vente
     * T14.2.3
     */
    public function test_client_cannot_cancel_sale(): void
    {
        // Arrange: Créer un client et une vente
        $client = User::factory()->create(['role' => 'client']);
        $order = $this->createMarcheOrder([
            'created_at' => now()->subMinutes(5),
        ]);

        // Act: Le client tente d'annuler
        $response = $this->actingAs($client)
            ->postJson(route('marche.cancel', $order));

        // Assert: Accès refusé (403)
        $response->assertStatus(403);
        
        // Vérifier que la vente n'est pas annulée
        $order->refresh();
        $this->assertEquals('payee', $order->statut);
    }

    /**
     * Test: Une vente annulée garde une trace (soft delete concept)
     * T14.2.4
     */
    public function test_cancelled_sale_is_preserved_not_deleted(): void
    {
        // Arrange: Créer une vente avec items
        $employe = User::factory()->create(['role' => 'employe']);
        $vinyle = Vinyle::factory()->create();
        
        $order = $this->createMarcheOrder([
            'created_at' => now()->subMinutes(5),
            'total' => 75.00,
            'mode_paiement_marche' => 'cash',
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'vinyle_id' => $vinyle->id,
            'quantite' => 1,
            'prix_unitaire' => 75.00,
        ]);

        // Act: Annuler la vente
        $this->actingAs($employe)
            ->postJson(route('marche.cancel', $order));

        // Assert: La vente existe toujours en base avec statut annulé
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'statut' => 'annulee',
            'total' => 75.00,
            'mode_paiement_marche' => 'cash',
        ]);
        
        // Les items sont conservés
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
        ]);
    }

    /**
     * Test: Impossible d'annuler une vente après la limite de temps
     * T14.2.5
     */
    public function test_cannot_cancel_sale_after_time_limit(): void
    {
        // Arrange: Créer une vente vieille de plus de 24h
        $employe = User::factory()->create(['role' => 'employe']);
        $order = $this->createMarcheOrder([
            'created_at' => now()->subHours(25),
        ]);

        // Act: Tenter d'annuler
        $response = $this->actingAs($employe)
            ->postJson(route('marche.cancel', $order));

        // Assert: Refusé car trop tard
        $response->assertStatus(422)
            ->assertJson(['success' => false]);
        
        $order->refresh();
        $this->assertEquals('payee', $order->statut);
    }

    /**
     * Test: Impossible d'annuler une vente déjà annulée
     * T14.2.6
     */
    public function test_cannot_cancel_already_cancelled_sale(): void
    {
        // Arrange: Créer une vente déjà annulée
        $employe = User::factory()->create(['role' => 'employe']);
        $order = $this->createMarcheOrder([
            'created_at' => now()->subMinutes(5),
            'statut' => 'annulee',
            'annulee_at' => now(),
        ]);

        // Act: Tenter d'annuler à nouveau
        $response = $this->actingAs($employe)
            ->postJson(route('marche.cancel', $order));

        // Assert: Erreur car déjà annulée
        $response->assertStatus(400)
            ->assertJson(['error' => 'Cette vente est déjà annulée']);
    }

    /**
     * Test: Seules les ventes mode marché peuvent être annulées via cette route
     * T14.2.7
     */
    public function test_only_marche_sales_can_be_cancelled_here(): void
    {
        // Arrange: Créer une vente en ligne (pas mode marché)
        $employe = User::factory()->create(['role' => 'employe']);
        $order = Order::factory()->create([
            'source' => 'web', // Pas mode marché
            'statut' => 'payee',
            'created_at' => now()->subMinutes(5),
        ]);

        // Act: Tenter d'annuler via la route marche
        $response = $this->actingAs($employe)
            ->postJson(route('marche.cancel', $order));

        // Assert: Refusé car pas mode marché
        $response->assertStatus(403)
            ->assertJson(['error' => 'Seules les ventes marché peuvent être annulées ici']);
    }

    /**
     * Test: L'annulation nécessite une authentification
     * T14.2.8
     */
    public function test_cancel_requires_authentication(): void
    {
        // Arrange: Une vente mode marché
        $order = $this->createMarcheOrder([
            'created_at' => now()->subMinutes(5),
        ]);

        // Act: Tenter d'annuler sans être connecté
        $response = $this->postJson(route('marche.cancel', $order));

        // Assert: Redirection vers login
        $response->assertStatus(401);
    }

    /**
     * Test: Le restock fonctionne pour plusieurs articles
     * T14.2.9
     */
    public function test_restock_works_for_multiple_items(): void
    {
        // Arrange: Créer une vente avec plusieurs articles
        $employe = User::factory()->create(['role' => 'employe']);
        $vinyle1 = Vinyle::factory()->create(['quantite' => 5]);
        $vinyle2 = Vinyle::factory()->create(['quantite' => 8]);
        
        $order = $this->createMarcheOrder([
            'created_at' => now()->subMinutes(5),
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'vinyle_id' => $vinyle1->id,
            'quantite' => 2,
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'vinyle_id' => $vinyle2->id,
            'quantite' => 3,
        ]);
        
        // Simuler le stock après vente
        $vinyle1->quantite = 3; // 5 - 2
        $vinyle1->save();
        $vinyle2->quantite = 5; // 8 - 3
        $vinyle2->save();

        // Act: Annuler la vente
        $this->actingAs($employe)
            ->postJson(route('marche.cancel', $order));

        // Assert: Les deux stocks sont restaurés
        $vinyle1->refresh();
        $vinyle2->refresh();
        $this->assertEquals(5, $vinyle1->quantite);
        $this->assertEquals(8, $vinyle2->quantite);
    }

    /**
     * Test: Un admin peut aussi annuler une vente
     * T14.2.10
     */
    public function test_admin_can_cancel_sale(): void
    {
        // Arrange: Créer un admin et une vente
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->createMarcheOrder([
            'created_at' => now()->subMinutes(5),
        ]);

        // Act: L'admin annule la vente
        $response = $this->actingAs($admin)
            ->postJson(route('marche.cancel', $order));

        // Assert: Succès
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
        
        $order->refresh();
        $this->assertEquals('annulee', $order->statut);
    }
}
