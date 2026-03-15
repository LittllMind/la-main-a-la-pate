<?php

namespace Tests\Feature\Mouvements;

use Tests\TestCase;
use App\Models\User;
use App\Models\Fond;
use App\Models\Vinyle;
use App\Models\MouvementStock;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StockMovementControllerExportTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_peut_exporter_mouvements_en_csv()
    {
        $admin = $this->adminUser();
        
        // Créer un mouvement complet avec utilisateur lié
        MouvementStock::factory()->create([
            'user_id' => $admin->id
        ]);
        
        $response = $this->actingAs($admin)
            ->get(route('mouvements.export'));
        
        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('mouvements_stock_', $contentDisposition);
    }

    /** @test */
    public function employe_peut_exporter_mouvements_en_csv()
    {
        $employe = $this->employeUser();
        
        MouvementStock::factory()->create();
        
        $response = $this->actingAs($employe)
            ->get(route('mouvements.export'));
        
        $response->assertOk();
    }

    /** @test */
    public function client_ne_peut_pas_exporter_csv()
    {
        $client = $this->clientUser();
        
        $response = $this->actingAs($client)
            ->get(route('mouvements.export'));
        
        $response->assertRedirect('/kiosque');
    }

    /** @test */
    public function export_respecte_les_filtres()
    {
        $admin = $this->adminUser();
        
        // Mouvements de test
        MouvementStock::factory()->entree()->create(['date_mouvement' => now()->subDays(5)]);
        MouvementStock::factory()->sortie()->create(['date_mouvement' => now()]);
        MouvementStock::factory()->entree()->create(['date_mouvement' => now()->subDays(3)]);
        
        $response = $this->actingAs($admin)
            ->get(route('mouvements.export', [
                'type' => 'entree',
                'date_debut' => now()->subDays(4)->toDateString(),
                'date_fin' => now()->toDateString()
            ]));
        
        $content = $response->streamedContent();
        $lines = explode("\n", trim($content));
        
        // Headers + 1 seul mouvement (entree du -3 jours)
        $this->assertCount(2, $lines);
    }

    /** @test */
    public function export_csv_contient_bons_headers()
    {
        $admin = $this->adminUser();
        
        MouvementStock::factory()->create();
        
        $response = $this->actingAs($admin)
            ->get(route('mouvements.export'));
        
        $content = $response->streamedContent();
        // Enlever le BOM UTF-8 si présent
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $lines = explode("\n", trim($content));
        $headers = str_getcsv($lines[0]);
        
        $expectedHeaders = [
            'Date',
            'Type',
            'Produit',
            'ID Produit',
            'Quantité',
            'Utilisateur',
            'Référence',
            'Notes'
        ];
        
        $this->assertEquals($expectedHeaders, $headers);
    }

    /** @test */
    public function export_csv_contient_donnees_correctes()
    {
        $admin = $this->adminUser();
        
        $fond = Fond::factory()->create([
            'type' => 'miroir',
            'quantite' => 10
        ]);
        
        $mouvement = MouvementStock::factory()->entree()->create([
            'produit_type' => 'miroir',
            'produit_id' => $fond->id,
            'quantite' => 10,
            'reference' => 'CMD-2026-001',
            'notes' => 'Test note',
            'date_mouvement' => '2026-03-05 14:30:00',
            'user_id' => $admin->id
        ]);
        
        $response = $this->actingAs($admin)
            ->get(route('mouvements.export'));
        
        $content = $response->streamedContent();
        $lines = explode("\n", trim($content));
        
        // Vérifier la ligne de données (2ème ligne)
        $dataRow = str_getcsv($lines[1]);
        
        $this->assertStringContainsString('05/03/2026', $dataRow[0]); // Format d/m/Y H:i
        $this->assertEquals('Entrée', $dataRow[1]);
        $this->assertStringContainsString('Fond Miroir', $dataRow[2]);
        $this->assertEquals($fond->id, (int)$dataRow[3]);
        $this->assertEquals(10, (int)$dataRow[4]);
        $this->assertEquals($admin->name, $dataRow[5]);
        $this->assertEquals('CMD-2026-001', $dataRow[6]);
        $this->assertEquals('Test note', $dataRow[7]);
    }

    /** @test */
    public function export_csv_genere_nom_fichier_avec_date()
    {
        $admin = $this->adminUser();
        
        MouvementStock::factory()->create();
        
        $response = $this->actingAs($admin)
            ->get(route('mouvements.export'));
        
        $contentDisposition = $response->headers->get('Content-Disposition');
        
        $this->assertMatchesRegularExpression(
            '/mouvements_stock_\d{4}-\d{2}-\d{2}\.csv/',
            $contentDisposition
        );
    }

    /** @test */
    public function export_csv_vide_retourne_headers_seulement()
    {
        $admin = $this->adminUser();
        
        $response = $this->actingAs($admin)
            ->get(route('mouvements.export'));
        
        $response->assertOk();
        
        $content = $response->streamedContent();
        $lines = array_filter(explode("\n", $content));
        
        $this->assertCount(1, $lines); // Seulement les headers
    }
}