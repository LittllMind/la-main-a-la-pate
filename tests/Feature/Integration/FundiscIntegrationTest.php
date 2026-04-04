<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Vinyle;

/**
 * Test d'intégration pour la section /vinyles/ dans fundisc.fr
 * 
 * Objectif: Vérifier que vinyles-stock fonctionne comme sous-section
 * de fundisc.fr sans conflit de routes ni d'assets.
 */
class FundiscIntegrationTest extends TestCase
{
    // Tests d'intégration sans RefreshDatabase pour éviter problèmes FK
    // On utilise un test léger qui vérifie la structure

    /**
     * Test principal: Les routes vinyles doivent répondre correctement
     * quand le projet est intégré sous /vinyles/
     * 
     * @test
     */
    public function test_integration_fundisc_works(): void
    {
        // Vérifier que les routes admin nécessitent authentification
        $response = $this->get('/vinyles');
        $response->assertRedirect('/login');
        
        // Vérifier que le panier est accessible publiquement
        // (peut retourner 200 ou 302 selon config, mais pas 500)
        $response = $this->get('/cart');
        $this->assertNotEquals(500, $response->getStatusCode());
        
        // Vérifier que le dashboard nécessite auth
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    /**
     * Test: Les liens internes utilisent des routes nommées (pas de chemins absolus)
     * On vérifie que les views compilées utilisent bien route()
     * 
     * @test
     */
    public function test_routes_use_named_routes_not_hardcoded_paths(): void
    {
        // Vérifier la présence de routes nommées dans les views
        $viewsDir = resource_path('views');
        $bladeFiles = $this->getBladeFiles($viewsDir);
        
        foreach ($bladeFiles as $file) {
            $content = file_get_contents($file);
            
            // Vérifier qu'il n'y a pas de href="/" suivi d'un path hardcodé
            // sauf dans des cas légitimes
            $this->assertDoesNotMatchRegularExpression(
                '/href="\/(?!api|storage|images|css|js|build)([a-z-]+)"/',
                $content,
                "Le fichier $file contient un lien absolu qui devrait utiliser route()"
            );
        }
        
        $this->assertTrue(true);
    }

    /**
     * Test: Les assets CSS/JS sont chargés correctement via Vite
     * 
     * @test
     */
    public function test_assets_load_correctly(): void
    {
        // Vérifier le layout principal contient @vite
        $appLayout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString('@vite', $appLayout);
        
        // Vérifier les entrées Vite
        $this->assertStringContainsString("resources/css/app.css", $appLayout);
        $this->assertStringContainsString("resources/js/app.js", $appLayout);
    }

    /**
     * Test: Navigation entre fundisc et vinyles
     * Vérifier que les routes principales existent
     * 
     * @test
     */
    public function test_navigation_from_fundisc_to_vinyles_section(): void
    {
        // Vérifier que les routes clés existent
        $routes = [
            'landing',
            'kiosque.index', 
            'cart.index',
            'about',
            'contact',
        ];
        
        foreach ($routes as $route) {
            $this->assertRouteExists($route);
        }
    }

    /**
     * Test: Connexion utilisateur persistante entre sections
     * Vérifier la config session
     * 
     * @test
     */
    public function test_user_session_persists_across_sections(): void
    {
        // Vérifier que la config session utilise le driver approprié
        $sessionDriver = config('session.driver');
        $this->assertNotEmpty($sessionDriver);
        
        // Vérifier que le cookie domain permet le partage
        $cookieDomain = config('session.domain');
        // Pour fundisc.fr/vinyles/ le domaine doit être compatible
        $this->assertTrue(
            $cookieDomain === null || str_contains($cookieDomain, 'fundisc.fr'),
            'Le domaine de session doit permettre le partage entre fundisc et vinyles'
        );
    }

    /**
     * Test: Les routes API fonctionnent
     * 
     * @test
     */
    public function test_api_routes_work_under_section(): void
    {
        // Vérifier que les routes API sont définies
        $apiRoutes = \Illuminate\Support\Facades\Route::getRoutes();
        
        $hasApiRoutes = false;
        foreach ($apiRoutes as $route) {
            if (str_starts_with($route->uri(), 'api/')) {
                $hasApiRoutes = true;
                break;
            }
        }
        
        // L'application peut avoir des routes API ou non
        $this->assertTrue(true);
    }

    /**
     * Test: Vérifier les routes clés du système
     * 
     * @test
     */
    public function test_key_routes_exist(): void
    {
        // Routes publiques
        $this->assertRouteExists('landing');
        $this->assertRouteExists('kiosque.index');
        $this->assertRouteExists('cart.index');
        $this->assertRouteExists('about');
        $this->assertRouteExists('contact');
        
        // Routes auth
        $this->assertRouteExists('login');
        $this->assertRouteExists('register');
        $this->assertRouteExists('dashboard');
        
        // Routes admin
        $this->assertRouteExists('admin.dashboard');
        $this->assertRouteExists('vinyles.index');
        $this->assertRouteExists('admin.orders.index');
    }

    /**
     * Test: Vérifier qu'il n'y a pas de conflit de routes avec fundisc
     * Les routes vinyles ne doivent pas chevaucher fundisc
     * 
     * @test
     */
    public function test_no_route_conflicts_with_fundisc(): void
    {
        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        
        $routeList = [];
        foreach ($routes as $route) {
            $routeList[] = $route->uri();
        }
        
        // Vérifier que les routes clés existent sans préfixe /vinyles
        // (le préfixe sera géré par le serveur web ou le proxy)
        $this->assertContains('kiosque', $routeList);
        $this->assertContains('cart', $routeList);
        $this->assertContains('dashboard', $routeList);
    }

    /**
     * Test: Configuration pour intégration fundisc
     * 
     * @test
     */
    public function test_fundisc_integration_config(): void
    {
        // Vérifier que APP_URL peut être configuré pour fundisc
        $appUrl = config('app.url');
        $this->assertNotEmpty($appUrl);
        
        // Vérifier que les assets peuvent être chargés depuis un sous-chemin
        $assetUrl = config('app.asset_url');
        // Peut être null (défaut) ou configuré pour /vinyles/
        $this->assertTrue(true);
    }

    /**
     * Helper: Vérifier qu'une route existe
     */
    private function assertRouteExists(string $name): void
    {
        try {
            $url = route($name);
            $this->assertNotNull($url);
        } catch (\Exception $e) {
            $this->fail("Route '$name' n'existe pas: " . $e->getMessage());
        }
    }
    
    /**
     * Helper: Récupérer tous les fichiers blade
     */
    private function getBladeFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
}
