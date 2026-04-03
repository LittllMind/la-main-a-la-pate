<?php

namespace Tests\Feature\Reports;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vinyle;
use App\Models\Fond;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StockReportTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    public function setUp(): void
    {
        parent::setUp();
        // Créer un admin avec id=1 avant tous les tests pour l'observer
        $this->admin = User::factory()->admin()->create(['id' => 1]);
    }

    /**
     * T13.1-SR-1 : Admin peut voir le rapport de stock global
     */
    public function test_admin_can_view_stock_report(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.stock'));
        
        $response->assertOk();
        $response->assertViewIs('admin.reports.stock');
    }

    /**
     * T13.1-SR-2 : Rapport affiche la valeur totale du stock
     */
    public function test_stock_report_shows_total_value(): void
    {
        Vinyle::factory()->create(['prix' => 20.00, 'quantite' => 5]); // 100€
        Vinyle::factory()->create(['prix' => 30.00, 'quantite' => 2]); // 60€
        Fond::factory()->create(['prix_vente' => 50.00, 'quantite' => 3]); // 150€

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.stock'));

        $response->assertOk();
        $response->assertSee('310'); // Valeur totale
    }

    /**
     * T13.1-SR-3 : Rapport affiche la répartition vinyles vs fonds
     */
    public function test_stock_report_shows_vinyls_vs_fonds_breakdown(): void
    {
        Vinyle::factory()->count(5)->create(['quantite' => 2]);
        Fond::factory()->count(3)->create(['quantite' => 4]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.stock'));

        $response->assertOk();
        $response->assertSee('Vinyles');
        $response->assertSee('Fonds');
        $response->assertSee('10'); // 5 vinyles × 2 quantité
        $response->assertSee('12'); // 3 fonds × 4 quantité
    }

    /**
     * T13.1-SR-4 : Vendeur peut voir le rapport de stock
     */
    public function test_employe_can_view_stock_report(): void
    {
        $employe = User::factory()->employe()->create();
        
        $response = $this->actingAs($employe)
            ->get(route('admin.reports.stock'));
        
        $response->assertOk();
    }

    /**
     * T13.1-SR-5 : Client ne peut pas voir le rapport
     */
    public function test_client_cannot_view_stock_report(): void
    {
        $client = User::factory()->client()->create();
        
        $response = $this->actingAs($client)
            ->get(route('admin.reports.stock'));
        
        $response->assertRedirect('/kiosque');
    }

    /**
     * T13.1-SR-6 : Rapport affiche les alertes de stock bas
     */
    public function test_stock_report_shows_low_stock_alerts(): void
    {
        Vinyle::factory()->create(['quantite' => 2, 'seuil_alerte' => 5]);
        Vinyle::factory()->create(['quantite' => 10, 'seuil_alerte' => 5]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.stock'));

        $response->assertOk();
        $response->assertSee('Stock bas'); // Indication d'alerte
    }

    /**
     * T13.1-SR-7 : Rapport affiche le stock par catégorie (genre/format)
     */
    public function test_stock_report_shows_breakdown_by_category(): void
    {
        Vinyle::factory()->create(['genre' => 'Rock', 'quantite' => 5]);
        Vinyle::factory()->create(['genre' => 'Jazz', 'quantite' => 3]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.stock'));

        $response->assertOk();
        $response->assertSee('Rock');
        $response->assertSee('Jazz');
    }
}
