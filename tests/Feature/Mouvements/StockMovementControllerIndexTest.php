<?php

namespace Tests\Feature\Mouvements;

use Tests\TestCase;
use App\Models\User;
use App\Models\Fond;
use App\Models\Vinyle;
use App\Models\MouvementStock;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StockMovementControllerIndexTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_peut_voir_historique_mouvements()
    {
        $admin = $this->adminUser();
        
        // Créer mouvements de test
        MouvementStock::factory()->entree()->create();
        MouvementStock::factory()->sortie()->create();
        
        $response = $this->actingAs($admin)
            ->get(route('mouvements.index'));
        
        $response->assertOk()
            ->assertViewIs('mouvements.index')
            ->assertViewHas(['mouvements', 'stats', 'types', 'produitTypes'])
            ->assertSee('Historique')
            ->assertSee('Total Entrées')
            ->assertSee('Total Sorties');
    }

    /** @test */
    public function employe_peut_voir_historique_mouvements()
    {
        $employe = $this->employeUser();
        
        MouvementStock::factory()->entree()->create();
        
        $response = $this->actingAs($employe)
            ->get(route('mouvements.index'));
        
        $response->assertOk()
            ->assertViewHas('mouvements');
    }

    /** @test */
    public function client_ne_peut_pas_voir_historique_mouvements()
    {
        $client = $this->clientUser();
        
        $response = $this->actingAs($client)
            ->get(route('mouvements.index'));
        
        $response->assertRedirect('/kiosque');
    }

    /** @test */
    public function utilisateur_non_connecte_est_redirige_vers_login()
    {
        $response = $this->get(route('mouvements.index'));
        
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function stats_sont_correctement_calculees()
    {
        $admin = $this->adminUser();
        
        // Créer des mouvements avec dates forcées à aujourd'hui
        MouvementStock::factory()->entree()->create(['quantite' => 10, 'date_mouvement' => now()]);
        MouvementStock::factory()->entree()->create(['quantite' => 15, 'date_mouvement' => now()]);
        MouvementStock::factory()->sortie()->create(['quantite' => 5, 'date_mouvement' => now()]);
        MouvementStock::factory()->sortie()->create(['quantite' => 8, 'date_mouvement' => now()]);
        
        // Calcul attendu
        $expectedEntrees = 10 + 15;  // 25
        $expectedSorties = 5 + 8;    // 13
        $expectedAujourdhui = 4;      // tous nos 4 mouvements
        
        $response = $this->actingAs($admin)
            ->get(route('mouvements.index'));
        
        $stats = $response->viewData('stats');
        
        // Vérifier que les stats incluent nos valeurs (>= expected)
        $this->assertGreaterThanOrEqual($expectedEntrees, $stats['total_entrees']);
        $this->assertGreaterThanOrEqual($expectedSorties, $stats['total_sorties']);
        $this->assertGreaterThanOrEqual($expectedAujourdhui, $stats['aujourdhui']);
    }

    /** @test */
    public function filtre_par_type_entree_fonctionne()
    {
        $admin = $this->adminUser();
        
        $mouvementEntree = MouvementStock::factory()->entree()->create();
        $mouvementSortie = MouvementStock::factory()->sortie()->create();
        
        $response = $this->actingAs($admin)
            ->get(route('mouvements.index', ['type' => 'entree']));
        
        $mouvements = $response->viewData('mouvements');
        
        $this->assertTrue($mouvements->pluck('id')->contains($mouvementEntree->id));
        $this->assertFalse($mouvements->pluck('id')->contains($mouvementSortie->id));
    }

    /** @test */
    public function filtre_par_type_sortie_fonctionne()
    {
        $admin = $this->adminUser();
        
        $mouvementEntree = MouvementStock::factory()->entree()->create();
        $mouvementSortie = MouvementStock::factory()->sortie()->create();
        
        $response = $this->actingAs($admin)
            ->get(route('mouvements.index', ['type' => 'sortie']));
        
        $mouvements = $response->viewData('mouvements');
        
        $this->assertTrue($mouvements->pluck('id')->contains($mouvementSortie->id));
        $this->assertFalse($mouvements->pluck('id')->contains($mouvementEntree->id));
    }

    /** @test */
    public function filtre_par_produit_type_fonctionne()
    {
        $admin = $this->adminUser();
        
        $mouvementVinyle = MouvementStock::factory()->create(['produit_type' => 'vinyle']);
        $mouvementMiroir = MouvementStock::factory()->create(['produit_type' => 'miroir']);
        
        $response = $this->actingAs($admin)
            ->get(route('mouvements.index', ['produit_type' => 'vinyle']));
        
        $mouvements = $response->viewData('mouvements');
        
        $this->assertTrue($mouvements->pluck('id')->contains($mouvementVinyle->id));
        $this->assertFalse($mouvements->pluck('id')->contains($mouvementMiroir->id));
    }

    /** @test */
    public function filtre_par_dates_fonctionne()
    {
        $admin = $this->adminUser();
        
        // Mouvement du 1er mars
        $mouvementAncien = MouvementStock::factory()->create([
            'date_mouvement' => '2026-03-01 10:00:00'
        ]);
        
        // Mouvement du 5 mars
        $mouvementRecent = MouvementStock::factory()->create([
            'date_mouvement' => '2026-03-05 10:00:00'
        ]);
        
        $response = $this->actingAs($admin)
            ->get(route('mouvements.index', [
                'date_debut' => '2026-03-03',
                'date_fin' => '2026-03-10'
            ]));
        
        $mouvements = $response->viewData('mouvements');
        
        $this->assertFalse($mouvements->pluck('id')->contains($mouvementAncien->id));
        $this->assertTrue($mouvements->pluck('id')->contains($mouvementRecent->id));
    }

    /** @test */
    public function recherche_par_reference_fonctionne()
    {
        $admin = $this->adminUser();
        
        $mouvement1 = MouvementStock::factory()->create(['reference' => 'CMD-2026-001']);
        $mouvement2 = MouvementStock::factory()->create(['reference' => 'FOURN-2026-023']);
        
        $response = $this->actingAs($admin)
            ->get(route('mouvements.index', ['search' => 'CMD-2026']));
        
        $mouvements = $response->viewData('mouvements');
        
        $this->assertTrue($mouvements->pluck('id')->contains($mouvement1->id));
        $this->assertFalse($mouvements->pluck('id')->contains($mouvement2->id));
    }

    /** @test */
    public function tri_par_date_decroissante_par_defaut()
    {
        $admin = $this->adminUser();
        
        MouvementStock::factory()->create(['date_mouvement' => now()->subDays(2)]);
        MouvementStock::factory()->create(['date_mouvement' => now()]);
        MouvementStock::factory()->create(['date_mouvement' => now()->subDay()]);
        
        $response = $this->actingAs($admin)
            ->get(route('mouvements.index'));
        
        $mouvements = $response->viewData('mouvements');
        $dates = $mouvements->pluck('date_mouvement')->map(fn($d) => $d->toDateString());
        
        // Vérifier que les dates sont en ordre décroissant
        for ($i = 0; $i < count($dates) - 1; $i++) {
            $this->assertGreaterThanOrEqual(
                $dates[$i + 1], 
                $dates[$i],
                'Les mouvements devraient être triés par date décroissante'
            );
        }
    }

    /** @test */
    public function pagination_affiche_25_utilisateurs_par_page()
    {
        $admin = $this->adminUser();
        
        // Créer 30 mouvements
        MouvementStock::factory()->count(30)->create();
        
        $response = $this->actingAs($admin)
            ->get(route('mouvements.index'));
        
        $mouvements = $response->viewData('mouvements');
        
        $this->assertCount(25, $mouvements->items());
        $this->assertTrue($mouvements->hasMorePages());
    }

    /** @test */
    public function filtres_multiple_fonctionnent_ensemble()
    {
        $admin = $this->adminUser();
        
        $fond = Fond::factory()->create();
        
        // Créer mouvements variés
        MouvementStock::factory()->entree()->create([
            'produit_type' => 'vinyle',
            'date_mouvement' => now()->subDays(5),
            'reference' => 'ENT-001'
        ]);
        
        $mouvementFiltre = MouvementStock::factory()->entree()->create([
            'produit_type' => 'miroir',
            'date_mouvement' => now()->subDay(),
            'reference' => 'FOURN-2026-001'
        ]);
        
        $response = $this->actingAs($admin)
            ->get(route('mouvements.index', [
                'type' => 'entree',
                'produit_type' => 'miroir',
                'date_debut' => now()->subDays(3)->toDateString(),
                'date_fin' => now()->toDateString(),
                'search' => 'FOURN'
            ]));
        
        $mouvements = $response->viewData('mouvements');
        
        $this->assertCount(1, $mouvements->items());
        $this->assertTrue($mouvements->pluck('id')->contains($mouvementFiltre->id));
    }
}