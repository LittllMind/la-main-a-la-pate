<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationLocalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_admin_can_impersonate_local_roles_and_restore(): void
    {
        $this->app['env'] = 'local';

        $admin = User::factory()->create([
            'id' => 1,
            'role' => 'admin',
            'email_verified_at' => now(),
            'requires_setup' => false,
        ]);

        // Admin -> Citizen
        $this->actingAs($admin)
            ->get(route('impersonate.become', ['citoyen']) . '?redirect=' . urlencode('/dashboard'))
            ->assertRedirect('/dashboard')
            ->assertSessionHas('success');

        $this->assertAuthenticated();
        $this->assertEquals('citoyen', auth()->user()->role);
        $this->assertEquals('test-citoyen@local.test', auth()->user()->email);
        $this->assertTrue(session()->has('impersonate_admin_id'));

        // Citizen -> Admin (revenir admin)
        $this->post(route('impersonate.restore') . '?redirect=' . urlencode('/'))
            ->assertRedirect('/')
            ->assertSessionHas('success');

        $this->assertAuthenticatedAs($admin);
        $this->assertEquals(1, auth()->user()->id);
        $this->assertFalse(session()->has('impersonate_admin_id'));
    }

    public function test_impersonation_fails_outside_local_environment(): void
    {
        $this->app['env'] = 'production';

        $admin = User::factory()->create([
            'id' => 1,
            'role' => 'admin',
            'email_verified_at' => now(),
            'requires_setup' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('impersonate.become', ['citoyen']))
            ->assertForbidden();

        $this->assertAuthenticatedAs($admin);
    }

    /**
     * G — Restore sans session impersonation est sûr (redirection + message).
     * Testé via test_restore_requires_existing_impersonation_session ci-dessus.
     */

    /**
     * A — guest ne peut pas impersoner
     */
    public function test_guest_cannot_impersonate(): void
    {
        $this->app['env'] = 'local';

        $this->get(route('impersonate.become', ['citoyen']))->assertRedirect('/');
    }

    /**
     * B — utilisateur ordinaire local ne peut pas impersoner
     */
    public function test_non_admin_user_cannot_impersonate(): void
    {
        $this->app['env'] = 'local';

        $user = User::factory()->create([
            'role' => 'citoyen',
            'email_verified_at' => now(),
            'requires_setup' => false,
        ]);

        $this->actingAs($user)
            ->get(route('impersonate.become', ['citoyen']))
            ->assertForbidden();
    }

    /**
     * D — rôle invalide refusé
     */
    public function test_invalid_role_is_rejected(): void
    {
        $this->app['env'] = 'local';

        $admin = User::factory()->create([
            'id' => 1,
            'role' => 'admin',
            'email_verified_at' => now(),
            'requires_setup' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('impersonate.become', ['superadmin']))
            ->assertNotFound();
    }

    /**
     * F — restore fonctionne et repasse au bon admin
     * Testé via test_admin_can_impersonate_local_roles_and_restore ci-dessus.
     */

    /**
     * UI — indicateur + bouton restore visibles pendant impersonation
     */
    public function test_navbar_shows_impersonation_indicator_and_restore_button_during_session(): void
    {
        $this->app['env'] = 'local';

        $admin = User::factory()->create([
            'id' => 1,
            'role' => 'admin',
            'email_verified_at' => now(),
            'requires_setup' => false,
        ]);

        // Déclencher impersonation
        $this->actingAs($admin)
            ->get(route('impersonate.become', ['citoyen']) . '?redirect=' . urlencode('/dashboard'))
            ->assertRedirect('/dashboard')
            ->assertSessionHas('success');

        // User courant est maintenant le test-citoyen
        $this->assertEquals('citoyen', auth()->user()->role);
        $this->assertNotEquals(1, auth()->user()->id);
        $this->assertTrue(session()->has('impersonate_admin_id'));

        // Charger une page avec navbar en tant que citoyen impersonné
        $response = $this->get('/dashboard');
        $response->assertOk();

        // L'état impersoné doit afficher l'indicateur et le bouton de restauration
        $response->assertSee('Impersonation citoyen');
        $response->assertSee('Revenir admin');

        // Le panneau "Tester comme" ne doit PAS s'afficher car id !== 1
        $response->assertDontSee('Tester comme');

        // Restaurer via bouton
        $this->post(route('impersonate.restore') . '?redirect=' . urlencode('/dashboard'))
            ->assertRedirect('/dashboard')
            ->assertSessionHas('success');

        $this->assertAuthenticatedAs($admin);
        $this->assertEquals(1, auth()->user()->id);
        $this->assertFalse(session()->has('impersonate_admin_id'));
    }

    /**
     * G — restore sans session impersonation est sûr
     */
    public function test_restore_requires_existing_impersonation_session(): void
    {
        $this->app['env'] = 'local';

        $admin = User::factory()->create([
            'id' => 1,
            'role' => 'admin',
            'email_verified_at' => now(),
            'requires_setup' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('impersonate.restore'))
            ->assertRedirect('/')
            ->assertSessionHas('error', 'Aucune impersonation active.');
    }

    /**
     * H — navbar impersonation absente hors environnement local
     */
    public function test_navbar_impersonation_controls_are_absent_outside_local(): void
    {
        $this->app['env'] = 'production';

        $admin = User::factory()->create([
            'id' => 1,
            'role' => 'admin',
            'email_verified_at' => now(),
            'requires_setup' => false,
        ]);

        $response = $this->actingAs($admin)->get('/');

        // Pour un user authentifié, home redirige vers dashboard.
        $response->assertRedirect('/dashboard');

        $final = $this->followingRedirects()->actingAs($admin)->get('/');
        $final->assertOk();
        $final->assertDontSee('Tester comme');
    }

    /**
     * I — navbar impersonation absente pour utilisateur non autorisé
     */
    public function test_navbar_impersonation_controls_are_absent_for_non_admin(): void
    {
        $this->app['env'] = 'local';

        $user = User::factory()->create([
            'role' => 'citoyen',
            'email_verified_at' => now(),
            'requires_setup' => false,
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect('/dashboard');

        $final = $this->followingRedirects()->actingAs($user)->get('/');
        $final->assertOk();
        $final->assertDontSee('Tester comme');
    }
}
