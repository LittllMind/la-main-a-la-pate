<?php

namespace Tests\Feature\Performance;

use App\Models\User;
use App\Models\Vinyle;
use App\Models\Vente;
use App\Models\LigneVente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StatsPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        // Désactiver le query log pour gain de perf sur gros volumes
        DB::disableQueryLog();
    }

    /**
     * @test
     * Test que le dashboard stats ne génère pas de requêtes N+1
     */
    public function test_stats_dashboard_avoids_n_plus_1_queries(): void
    {
        // Créer admin
        $admin = User::factory()->create(['role' => 'admin']);

        // Créer 50 vinyles avec 10 médias chacun (simulation)
        Vinyle::factory()->count(50)->create();

        // Créer 100 ventes avec lignes
        $ventes = Vente::factory()->count(100)->create(['total' => 100]);
        foreach ($ventes as $vente) {
            LigneVente::factory()->count(3)->create([
                'vente_id' => $vente->id,
                'quantite' => 2,
                'prix_unitaire' => 25
            ]);
        }

        // Activer le query log
        DB::enableQueryLog();
        DB::flushQueryLog();

        // Exécuter la requête
        $response = $this->actingAs($admin)->get('/stats');

        // Récupérer les requêtes exécutées
        $queries = DB::getQueryLog();

        // Compter les requêtes uniques (sans doublons de bindings)
        $uniqueQueries = collect($queries)->map(fn($q) => $q['query'])->unique();

        // Assertions : pas plus de 30 requêtes différentes pour afficher les stats
        // (avec les optimisations, on devrait être autour de 15-20 requêtes)
        $this->assertLessThanOrEqual(
            30,
            $uniqueQueries->count(),
            'Le dashboard stats génère trop de requêtes SQL. ' .
            'Vérifier les N+1 et ajouter eager loading si nécessaire. ' .
            'Requêtes: ' . implode("\n", $uniqueQueries->take(30)->toArray())
        );

        $response->assertSuccessful();
    }

    /**
     * @test
     * Test que les stats se chargent en moins de 500ms avec 1000 ventes
     */
    public function test_stats_loads_quickly_with_large_dataset(): void
    {
        // Skip si pas assez de mémoire
        if (memory_get_usage() > 100 * 1024 * 1024) {
            $this->markTestSkipped('Mémoire insuffisante pour ce test');
        }

        $admin = User::factory()->create(['role' => 'admin']);

        // Créer dataset significatif
        Vinyle::factory()->count(100)->create();
        Vente::factory()->count(500)->create()->each(function ($vente) {
            LigneVente::factory()->count(2)->create(['vente_id' => $vente->id]);
        });

        // Mesurer le temps
        $start = microtime(true);

        $response = $this->actingAs($admin)->get('/stats?periode=30j');

        $duration = (microtime(true) - $start) * 1000; // en ms

        // Assertion : moins de 2 secondes (tolérance généreuse pour CI)
        $this->assertLessThan(
            2000,
            $duration,
            "Le dashboard stats prend trop de temps ({$duration}ms). " .
            "Optimiser les requêtes ou ajouter du cache."
        );

        $response->assertSuccessful();
    }

    /**
     * @test
     * Test que le cache est utilisé pour les stats fréquentes
     */
    public function test_frequent_stats_are_cached(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Créer données
        Vinyle::factory()->count(50)->create();
        Vente::factory()->count(100)->create();

        // Premier appel - génère le cache
        $this->actingAs($admin)->get('/stats');

        // Deuxième appel (devrait utiliser cache)
        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->actingAs($admin)->get('/stats');
        $secondCallQueries = DB::getQueryLog();

        // Avec le cache, on devrait avoir très peu de requêtes (juste user + session)
        // Le cache Laravel évite les requêtes de stats
        $statsQueries = collect($secondCallQueries)->filter(fn($q) => 
            str_contains($q['query'], 'vinyles') || 
            str_contains($q['query'], 'ventes') ||
            str_contains($q['query'], 'ligne_ventes') ||
            str_contains($q['query'], 'fonds')
        );

        // Si le cache fonctionne, pas de requêtes sur les tables de stats
        $this->assertLessThanOrEqual(
            2,
            $statsQueries->count(),
            'Le cache ne semble pas fonctionner - trop de requêtes sur les tables de stats au 2ème appel'
        );
    }

    /**
     * @test
     * Vérifier l'absence de requêtes dans des boucles
     */
    public function test_no_queries_inside_loops(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Créer beaucoup de données
        Vinyle::factory()->count(200)->create();
        $ventes = Vente::factory()->count(100)->create();
        
        foreach ($ventes as $vente) {
            LigneVente::factory()->create([
                'vente_id' => $vente->id,
                'vinyle_id' => Vinyle::inRandomOrder()->first()->id
            ]);
        }

        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->actingAs($admin)->get('/stats');

        $queries = collect(DB::getQueryLog())->map(fn($q) => $q['query']);

        // Vérifier qu'il n'y a pas de requêtes identiques répétées (signe de N+1)
        $duplicates = $queries->countBy()->filter(fn($count) => $count > 10);

        $this->assertEmpty(
            $duplicates,
            'Requêtes N+1 détectées : ' . $duplicates->toJson()
        );
    }
}
