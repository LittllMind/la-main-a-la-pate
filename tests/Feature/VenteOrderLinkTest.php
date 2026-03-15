<?php

namespace Tests\Feature;

use App\Models\Fond;
use App\Models\User;
use App\Models\Vente;
use App\Models\Vinyle;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenteOrderLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_vente_creates_linked_order()
    {
        // Arrange
        $user = User::factory()->create();
        $fond = Fond::factory()->create(['solde' => 100]);
        $vinyle = Vinyle::factory()->create(['prix' => 25]);

        // Act - Créer une vente kiosque
        $vente = Vente::create([
            'date' => now(),
            'total' => 25,
            'mode_paiement' => 'carte',
        ]);

        // Assert - Un Order doit être créé automatiquement
        $this->assertDatabaseHas('orders', [
            'source' => 'kiosque',
            'total' => 25,
            'statut' => 'completed',
        ]);

        $order = Order::where('source', 'kiosque')->first();
        $this->assertNotNull($order);
        $this->assertNotNull($order->numero_commande);
        
        // Vérifier la liaison
        $this->assertEquals($vente->id, $order->vente_id);
    }

    public function test_vente_order_has_generated_number()
    {
        $vente = Vente::create([
            'date' => now(),
            'total' => 30,
            'mode_paiement' => 'especes',
        ]);

        $order = Order::where('vente_id', $vente->id)->first();
        
        $this->assertNotNull($order->numero_commande);
        $this->assertStringStartsWith('CMD-', $order->numero_commande);
        $this->assertMatchesRegularExpression('/^CMD-\d{4}-\d{4}$/', $order->numero_commande);
    }

    public function test_vente_order_is_accessible_from_vente()
    {
        $vente = Vente::create([
            'date' => now(),
            'total' => 20,
            'mode_paiement' => 'carte',
        ]);

        $this->assertNotNull($vente->order);
        $this->assertEquals($vente->total, $vente->order->total);
    }

    public function test_order_is_accessible_from_vente_relation()
    {
        $vente = Vente::create([
            'date' => now(),
            'total' => 15,
            'mode_paiement' => 'especes',
        ]);

        $this->assertInstanceOf(Order::class, $vente->order);
    }
}
