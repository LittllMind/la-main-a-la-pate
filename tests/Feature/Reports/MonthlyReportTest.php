<?php

namespace Tests\Feature\Reports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Vinyle;
use App\Models\Fond;
use Carbon\Carbon;

class MonthlyReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * T12.3-A-1 : Admin peut générer un rapport mensuel PDF
     */
    public function test_admin_can_generate_monthly_report_pdf(): void
    {
        $admin = User::factory()->admin()->create();
        
        $response = $this->actingAs($admin)
            ->post(route('admin.reports.monthly.generate'), [
                'month' => now()->format('Y-m')
            ]);
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * T12.3-A-2 : Rapport contient les ventes du mois
     */
    public function test_monthly_report_contains_sales_data(): void
    {
        $admin = User::factory()->admin()->create();
        $thisMonth = now()->format('Y-m');
        
        // Créer des commandes livrées ce mois
        Order::factory()->count(3)->create([
            'statut' => 'livree',
            'created_at' => now(),
            'total' => 150.00
        ]);
        
        $response = $this->actingAs($admin)
            ->post(route('admin.reports.monthly.generate'), [
                'month' => $thisMonth
            ]);
        
        $response->assertStatus(200);
        $content = $response->getContent();
        
        // Vérifier présence données dans le PDF (texte brut du PDF)
        $this->assertStringContainsString('VENTES', $content);
        $this->assertStringContainsString('450', $content); // 3×150
    }

    /**
     * T12.3-A-3 : Vendeur peut générer un rapport
     */
    public function test_vendeur_can_generate_monthly_report(): void
    {
        $vendeur = User::factory()->employe()->create();
        
        $response = $this->actingAs($vendeur)
            ->post(route('admin.reports.monthly.generate'), [
                'month' => now()->format('Y-m')
            ]);
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * T12.3-A-4 : Client ne peut pas générer de rapport
     */
    public function test_client_cannot_generate_monthly_report(): void
    {
        $client = User::factory()->client()->create();
        
        $response = $this->actingAs($client)
            ->post(route('admin.reports.monthly.generate'), [
                'month' => now()->format('Y-m')
            ]);
        
        $response->assertRedirect('/kiosque');
    }

    /**
     * T12.3-A-5 : Rapport inclut les KPI globaux
     */
    public function test_monthly_report_includes_global_kpis(): void
    {
        $admin = User::factory()->admin()->create();
        $thisMonth = now()->format('Y-m');
        
        // Créer stock
        Vinyle::factory()->create(['quantite' => 100]);
        Fond::factory()->create(['quantite' => 50]);
        
        // Commandes
        Order::factory()->create([
            'statut' => 'livree',
            'total' => 1000,
            'created_at' => now()
        ]);
        
        $response = $this->actingAs($admin)
            ->post(route('admin.reports.monthly.generate'), [
                'month' => $thisMonth
            ]);
        
        $response->assertStatus(200);
        $content = $response->getContent();
        
        // Vérifier présence des sections
        $this->assertStringContainsString('INVENTAIRE', $content);
        $this->assertStringContainsString('Vinyles en stock', $content);
        $this->assertStringContainsString('100', $content);
    }

    /**
     * T12.3-A-6 : Route GET affiche formulaire
     */
    public function test_monthly_report_form_is_accessible(): void
    {
        $admin = User::factory()->admin()->create();
        
        $response = $this->actingAs($admin)
            ->get(route('admin.reports.monthly'));
        
        $response->assertOk();
        $response->assertViewIs('admin.reports.monthly-form');
    }

    /**
     * T12.3-A-7 : Validation des paramètres requis
     */
    public function test_monthly_report_validates_parameters(): void
    {
        $admin = User::factory()->admin()->create();
        
        $response = $this->actingAs($admin)
            ->post(route('admin.reports.monthly.generate'), [
                'month' => 'invalid-format'
            ]);
        
        $response->assertSessionHasErrors('month');
    }

    /**
     * T12.3-A-8 : Rapport inclut l'historique des mouvements
     */
    public function test_monthly_report_includes_mouvements(): void
    {
        $admin = User::factory()->admin()->create();
        $thisMonth = now()->format('Y-m');
        
        // Créer mouvements de stock
        \App\Models\MouvementStock::factory()->count(3)->create([
            'type' => 'entree',
            'created_at' => now()
        ]);
        
        $response = $this->actingAs($admin)
            ->post(route('admin.reports.monthly.generate'), [
                'month' => $thisMonth
            ]);
        
        $response->assertStatus(200);
        $content = $response->getContent();
        
        // Vérifier section mouvements
        $this->assertStringContainsString('MOUVEMENTS DE STOCK', $content);
    }
}
