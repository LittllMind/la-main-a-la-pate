<?php

namespace Tests\Feature\Fonds;

use Tests\TestCase;
use App\Models\Fond;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FondControllerIndexTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_peut_voir_dashboard_fonds()
    {
        $admin = $this->adminUser();
        
        // Créer fonds de test avec types uniques
        Fond::factory()->count(3)->create();
        
        $response = $this->actingAs($admin)
            ->get(route('fonds.index'));
        
        $response->assertOk()
            ->assertViewIs('fonds.index')
            ->assertViewHas(['fonds', 'totaux']);
    }

    /** @test */
    public function employe_peut_voir_dashboard_fonds()
    {
        $employe = $this->employeUser();
        
        // Créer un fond de test
        Fond::factory()->create(['quantite' => 10]);
        
        $response = $this->actingAs($employe)
            ->get(route('fonds.index'));
        
        $response->assertOk()
            ->assertViewHas('fonds');
    }

    /** @test */
    public function client_ne_peut_pas_voir_dashboard_fonds()
    {
        $client = $this->clientUser();
        
        $response = $this->actingAs($client)
            ->get(route('fonds.index'));
        
        // Redirection vers login (middleware role)
        $response->assertRedirect();
    }

    /** @test */
    public function utilisateur_non_connecte_est_redirige_vers_login()
    {
        $response = $this->get(route('fonds.index'));
        
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function totaux_sont_correctement_calcules()
    {
        $admin = $this->adminUser();
        
        // Créer 2 fonds avec quantités connues
        Fond::factory()->create([
            'quantite' => 10,
            'prix_achat' => 5,
            'prix_vente' => 10
        ]);
        
        Fond::factory()->create([
            'quantite' => 5,
            'prix_achat' => 8,
            'prix_vente' => 15
        ]);

        $response = $this->actingAs($admin)
            ->get(route('fonds.index'));

        $totaux = $response->viewData('totaux');
        
        // Vérifier les calculs
        $this->assertEquals(15, $totaux['quantite_totale']); // 10 + 5
        $this->assertEquals((10 * 5) + (5 * 8), $totaux['montant_investi']); // Montant stock
        $this->assertEquals((10 * 10) + (5 * 15), $totaux['valeur_totale']); // Valeur stock
    }

    /** @test */
    public function fonds_ont_statuts_corrects()
    {
        $admin = $this->adminUser();
        
        // Fond en rupture
        Fond::factory()->create(['quantite' => 0]);
        
        // Fond stock faible
        Fond::factory()->create(['quantite' => 3]);
        
        // Fond OK
        Fond::factory()->create(['quantite' => 20]);

        $response = $this->actingAs($admin)
            ->get(route('fonds.index'));

        $fonds = $response->viewData('fonds')->sortBy('quantite')->values();
        
        $this->assertEquals('Rupture', $fonds[0]['status']);
        $this->assertEquals('Alerte', $fonds[1]['status']);
        $this->assertEquals('OK', $fonds[2]['status']);
    }

    /** @test */
    public function admin_voit_menu_dashboard()
    {
        $admin = $this->adminUser();
        Fond::factory()->create();

        $response = $this->actingAs($admin)
            ->get(route('fonds.index'));

        // Vérifier présence du menu admin
        $response->assertSee('🔧 Dashboard');
    }

    /** @test */
    public function employe_voit_dashboard_mais_pas_mode_admin()
    {
        $employe = $this->employeUser();
        Fond::factory()->create();

        $response = $this->actingAs($employe)
            ->get(route('fonds.index'));

        // Les employés voient le tableau
        $response->assertOk()
            ->assertViewHas('fonds');
        
        // Mais pas le mode admin
        $response->assertDontSee('Mode Admin');
    }

    /** @test */
    public function test_fond_dashboard_affiche_prix_achat()
    {
        $admin = $this->adminUser();
        
        // Créer exactement un fond avec des valeurs spécifiques
        Fond::factory()->create([
            'prix_achat' => 5.50,
            'prix_vente' => 12.00,
            'quantite' => 1
        ]);

        $response = $this->actingAs($admin)
            ->get(route('fonds.index'));

        $fonds = $response->viewData('fonds');
        
        // Il n'y a qu'un seul fond, vérifier ses données
        $this->assertCount(1, $fonds);
        $fondVue = $fonds->first();
        
        $this->assertEquals(5.50, $fondVue['prix_achat']);
        $this->assertEquals(12.00, $fondVue['prix_vente']);
        $this->assertEquals(1 * (12.00 - 5.50), $fondVue['marge']);
    }
}
