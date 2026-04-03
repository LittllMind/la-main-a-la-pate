<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Models\Fond;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests IDOR (Insecure Direct Object Reference) sur les fonds.
 * 
 * Note: Les routes /fonds/{fond} (show/edit/update) n'existent pas actuellement.
 * Les fonds sont gérés via des routes dédiées:
 * - GET /fonds (index) - admin, employé
 * - PATCH /fonds/{fond}/stock - admin uniquement
 * - PATCH /fonds/{fond}/prix - admin uniquement
 */
class FondIdorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Un client ne peut pas voir l'index des fonds (pas de route)
     */
    public function test_client_cannot_access_fonds_list(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($client)->get('/fonds');

        // Redirection vers kiosque (client n'a pas accès à admin)
        $response->assertRedirect();
    }

    /**
     * Test: Un employé peut voir l'index des fonds
     */
    public function test_employe_can_view_fonds_list(): void
    {
        $employe = User::factory()->create(['role' => 'employe']);
        Fond::factory()->create(['type' => 'standard_test']);

        $response = $this->actingAs($employe)->get('/fonds');

        $response->assertOk();
    }

    /**
     * Test: Un employé ne peut pas modifier le stock (route admin uniquement)
     */
    public function test_employe_cannot_modify_fond_stock(): void
    {
        $employe = User::factory()->create(['role' => 'employe']);
        $fond = Fond::factory()->create(['quantite' => 10]);

        $response = $this->actingAs($employe)->patch("/fonds/{$fond->id}/stock", [
            'quantite' => 999,
        ]);

        // 403 Forbidden ou redirect
        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Employe ne doit pas modifier le stock'
        );

        $this->assertDatabaseHas('fonds', ['id' => $fond->id, 'quantite' => 10]);
    }

    /**
     * Test: Un admin peut modifier le stock (avec action 'set')
     */
    public function test_admin_can_modify_any_fond(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fond = Fond::factory()->create(['quantite' => 10]);

        $response = $this->actingAs($admin)->patch("/fonds/{$fond->id}/stock", [
            'action' => 'set',
            'quantite' => 50,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('fonds', ['id' => $fond->id, 'quantite' => 50]);
    }

    /**
     * Test: Un client ne peut pas modifier le stock
     */
    public function test_client_cannot_modify_fond_stock(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $fond = Fond::factory()->create(['quantite' => 10]);

        $response = $this->actingAs($client)->patch("/fonds/{$fond->id}/stock", [
            'quantite' => 999,
        ]);

        // Client n'a pas accès
        $this->assertFalse($response->isSuccessful());

        $this->assertDatabaseHas('fonds', ['id' => $fond->id, 'quantite' => 10]);
    }

    /**
     * Test: Un employé ne peut pas modifier les prix
     */
    public function test_employe_cannot_update_fond_prices(): void
    {
        $employe = User::factory()->create(['role' => 'employe']);
        $fond = Fond::factory()->create(['prix_vente' => 15.00]);

        $response = $this->actingAs($employe)->patch("/fonds/{$fond->id}/prix", [
            'prix_vente' => 50.00,
        ]);

        // 403 Forbidden ou redirect
        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Employe ne doit pas modifier les prix'
        );

        $this->assertDatabaseHas('fonds', ['id' => $fond->id, 'prix_vente' => 15.00]);
    }

    /**
     * Test: Un admin peut modifier les prix (avec prix_achat et prix_vente)
     */
    public function test_admin_can_update_fond_prices(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fond = Fond::factory()->create(['prix_achat' => 10.00, 'prix_vente' => 15.00]);

        $response = $this->actingAs($admin)->patch("/fonds/{$fond->id}/prix", [
            'prix_achat' => 12.00,
            'prix_vente' => 25.00,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('fonds', ['id' => $fond->id, 'prix_vente' => 25.00]);
    }
}
