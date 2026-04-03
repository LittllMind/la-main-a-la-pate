<?php

namespace Tests\Feature;

use App\Models\Fond;
use App\Models\Vinyle;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfrastructureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function la_base_sqlite_in_memory_fonctionne(): void
    {
        // Vérifier la connexion
        $this->assertDatabaseCount('users', 0);
        
        // Créer un utilisateur
        User::factory()->create(['email' => 'test@example.com']);
        
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    /** @test */
    public function les_factories_fond_fonctionnent(): void
    {
        $fond = Fond::factory()->create();
        
        $this->assertDatabaseHas('fonds', [
            'id' => $fond->id,
            'actif' => true,
        ]);
        
        $this->assertNotNull($fond->type);
        $this->assertNotNull($fond->quantite);
    }

    /** @test */
    public function les_factories_vinyle_fonctionnent(): void
    {
        $vinyle = Vinyle::factory()->create();
        
        $this->assertDatabaseHas('vinyles', [
            'id' => $vinyle->id,
        ]);
        
        $this->assertNotNull($vinyle->reference);
        $this->assertNotNull($vinyle->artiste);
    }

    /** @test */
    public function les_factories_order_fonctionnent(): void
    {
        $order = Order::factory()->ready()->create();
        
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'statut' => 'prete',
        ]);
    }
}
