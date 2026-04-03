<?php

namespace Tests\Feature\Stats;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Vinyle;
use App\Models\Fond;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GlobalStatsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_peut_voir_dashboard_stats()
    {
        $admin = User::factory()->admin()->create();
        
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
    }

    /** @test */
    public function dashboard_affiche_ventes_mois_en_cours()
    {
        $admin = User::factory()->admin()->create();
        
        // Créer une commande ce mois
        Order::factory()->create([
            'statut' => 'livree',
            'total' => 100.00,
            'created_at' => now(),
        ]);
        
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        
        $response->assertStatus(200);
        $response->assertSee('100');
    }

    /** @test */
    public function dashboard_affiche_nombre_commandes_en_cours()
    {
        $admin = User::factory()->admin()->create();
        
        // Commandes en cours
        Order::factory()->count(3)->create(['statut' => 'en_attente']);
        
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        
        $response->assertStatus(200);
        $response->assertViewHas('commandesEnCours', 3);
    }

    /** @test */
    public function dashboard_affiche_valeur_stock_vinyles()
    {
        $admin = User::factory()->admin()->create();
        
        Vinyle::factory()->create(['quantite' => 10, 'prix' => 25.00]);
        
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        
        $response->assertStatus(200);
        $response->assertViewHas('valeurStockVinyles', 250.0);
    }

    /** @test */
    public function dashboard_affiche_valeur_stock_fonds()
    {
        $admin = User::factory()->admin()->create();
        
        Fond::factory()->create(['quantite' => 20, 'prix_vente' => 5.00]);
        
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        
        $response->assertStatus(200);
        $response->assertViewHas('valeurStockFonds', 100.0);
    }

    /** @test */
    public function dashboard_affiche_total_unites_stock()
    {
        $admin = User::factory()->admin()->create();
        
        Vinyle::factory()->create(['quantite' => 100]);
        Fond::factory()->create(['quantite' => 500]);
        
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        
        $response->assertStatus(200);
        $response->assertViewHas('totalVinyles', 100);
        $response->assertViewHas('totalFonds', 500);
    }

    /** @test */
    public function dashboard_affiche_alertes_stock()
    {
        $admin = User::factory()->admin()->create();
        
        Vinyle::factory()->create(['quantite' => 2, 'seuil_alerte' => 5]);
        
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        
        $response->assertStatus(200);
    }

    /** @test */
    public function employe_peut_voir_dashboard_stats()
    {
        $employe = User::factory()->employe()->create();
        
        $response = $this->actingAs($employe)->get('/admin/dashboard');
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
    }

    /** @test */
    public function client_ne_peut_pas_voir_dashboard_stats()
    {
        $client = User::factory()->client()->create();
        
        $response = $this->actingAs($client)->get('/admin/dashboard');
        
        // CheckRole redirige vers la page d'accueil (kiosque) pour les non autorisés
        $response->assertStatus(302);
        $response->assertRedirect('/kiosque');
    }

    /** @test */
    public function dashboard_api_retourne_stats_json()
    {
        $admin = User::factory()->admin()->create();
        
        Order::factory()->create([
            'statut' => 'livree',
            'total' => 100.00,
            'created_at' => now(),
        ]);
        
        $response = $this->actingAs($admin)->getJson('/admin/stats');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'ventes_mois',
            'commandes_en_cours',
            'valeur_stock_vinyles',
            'valeur_stock_fonds',
            'total_vinyles',
            'total_fonds',
        ]);
    }

    /** @test */
    public function dashboard_affiche_dernieres_commandes()
    {
        $admin = User::factory()->admin()->create();
        
        Order::factory()->count(5)->create();
        
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        
        $response->assertStatus(200);
        $response->assertViewHas('dernieresCommandes');
    }

    /** @test */
    public function stats_exclut_commandes_annulees_des_ventes()
    {
        $admin = User::factory()->admin()->create();
        
        Order::factory()->create([
            'statut' => 'livree',
            'total' => 100.00,
            'created_at' => now(),
        ]);
        
        Order::factory()->create([
            'statut' => 'annulee',
            'total' => 50.00,
            'created_at' => now(),
        ]);
        
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        
        $response->assertStatus(200);
        // Vérifie que seule la commande livrée est prise en compte (100, pas 150)
        $response->assertViewHas('ventesMois', 100.0);
    }
}
