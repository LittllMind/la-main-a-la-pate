<?php

namespace Tests\Feature\Stats;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChartsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function endpoint_api_charts_retourne_ventes_sur_12_mois()
    {
        $admin = User::factory()->admin()->create();
        
        // Créer des commandes sur différents mois
        Order::factory()->create([
            'statut' => 'livree',
            'total' => 100.00,
            'created_at' => now()->subMonths(2),
        ]);
        
        Order::factory()->create([
            'statut' => 'livree',
            'total' => 150.00,
            'created_at' => now()->subMonth(),
        ]);
        
        $response = $this->actingAs($admin)->getJson('/admin/stats/charts');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'ventes_12_mois' => [],
        ]);
        
        // Vérifie que le tableau contient les données
        $data = $response->json('ventes_12_mois');
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(12, count($data)); // Au moins 12 mois
    }

    /** @test */
    public function endpoint_charts_exclut_commandes_annulees()
    {
        $admin = User::factory()->admin()->create();
        
        // Commande livrée ce mois
        Order::factory()->create([
            'statut' => 'livree',
            'total' => 100.00,
            'created_at' => now(),
        ]);
        
        // Commande annulée ce mois (ne doit pas compter)
        Order::factory()->create([
            'statut' => 'annulee',
            'total' => 50.00,
            'created_at' => now(),
        ]);
        
        $response = $this->actingAs($admin)->getJson('/admin/stats/charts');
        
        $response->assertStatus(200);
        
        // Recherche le mois courant dans les données
        $ventes12Mois = $response->json('ventes_12_mois');
        $moisCourant = now()->format('Y-m');
        
        $moisCourantData = collect($ventes12Mois)
            ->first(fn($m) => $m['mois'] === $moisCourant);
        
        // Doit contenir seulement 100€ (pas 150€)
        $this->assertNotNull($moisCourantData);
        $this->assertEquals(100.00, $moisCourantData['montant']);
    }

    /** @test */
    public function endpoint_charts_retourne_evolution_stock_vinyles()
    {
        $admin = User::factory()->admin()->create();
        
        $response = $this->actingAs($admin)->getJson('/admin/stats/charts');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'evolution_stock_vinyles' => [],
        ]);
    }

    /** @test */
    public function endpoint_charts_retourne_evolution_stock_fonds()
    {
        $admin = User::factory()->admin()->create();
        
        $response = $this->actingAs($admin)->getJson('/admin/stats/charts');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'evolution_stock_fonds' => [],
        ]);
    }

    /** @test */
    public function employe_peut_acceder_aux_charts()
    {
        $employe = User::factory()->employe()->create();
        
        $response = $this->actingAs($employe)->getJson('/admin/stats/charts');
        
        $response->assertStatus(200);
    }

    /** @test */
    public function client_ne_peut_pas_acceder_aux_charts()
    {
        $client = User::factory()->client()->create();
        
        $response = $this->actingAs($client)->getJson('/admin/stats/charts');
        
        $response->assertStatus(403); // Forbidden
    }

    /** @test */
    public function guest_ne_peut_pas_acceder_aux_charts()
    {
        $response = $this->getJson('/admin/stats/charts');
        
        $response->assertStatus(401); // Unauthorized
    }
}
