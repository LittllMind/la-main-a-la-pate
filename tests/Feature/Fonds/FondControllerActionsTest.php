<?php

namespace Tests\Feature\Fonds;

use Tests\TestCase;
use App\Models\Fond;
use App\Models\User;
use App\Models\MouvementStock;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FondControllerActionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_peut_incrementer_stock_via_dashboard()
    {
        $admin = $this->adminUser();
        $fond = Fond::factory()->create(['quantite' => 10]);

        $response = $this->actingAs($admin)
            ->patch(route('fonds.updateStock', $fond), [
                'action' => 'increment',
                'quantite' => 5
            ]);

        $response->assertRedirect(route('fonds.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('fonds', [
            'id' => $fond->id,
            'quantite' => 15 // 10 + 5
        ]);
    }

    /** @test */
    public function admin_peut_decrementer_stock_via_dashboard()
    {
        $admin = $this->adminUser();
        $fond = Fond::factory()->create(['quantite' => 10]);

        $response = $this->actingAs($admin)
            ->patch(route('fonds.updateStock', $fond), [
                'action' => 'decrement',
                'quantite' => 3
            ]);

        $response->assertRedirect(route('fonds.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('fonds', [
            'id' => $fond->id,
            'quantite' => 7 // 10 - 3
        ]);
    }

    /** @test */
    public function admin_peut_definir_stock_via_dashboard()
    {
        $admin = $this->adminUser();
        $fond = Fond::factory()->create(['quantite' => 10]);

        $response = $this->actingAs($admin)
            ->patch(route('fonds.updateStock', $fond), [
                'action' => 'set',
                'quantite' => 25
            ]);

        $response->assertRedirect(route('fonds.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('fonds', [
            'id' => $fond->id,
            'quantite' => 25
        ]);
    }

    /** @test */
    public function decrement_echoue_si_stock_insuffisant()
    {
        $admin = $this->adminUser();
        $fond = Fond::factory()->create(['quantite' => 5]);

        $response = $this->actingAs($admin)
            ->patch(route('fonds.updateStock', $fond), [
                'action' => 'decrement',
                'quantite' => 10
            ]);

        $response->assertRedirect(route('fonds.index'))
            ->assertSessionHas('error', 'Stock insuffisant pour cette sortie');

        // Le stock ne doit pas avoir changé
        $this->assertDatabaseHas('fonds', [
            'id' => $fond->id,
            'quantite' => 5
        ]);
    }

    /** @test */
    public function employe_ne_peut_pas_modifier_stock()
    {
        $employe = $this->employeUser();
        $fond = Fond::factory()->create(['quantite' => 10]);

        $response = $this->actingAs($employe)
            ->patch(route('fonds.updateStock', $fond), [
                'action' => 'increment',
                'quantite' => 5
            ]);

        // Le middleware redirige vers kiosque (pas de permission)
        $response->assertRedirect();

        // Le stock ne doit pas avoir changé
        $this->assertDatabaseHas('fonds', [
            'id' => $fond->id,
            'quantite' => 10
        ]);
    }

    /** @test */
    public function client_ne_peut_pas_modifier_stock()
    {
        $client = $this->clientUser();
        $fond = Fond::factory()->create(['quantite' => 10]);

        $response = $this->actingAs($client)
            ->patch(route('fonds.updateStock', $fond), [
                'action' => 'increment',
                'quantite' => 5
            ]);

        // Redirection (client n'a pas accès à cette route via middleware)
        $response->assertRedirect();

        // Le stock ne doit pas avoir changé
        $this->assertDatabaseHas('fonds', [
            'id' => $fond->id,
            'quantite' => 10
        ]);
    }

    /** @test */
    public function action_increment_cree_mouvement_stock_entree()
    {
        $admin = $this->adminUser();
        $fond = Fond::factory()->create(['quantite' => 10, 'type' => 'Miroir']);

        $this->actingAs($admin)
            ->patch(route('fonds.updateStock', $fond), [
                'action' => 'increment',
                'quantite' => 5
            ]);

        // Vérifier qu'un mouvement a été créé
        $this->assertDatabaseHas('mouvements_stock', [
            'produit_type' => 'miroir',
            'produit_id' => $fond->id,
            'type' => 'entree',
            'quantite' => 5
        ]);
    }

    /** @test */
    public function action_decrement_cree_mouvement_stock_sortie()
    {
        $admin = $this->adminUser();
        $fond = Fond::factory()->create(['quantite' => 10, 'type' => 'Doré']);

        $this->actingAs($admin)
            ->patch(route('fonds.updateStock', $fond), [
                'action' => 'decrement',
                'quantite' => 3
            ]);

        // Vérifier qu'un mouvement a été créé (type = dore selon enum DB)
        $this->assertDatabaseHas('mouvements_stock', [
            'produit_type' => 'dore',
            'produit_id' => $fond->id,
            'type' => 'sortie',
            'quantite' => 3
        ]);
    }

    /** @test */
    public function action_invalide_est_rejetee()
    {
        $admin = $this->adminUser();
        $fond = Fond::factory()->create(['quantite' => 10]);

        $response = $this->actingAs($admin)
            ->patch(route('fonds.updateStock', $fond), [
                'action' => 'invalid_action',
                'quantite' => 5
            ]);

        $response->assertSessionHasErrors(['action']);

        // Le stock ne doit pas avoir changé
        $this->assertDatabaseHas('fonds', [
            'id' => $fond->id,
            'quantite' => 10
        ]);
    }

    /** @test */
    public function quantite_negative_est_rejetee()
    {
        $admin = $this->adminUser();
        $fond = Fond::factory()->create(['quantite' => 10]);

        $response = $this->actingAs($admin)
            ->patch(route('fonds.updateStock', $fond), [
                'action' => 'increment',
                'quantite' => -5
            ]);

        $response->assertSessionHasErrors(['quantite']);

        // Le stock ne doit pas avoir changé
        $this->assertDatabaseHas('fonds', [
            'id' => $fond->id,
            'quantite' => 10
        ]);
    }

    /** @test */
    public function action_set_mettre_quantite_a_zero()
    {
        $admin = $this->adminUser();
        $fond = Fond::factory()->create(['quantite' => 10]);

        $response = $this->actingAs($admin)
            ->patch(route('fonds.updateStock', $fond), [
                'action' => 'set',
                'quantite' => 0
            ]);

        $response->assertRedirect(route('fonds.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('fonds', [
            'id' => $fond->id,
            'quantite' => 0
        ]);
    }
}
