<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\Vinyle;
use App\Models\Fond;
use App\Models\Order;
use App\Models\User;
use App\Models\LigneCommande;
use App\Models\MouvementStock;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MouvementsStockIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function creation_vinyle_genere_entree_auto()
    {
        $vinyle = Vinyle::create([
            'titre' => 'Test Album',
            'artiste' => 'Test Artist',
            'stock' => 10
        ]);

        $this->assertDatabaseHas('mouvements_stock', [
            'type' => 'entree',
            'produit_type' => 'vinyle',
            'produit_id' => $vinyle->id,
            'quantite' => 10
        ]);
    }

    /** @test */
    public function modification_stock_vinyle_genere_mouvement()
    {
        $vinyle = Vinyle::create([
            'titre' => 'Test Album',
            'stock' => 10
        ]);

        // Augmentation stock
        $vinyle->stock = 15;
        $vinyle->save();

        $this->assertDatabaseHas('mouvements_stock', [
            'type' => 'entree',
            'quantite' => 5,
            'notes' => 'Mise à jour stock : Test Album (10 → 15)'
        ]);
    }

    /** @test */
    public function reduction_stock_vinyle_genere_sortie()
    {
        $vinyle = Vinyle::create([
            'titre' => 'Test Album',
            'stock' => 10
        ]);

        // Réduction stock
        $vinyle->stock = 8;
        $vinyle->save();

        $this->assertDatabaseHas('mouvements_stock', [
            'type' => 'sortie',
            'quantite' => 2,
            'notes' => 'Mise à jour stock : Test Album (10 → 8)'
        ]);
    }

    /** @test */
    public function suppression_vinyle_genere_sortie_totale()
    {
        $vinyle = Vinyle::create([
            'titre' => 'Test Album',
            'stock' => 10
        ]);

        $vinyle->delete();

        $this->assertDatabaseHas('mouvements_stock', [
            'type' => 'sortie',
            'produit_type' => 'vinyle',
            'quantite' => 10
        ]);
    }

    /** @test */
    public function modification_fond_miroir_genere_mouvement()
    {
        $fond = Fond::create([
            'type' => 'miroir',
            'stock_miroir' => 5
        ]);

        // Modifier le stock
        $fond->stock_miroir = 10;
        $fond->save();

        $this->assertDatabaseHas('mouvements_stock', [
            'type' => 'entree',
            'produit_type' => 'miroir',
            'quantite' => 5
        ]);
    }

    /** @test */
    public function commande_validee_genere_sorties()
    {
        $user = User::factory()->create();
        $vinyle = Vinyle::create([
            'titre' => 'Test Album',
            'stock' => 10
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'statut' => 'en_cours',
            'total' => 20
        ]);

        LigneCommande::create([
            'order_id' => $order->id,
            'vinyle_id' => $vinyle->id,
            'quantite' => 2,
            'prix_unitaire' => 10
        ]);

        // Valider la commande
        $order->statut = 'validee';
        $order->save();

        $this->assertDatabaseHas('mouvements_stock', [
            'type' => 'sortie',
            'produit_type' => 'vinyle',
            'produit_id' => $vinyle->id,
            'quantite' => 2,
            'reference' => $order->numero_commande ?? 'CMD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT)
        ]);
    }

    /** @test */
    public function commande_annulee_apres_validation_genere_entree()
    {
        $user = User::factory()->create();
        $vinyle = Vinyle::create([
            'titre' => 'Test Album',
            'stock' => 10
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'statut' => 'en_cours',
            'total' => 20
        ]);

        LigneCommande::create([
            'order_id' => $order->id,
            'vinyle_id' => $vinyle->id,
            'quantite' => 2,
            'prix_unitaire' => 10
        ]);

        // Valider puis annuler
        $order->statut = 'validee';
        $order->save();

        $order->statut = 'annulee';
        $order->save();

        $this->assertDatabaseHas('mouvements_stock', [
            'type' => 'entree',
            'produit_type' => 'vinyle',
            'produit_id' => $vinyle->id,
            'quantite' => 2,
            'reference' => 'RET-' . $order->numero_commande
        ]);
    }

    /** @test */
    public function changement_stock_identique_ne_genere_pas_mouvement()
    {
        $vinyle = Vinyle::create([
            'titre' => 'Test Album',
            'stock' => 10
        ]);

        $countBefore = MouvementStock::where('produit_type', 'vinyle')
            ->where('produit_id', $vinyle->id)
            ->count();

        // Sauvegarder sans changer le stock
        $vinyle->titre = 'Nouveau titre';
        $vinyle->save();

        $countAfter = MouvementStock::where('produit_type', 'vinyle')
            ->where('produit_id', $vinyle->id)
            ->count();

        $this->assertEquals($countBefore, $countAfter);
    }

    /** @test */
    public function stats_periode_retourne_bon_resultat()
    {
        // Créer des mouvements
        MouvementStock::enregistrer('entree', 'vinyle', 1, 10, 1);
        MouvementStock::enregistrer('entree', 'vinyle', 2, 5, 1);
        MouvementStock::enregistrer('sortie', 'vinyle', 1, 3, 1);

        $stats = MouvementStock::statsPeriode(now()->subDays(30), now());

        $this->assertEquals(15, $stats->total_entrees);
        $this->assertEquals(3, $stats->total_sorties);
        $this->assertEquals(12, $stats->balance);
    }
}
