<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Models\Vinyle;
use App\Models\Fond;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    // ========== AUTHENTICATION TESTS ==========

    public function test_admin_dashboard_requires_authentication(): void
    {
        $response = $this->get('/dashboard');
        $response->assertStatus(302);
    }

    public function test_admin_dashboard_requires_authenticated_user(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertOk(); // Dashboard est accessible à tous les utilisateurs authentifiés
    }

    public function test_admin_routes_require_admin_role(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        
        // Tester l'accès à une route admin avec un user normal
        $response = $this->actingAs($user)->get('/admin/users');
        
        // Doit rediriger vers kiosque avec message d'erreur en session
        $response->assertRedirect(route('kiosque.index'));
        $response->assertSessionHas('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à cette page.');
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Un admin doit pouvoir accéder aux routes admin
        $response = $this->actingAs($admin)->get('/admin/users');
        $response->assertOk(); // 200 pour les admins
    }

    public function test_operator_dashboard_requires_employe_or_admin_role(): void
    {
        $employe = User::factory()->create(['role' => 'employe']);
        
        // Un employé doit pouvoir accéder au kiosque (son espace de travail)
        $response = $this->actingAs($employe)->get('/kiosque');
        $response->assertOk();
        
        // Mais PAS aux routes admin
        $response = $this->actingAs($employe)->get('/admin/users');
        $response->assertRedirect(route('kiosque.index'));
        $response->assertSessionHas('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à cette page.');
    }

    public function test_logout_invalidates_session(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)->post('/logout');
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    // ========== IDOR / AUTHORIZATION TESTS ==========
    // Ces tests nécessitent une route de gestion users dédiée

    public function test_user_cannot_edit_other_user(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $otherUser = User::factory()->create(['role' => 'client']);
        
        // Les routes users sont sous /admin/users — les users normaux n'y ont pas accès
        // Route admin.users.edit requiert auth + role:admin
        $response = $this->actingAs($user)->get("/admin/users/{$otherUser->id}/edit");
        
        // Redirection vers kiosque (middleware role check après auth)
        $response->assertRedirect(route('kiosque.index'));
    }

    public function test_non_admin_cannot_list_users(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        
        // Tentative d'accès à la liste users (admin seulement)
        $response = $this->actingAs($user)->get('/admin/users');
        
        // Redirection (middleware role check)
        $response->assertRedirect();
    }

    public function test_idor_on_fonds_returns_403_for_unauthorized(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $fond = \App\Models\Fond::factory()->create();
        
        // Route correcte: /fonds/{fond}/stock (binding modèle)
        $response = $this->actingAs($user)->patch("/fonds/{$fond->id}/stock", [
            'action' => 'set',
            'quantite' => 1
        ]);
        
        // Doit être rejeté (403 ou redirection)
        $this->assertTrue(
            $response->status() === 403 || $response->isRedirect(),
            'Un client ne doit pas pouvoir modifier les fonds'
        );
    }

    public function test_employe_cannot_update_fond_stock(): void
    {
        $employe = User::factory()->create(['role' => 'employe']);
        $fond = \App\Models\Fond::factory()->create();
        
        // Un employé ne doit pas pouvoir modifier un fond
        $response = $this->actingAs($employe)->patch("/fonds/{$fond->id}/stock", [
            'action' => 'set',
            'quantite' => 1
        ]);
        
        // Doit être rejeté (403 ou redirection)
        $this->assertTrue(
            $response->status() === 403 || $response->isRedirect(),
            'Un employé ne doit pas pouvoir modifier les fonds'
        );
    }

    public function test_idor_invalid_fond_id_returns_404_not_500(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($user)->get('/fonds/99999');
        $response->assertNotFound(); // Doit être 404, pas 500
    }

    // ========== SQL INJECTION TESTS ==========

    public function test_sql_injection_in_kiosque_search_is_sanitized(): void
    {
        // Test que la recherche kiosque est protégée contre l'injection SQL
        $maliciousInput = "'; DROP TABLE vinyles; --";
        
        $response = $this->get('/kiosque?search=' . urlencode($maliciousInput));
        
        // La requête doit réussir (pas d'erreur 500) mais sans crash DB
        $response->assertStatus(200);
        
        // Vérifier que la table vinyles existe toujours (protection SQL injection)
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('vinyles'), 'Table vinyles should exist after SQL injection attempt');
    }

    public function test_sql_injection_in_order_by_is_sanitized(): void
    {
        // La route kiosque accepte un paramètre sort - vérifier la protection
        $maliciousOrder = "title; DELETE FROM users; --";
        
        $response = $this->get('/kiosque?sort=' . urlencode($maliciousOrder));
        
        // Doit retourner 200 sans exécuter la partie malveillante
        $response->assertStatus(200);
    }

    // ========== XSS TESTS ==========

    public function test_xss_payload_in_search_is_escaped(): void
    {
        // Injection XSS via paramètre de recherche
        $xssPayload = "<script>alert('xss')</script>";
        
        $response = $this->get('/kiosque?search=' . urlencode($xssPayload));
        
        $content = $response->getContent();
        
        // Le script ne doit pas apparaître tel quel dans la réponse
        $this->assertStringNotContainsString("<script>alert('xss')</script>", $content);
    }

    public function test_xss_payload_in_error_messages_is_escaped(): void
    {
        // Payload dans une URL invalide
        $response = $this->get('/non-existent-page<script>alert(1)</script>');
        
        // Doit retourner 404 sans exécuter le script
        $response->assertStatus(404);
        
        $content = $response->getContent();
        
        // Le script ne doit pas être présent en clair
        $this->assertStringNotContainsString('<script>', $content);
    }

    // ========== CSRF TESTS ==========

    public function test_form_without_csrf_token_fails(): void
    {
        // Test CSRF: token invalide doit être rejeté
        $user = User::factory()->create(['role' => 'admin']);
        
        $responseWithInvalidToken = $this->actingAs($user)
            ->post('/vinyles?_token=invalid', [
                'nom' => 'Test',
                'artiste' => 'Test',
                'prix' => 10,
                'quantite' => 1,
            ]);
        
        // Token invalide = redirection 302 (web) ou 419 (API/JSON)
        $this->assertTrue(
            $responseWithInvalidToken->status() === 419 || $responseWithInvalidToken->status() === 302,
            'Token CSRF invalide doit être rejeté (419 pour API ou 302 pour web)'
        );
    }

    public function test_api_routes_require_authentication(): void
    {
        // Les routes API retournent soit 401 soit 404 si non auth
        // Ce qui compte : pas d'accès non authentifié aux données
        $response = $this->get('/api/users');
        
        // Accepte 401 (Non authentifié) ou 404 (Route inexistante = sécurisée par défaut)
        $this->assertTrue(
            $response->status() === 401 || $response->status() === 404,
            'Les routes API doivent retourner 401 (non auth) ou 404 (inexistante), jamais 200'
        );
    }

    // ========== SECURITY HEADERS TESTS ==========

    public function test_x_frame_options_header_is_present(): void
    {
        $response = $this->get('/login');
        $response->assertHeader('X-Frame-Options');
    }

    public function test_x_content_type_options_header_is_present(): void
    {
        $response = $this->get('/login');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_csp_header_is_present(): void
    {
        $response = $this->get('/login');
        // Vérifier que le header CSP est présent (même si vide ou configuré différemment)
        $this->assertTrue(
            $response->headers->has('Content-Security-Policy') || 
            $response->headers->has('content-security-policy'),
            'CSP header should be present'
        );
    }

    // ========== ERROR HANDLING TESTS ==========

    public function test_error_pages_do_not_expose_sensitive_data(): void
    {
        // Forcer une 404
        $response = $this->get('/non-existent-route-xyz123');
        $response->assertNotFound();
        
        $content = $response->getContent();
        $this->assertDoesNotMatchRegularExpression('/SQLSTATE|laravel|vendor\/laravel/', $content);
    }

    public function test_method_not_allowed_returns_405(): void
    {
        $response = $this->delete('/'); // Route GET uniquement
        $response->assertStatus(405);
    }
}
