<?php

namespace Tests\Feature\Ventes;

use App\Models\User;
use App\Models\Vente;
use App\Models\LigneVente;
use App\Models\Vinyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class HistoriqueVentesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un utilisateur employé
        $this->employe = User::factory()->create([
            'role' => 'employe',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Test: Un employé peut voir l'historique des ventes pour aujourd'hui
     * T14.1 — Historique Ventes Jour
     */
    public function test_employe_can_view_sales_history_for_today(): void
    {
        // Arrange: Créer des ventes pour aujourd'hui
        $vinyle = Vinyle::factory()->create(['quantite' => 10]);
        
        $vente = Vente::factory()->create([
            'date' => Carbon::today(),
            'total' => 150.00,
            'mode_paiement' => 'especes',
        ]);
        
        LigneVente::factory()->create([
            'vente_id' => $vente->id,
            'vinyle_id' => $vinyle->id,
            'quantite' => 2,
            'prix_unitaire' => 75.00,
            'total' => 150.00,
        ]);
        
        // Act: Se connecter en tant qu'employé et accéder aux ventes
        $response = $this->actingAs($this->employe)
            ->get(route('ventes.index', ['date' => Carbon::today()->toDateString()]));
        
        // Assert: La page s'affiche avec la vente d'aujourd'hui
        $response->assertStatus(200);
        $response->assertViewIs('ventes.index');
        // Le montant s'affiche avec format français (virgule comme séparateur décimal)
        $response->assertSee('150');
    }

    /**
     * Test: L'historique montre le total correct des ventes
     * T14.1 — Historique Ventes Jour
     */
    public function test_sales_history_shows_correct_total_amount(): void
    {
        // Arrange: Créer plusieurs ventes pour aujourd'hui
        $vinyle1 = Vinyle::factory()->create(['prix' => 50.00]);
        $vinyle2 = Vinyle::factory()->create(['prix' => 30.00]);
        
        // Ventes pour aujourd'hui
        $vente1 = Vente::factory()->create([
            'date' => Carbon::today(),
            'total' => 100.00,
        ]);
        $vente2 = Vente::factory()->create([
            'date' => Carbon::today(),
            'total' => 60.00,
        ]);
        
        LigneVente::factory()->create([
            'vente_id' => $vente1->id,
            'vinyle_id' => $vinyle1->id,
            'total' => 100.00,
        ]);
        
        LigneVente::factory()->create([
            'vente_id' => $vente2->id,
            'vinyle_id' => $vinyle2->id,
            'total' => 60.00,
        ]);
        
        // Act
        $response = $this->actingAs($this->employe)
            ->get(route('ventes.index'));
        
        // Assert: Total doit être 160.00
        $response->assertStatus(200);
        $response->assertViewHas('caTotal', 160.00);
        $response->assertViewHas('ventes', function ($ventes) {
            return $ventes->count() === 2;
        });
    }

    /**
     * Test: L'historique peut être filtré par date
     * T14.1 — Historique Ventes Jour
     */
    public function test_sales_history_can_filter_by_date(): void
    {
        // Arrange: Ventes sur différentes dates
        $vinyle = Vinyle::factory()->create();
        
        $venteHier = Vente::factory()->create([
            'date' => Carbon::yesterday(),
            'total' => 100.00,
            'mode_paiement' => 'especes',
        ]);
        
        $venteAujourdhui = Vente::factory()->create([
            'date' => Carbon::today(),
            'total' => 200.00,
            'mode_paiement' => 'carte',
        ]);
        
        LigneVente::factory()->create([
            'vente_id' => $venteHier->id,
            'vinyle_id' => $vinyle->id,
        ]);
        
        LigneVente::factory()->create([
            'vente_id' => $venteAujourdhui->id,
            'vinyle_id' => $vinyle->id,
        ]);
        
        // Act: Filtrer par date d'hier
        $response = $this->actingAs($this->employe)
            ->get(route('ventes.index', ['date' => Carbon::yesterday()->toDateString()]));
        
        // Assert: Seule la vente d'hier doit apparaître
        $response->assertStatus(200);
        $response->assertViewHas('caTotal', 100.00);
        $response->assertViewHas('ventes', function ($ventes) {
            return $ventes->count() === 1;
        });
        $response->assertViewHas('currentDate', function ($date) {
            return $date->toDateString() === Carbon::yesterday()->toDateString();
        });
    }

    /**
     * Test: L'historique include les détails de chaque transaction
     * T14.1 — Historique Ventes Jour
     */
    public function test_sales_history_includes_transaction_details(): void
    {
        // Arrange: Créer une vente avec plusieurs lignes
        $vinyle1 = Vinyle::factory()->create([
            'artiste' => 'The Beatles',
            'modele' => 'Abbey Road',
            'prix' => 50.00,
        ]);
        
        $vinyle2 = Vinyle::factory()->create([
            'artiste' => 'Pink Floyd',
            'modele' => 'Dark Side',
            'prix' => 30.00,
        ]);
        
        $vente = Vente::factory()->create([
            'date' => Carbon::today(),
            'total' => 130.00,
            'mode_paiement' => 'carte',
        ]);
        
        LigneVente::factory()->create([
            'vente_id' => $vente->id,
            'vinyle_id' => $vinyle1->id,
            'quantite' => 2,
            'prix_unitaire' => 50.00,
            'total' => 100.00,
            'fond' => 'standard',
        ]);
        
        LigneVente::factory()->create([
            'vente_id' => $vente->id,
            'vinyle_id' => $vinyle2->id,
            'quantite' => 1,
            'prix_unitaire' => 30.00,
            'total' => 30.00,
            'fond' => 'miroir',
        ]);
        
        // Act
        $response = $this->actingAs($this->employe)
            ->get(route('ventes.index'));
        
        // Assert: Les détails sont présents
        $response->assertStatus(200);
        $response->assertViewHas('ventes', function ($ventes) {
            $vente = $ventes->first();
            return $vente->lignes->count() === 2;
        });
    }

    /**
     * Test: Un client ne peut pas accéder à l'historique des ventes
     */
    public function test_client_cannot_view_sales_history(): void
    {
        $client = User::factory()->create([
            'role' => 'client',
            'email_verified_at' => now(),
        ]);
        
        $response = $this->actingAs($client)
            ->get(route('ventes.index'));
        
        $response->assertRedirect(route('kiosque.index'));
    }

    /**
     * Test: L'accès nécessite une authentification
     */
    public function test_sales_history_requires_authentication(): void
    {
        $response = $this->get(route('ventes.index'));
        
        $response->assertRedirect(route('login'));
    }
}
