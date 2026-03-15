<?php

namespace Tests\Feature\Performance;

use App\Models\User;
use App\Models\Vinyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KiosquePerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * Test que le kiosque charge avec eager loading des images
     */
    public function test_kiosque_loads_vinyls_with_efficient_querying(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        // Créer 100 vinyles
        Vinyle::factory()->count(100)->create();

        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $this->actingAs($user)->get('/kiosque');

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Le kiosque devrait charger en max 3-4 requêtes (vinyles, genres/styles pour filtres, etc.)
        $this->assertLessThanOrEqual(
            10,
            $queryCount,
            'Le kiosque génère trop de requêtes (' . $queryCount . '). ' .
            'Ajouter eager loading avec with(["media"]).'
        );

        $response->assertSuccessful();
    }

    /**
     * @test
     * Test que la recherche reste performante avec beaucoup de résultats
     */
    public function test_search_remains_fast_with_large_catalog(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        // Créer 500 vinyles
        Vinyle::factory()->count(500)->create();

        $start = microtime(true);

        $response = $this->actingAs($user)->get('/kiosque?search=rock');

        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(
            1000,
            $duration,
            "La recherche est trop lente ({$duration}ms). " .
            "Vérifier les indexes sur artiste/modele/genre."
        );

        $response->assertSuccessful();
    }

    /**
     * @test
     * Test pagination pour éviter chargement trop grand
     */
    public function test_kiosque_uses_pagination(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        // Créer 200 vinyles
        Vinyle::factory()->count(200)->create();

        $response = $this->actingAs($user)->get('/kiosque');

        // Vérifier que la réponse contient une indication de pagination
        // (links, ou count < total si pagination active)
        $response->assertSuccessful();

        // La vue devrait recevoir une collection paginée
        $this->assertLessThan(
            200,
            $response->viewData('vinyles')?->count() ?? 200,
            'Tous les vinyles sont chargés sans pagination. ' .
            'Utiliser ->paginate(24) au lieu de ->get().'
        );
    }
}
