<?php

namespace Tests\Feature\Performance;

use App\Models\User;
use App\Models\Vinyle;
use App\Models\Fond;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VinyleQueriesOptimizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * Test que la liste des vinyles utilise eager loading pour les médias
     */
    public function test_admin_vinyls_list_uses_eager_loading(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Créer vinyles avec médias
        Vinyle::factory()->count(50)->create();

        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $this->actingAs($admin)->get('/vinyles');

        $queries = collect(DB::getQueryLog())->map(fn($q) => $q['query']);

        // Vérifier qu'on a pas de requêtes media répétées
        $mediaQueries = $queries->filter(fn($q) => str_contains($q, 'media'));

        // Devrait avoir seulement 1-2 requêtes pour les médias (eager loaded)
        $this->assertLessThanOrEqual(
            3,
            $mediaQueries->count(),
            'Les médias ne sont pas eager loaded. ' .
            'Ajouter ->with("media") dans le contrôleur.'
        );

        $response->assertSuccessful();
    }

    /**
     * @test
     * Test que les requêtes fonds sont optimisées
     */
    public function test_fonds_queries_use_aggregation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Fond::factory()->count(100)->create();

        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $this->actingAs($admin)->get('/fonds');

        $queries = DB::getQueryLog();

        // Vérifier qu'on utilise pas de boucle sur 100 fonds
        $this->assertLessThanOrEqual(
            5,
            count($queries),
            'Requêtes fonds non optimisées. '
        );

        $response->assertSuccessful();
    }

    /**
     * @test
     * Test que les relations sont chargées efficacement
     */
    public function test_vinyl_relations_load_efficiently(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $vinyle = Vinyle::factory()->create();

        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $this->actingAs($admin)->get("/vinyles/{$vinyle->id}/edit");

        $queries = DB::getQueryLog();

        // Page d'édition devrait faire max 5-6 requêtes (vinyle, médias, options, etc.)
        $this->assertLessThanOrEqual(
            8,
            count($queries),
            'Trop de requêtes sur la page édition vinyle. '
        );

        $response->assertSuccessful();
    }
}
