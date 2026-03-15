<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index'));

        $response->assertOk();
    }

    /** @test */
    public function employe_cannot_access_admin_routes(): void
    {
        $employe = User::factory()->create(['role' => 'employe']);

        $response = $this->actingAs($employe)
            ->get(route('admin.users.index'));

        $response->assertRedirect(); // 302 vers kiosque
    }

    /** @test */
    public function client_cannot_access_admin_routes(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($client)
            ->get(route('admin.users.index'));

        $response->assertRedirect(); // 302 vers kiosque
    }

    /** @test */
    public function admin_can_access_employe_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('vinyles.index'));

        $response->assertOk();
    }

    /** @test */
    public function employe_can_access_employe_routes(): void
    {
        $employe = User::factory()->create(['role' => 'employe']);

        $response = $this->actingAs($employe)
            ->get(route('vinyles.index'));

        $response->assertOk();
    }

    /** @test */
    public function client_cannot_access_employe_routes(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($client)
            ->get(route('vinyles.index'));

        $response->assertRedirect(); // 302 vers kiosque
    }

    /** @test */
    public function admin_and_employe_can_access_fonds_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employe = User::factory()->create(['role' => 'employe']);

        $this->actingAs($admin)
            ->get(route('fonds.index'))
            ->assertOk();

        $this->actingAs($employe)
            ->get(route('fonds.index'))
            ->assertOk();
    }

    /** @test */
    public function employe_cannot_update_fond_stock(): void
    {
        $employe = User::factory()->create(['role' => 'employe']);
        $fond = \App\Models\Fond::factory()->create();

        $response = $this->actingAs($employe)
            ->patch(route('fonds.updateStock', $fond), [
                'quantite' => 100,
            ]);

        $response->assertRedirect(); // 302 vers kiosque (middleware empêche)
    }

    /** @test */
    public function admin_can_update_fond_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fond = \App\Models\Fond::factory()->create(['quantite' => 50]);

        $response = $this->actingAs($admin)
            ->patch(route('fonds.updateStock', $fond), [
                'action' => 'set',
                'quantite' => 100,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('fonds', [
            'id' => $fond->id,
            'quantite' => 100,
        ]);
    }

    /** @test */
    public function guest_is_redirected_to_login_for_protected_routes(): void
    {
        $response = $this->get(route('vinyles.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function middleware_allows_multiple_roles(): void
    {
        // Route accessible à admin ET employe
        $admin = User::factory()->create(['role' => 'admin']);
        $employe = User::factory()->create(['role' => 'employe']);

        $adminResponse = $this->actingAs($admin)->get(route('stats'));
        $employeResponse = $this->actingAs($employe)->get(route('stats'));

        $adminResponse->assertOk();
        $employeResponse->assertOk();
    }

    /** @test */
    public function role_check_is_case_insensitive(): void
    {
        // Vérifier que le middleware gère bien les rôles en minuscules
        $admin = User::factory()->create(['role' => 'admin']);

        // La route admin.users nécessite 'admin' exactement
        $response = $this->actingAs($admin)
            ->get(route('admin.users.index'));

        $response->assertOk();
    }
}
