<?php

namespace Tests\Feature\ModeMarche;

use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Vinyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesHistoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Créer une commande mode marché pour les tests
     */
    private function createMarcheOrder(array $overrides = []): Order
    {
        return Order::factory()->create(array_merge([
            'source' => 'marche',
            'statut' => 'payee',
            'mode_paiement_marche' => 'cb_terminal',
        ], $overrides));
    }

    /**
     * Test: Un employé peut voir l'historique des ventes du jour
     * T14.1.1
     */
    public function test_employe_can_view_sales_history_for_today(): void
    {
        // Arrange: Créer un employé et des ventes
        $employe = User::factory()->create(['role' => 'employe']);
        
        // Créer une vente d'aujourd'hui
        $venteAujourdhui = $this->createMarcheOrder([
            'created_at' => now(),
            'total' => 100.00,
            'mode_paiement_marche' => 'cash',
        ]);

        // Créer une vente d'hier
        $venteHier = $this->createMarcheOrder([
            'created_at' => now()->subDay(),
            'total' => 50.00,
            'mode_paiement_marche' => 'cb_terminal',
        ]);

        // Act: L'employé accède à la page des ventes
        $response = $this->actingAs($employe)
            ->get(route('marche.ventes-jour'));

        // Assert: La réponse est OK et contient les ventes du jour
        $response->assertStatus(200);
        $response->assertViewIs('marche.ventes-jour');
        $response->assertViewHas('ventes');
        
        // Vérifier que la vente d'aujourd'hui est présente
        $ventes = $response->viewData('ventes');
        $this->assertTrue($ventes->contains('id', $venteAujourdhui->id));
        
        // Vérifier que la vente d'hier n'est PAS présente
        $this->assertFalse($ventes->contains('id', $venteHier->id));
    }

    /**
     * Test: L'historique affiche le montant total correct
     * T14.1.2
     */
    public function test_sales_history_shows_correct_total_amount(): void
    {
        // Arrange: Créer un employé et plusieurs ventes
        $employe = User::factory()->create(['role' => 'employe']);
        
        $vente1 = $this->createMarcheOrder([
            'created_at' => now(),
            'total' => 120.00,
            'mode_paiement_marche' => 'cash',
        ]);
        
        $vente2 = $this->createMarcheOrder([
            'created_at' => now(),
            'total' => 80.50,
            'mode_paiement_marche' => 'cb_terminal',
        ]);

        // Act: L'employé accède à la page des ventes
        $response = $this->actingAs($employe)
            ->get(route('marche.ventes-jour'));

        // Assert: La réponse contient les montants corrects
        $response->assertStatus(200);
        $response->assertViewHas('totalJour');
        
        $totalJour = $response->viewData('totalJour');
        $this->assertEquals(200.50, $totalJour);
    }

    /**
     * Test: L'historique peut être filtré par date
     * T14.1.3
     */
    public function test_sales_history_can_filter_by_date(): void
    {
        // Arrange: Créer un employé et des ventes sur différentes dates
        $employe = User::factory()->create(['role' => 'employe']);
        
        $dateCible = '2026-03-10';
        $dateAutre = '2026-03-15';
        
        $venteDateCible = $this->createMarcheOrder([
            'created_at' => $dateCible . ' 10:00:00',
            'total' => 150.00,
            'mode_paiement_marche' => 'cash',
        ]);
        
        $venteDateAutre = $this->createMarcheOrder([
            'created_at' => $dateAutre . ' 14:00:00',
            'total' => 75.00,
            'mode_paiement_marche' => 'cb_terminal',
        ]);

        // Act: L'employé filtre par une date spécifique
        $response = $this->actingAs($employe)
            ->get(route('marche.ventes-jour', ['date' => $dateCible]));

        // Assert: Seule la vente de la date cible est affichée
        $response->assertStatus(200);
        $response->assertViewHas('ventes');
        
        $ventes = $response->viewData('ventes');
        $this->assertTrue($ventes->contains('id', $venteDateCible->id));
        $this->assertFalse($ventes->contains('id', $venteDateAutre->id));
        
        // Vérifier que la date sélectionnée est dans la vue
        $response->assertViewHas('dateSelectionnee');
        $dateSelectionnee = $response->viewData('dateSelectionnee');
        $this->assertEquals($dateCible, $dateSelectionnee->toDateString());
    }

    /**
     * Test: L'historique inclut les détails des transactions
     * T14.1.4
     */
    public function test_sales_history_includes_transaction_details(): void
    {
        // Arrange: Créer un employé, un vinyle et une commande avec des items
        $employe = User::factory()->create(['role' => 'employe']);
        $vinyle = Vinyle::factory()->create();
        
        $order = $this->createMarcheOrder([
            'created_at' => now(),
            'total' => 50.00,
            'mode_paiement_marche' => 'cb_terminal',
        ]);
        
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'vinyle_id' => $vinyle->id,
            'titre_vinyle' => $vinyle->modele,
            'artiste_vinyle' => $vinyle->artiste ?? 'Artiste inconnu',
            'quantite' => 2,
            'prix_unitaire' => 25.00,
            'total' => 50.00,
        ]);

        // Act: L'employé accède à la page des ventes
        $response = $this->actingAs($employe)
            ->get(route('marche.ventes-jour'));

        // Assert: La réponse contient les détails de la transaction
        $response->assertStatus(200);
        $response->assertViewHas('ventes');
        
        $ventes = $response->viewData('ventes');
        $venteAffichee = $ventes->first();
        
        // Vérifier que les items sont chargés (eager loading)
        $this->assertTrue($venteAffichee->relationLoaded('items'));
        
        // Vérifier que l'item contient les bonnes informations
        $item = $venteAffichee->items->first();
        $this->assertEquals(2, $item->quantite);
        $this->assertEquals(25.00, $item->prix_unitaire);
        $this->assertEquals(50.00, $item->total);
    }

    /**
     * Test: Un client ne peut pas voir l'historique des ventes
     * T14.1.5 - Test de sécurité
     */
    public function test_client_cannot_view_sales_history(): void
    {
        // Arrange: Créer un client
        $client = User::factory()->create(['role' => 'client']);

        // Act: Le client tente d'accéder à la page des ventes
        // Pour obtenir une 403, il faut une requête AJAX/JSON
        $response = $this->actingAs($client)
            ->getJson(route('marche.ventes-jour'));

        // Assert: Le client reçoit une erreur 403
        $response->assertStatus(403);
    }

    /**
     * Test: Un visiteur non connecté ne peut pas voir l'historique
     * T14.1.6 - Test de sécurité
     */
    public function test_guest_cannot_view_sales_history(): void
    {
        // Act: Un visiteur non connecté tente d'accéder
        $response = $this->get(route('marche.ventes-jour'));

        // Assert: Redirection vers la page de connexion
        $response->assertRedirect(route('login'));
    }
}
