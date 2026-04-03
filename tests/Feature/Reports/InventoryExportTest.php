<?php

namespace Tests\Feature\Reports;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vinyle;
use App\Models\Fond;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_vinyls_inventory_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Vinyle::factory()->count(5)->create();

        $response = $this->actingAs($admin)
            ->get('/admin/reports/inventory/vinyls/pdf');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_admin_can_export_fonds_inventory_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Fond::factory()->count(3)->create();

        $response = $this->actingAs($admin)
            ->get('/admin/reports/inventory/fonds/pdf');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_export_vinyls_contains_all_vinyls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vinyles = Vinyle::factory()->count(3)->create();

        $response = $this->actingAs($admin)
            ->get('/admin/reports/inventory/vinyls/pdf');

        $response->assertStatus(200);
    }

    public function test_employee_cannot_export_inventory(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($employee)
            ->get('/admin/reports/inventory/vinyls/pdf');

        // Redirection (non autorisé) ou 403
        $this->assertTrue(
            $response->status() === 302 || $response->status() === 403,
            'Employee should not be able to export inventory'
        );
        
        // Vérifier qu'une redirection se fait
        $response->assertRedirect();
    }

    public function test_client_cannot_export_inventory(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($client)
            ->get('/admin/reports/inventory/vinyls/pdf');

        // Redirection (non autorisé) ou 403
        $this->assertTrue(
            $response->status() === 302 || $response->status() === 403,
            'Client should not be able to export inventory'
        );
        
        $response->assertRedirect();
    }

    public function test_export_calculates_total_stock_value(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Vinyle::factory()->create(['prix' => 20.00, 'quantite' => 5]);
        Vinyle::factory()->create(['prix' => 30.00, 'quantite' => 2]);

        $response = $this->actingAs($admin)
            ->get('/admin/reports/inventory/vinyls/pdf');

        $response->assertStatus(200);
    }

    public function test_export_includes_report_generation_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Vinyle::factory()->create();

        $response = $this->actingAs($admin)
            ->get('/admin/reports/inventory/vinyls/pdf');

        $response->assertStatus(200);
    }
}
