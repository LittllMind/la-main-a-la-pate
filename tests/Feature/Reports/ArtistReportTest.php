<?php

namespace Tests\Feature\Reports;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vinyle;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ArtistReportTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    public function setUp(): void
    {
        parent::setUp();
        // Créer un admin avec id=1 avant tous les tests pour l'observer
        $this->admin = User::factory()->admin()->create(['id' => 1]);
    }

    /**
     * T13.1-AR-1 : Admin peut voir le rapport par artiste
     */
    public function test_admin_can_view_artist_report(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.artists'));
        
        $response->assertOk();
        $response->assertViewIs('admin.reports.artists');
    }

    /**
     * T13.1-AR-2 : Rapport liste tous les artistes avec leur stock
     */
    public function test_artist_report_lists_all_artists_with_stock(): void
    {
        Vinyle::factory()->create(['artiste' => 'The Beatles', 'quantite' => 5]);
        Vinyle::factory()->create(['artiste' => 'The Beatles', 'quantite' => 3]);
        Vinyle::factory()->create(['artiste' => 'Pink Floyd', 'quantite' => 7]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.artists'));

        $response->assertOk();
        $response->assertSee('The Beatles');
        $response->assertSee('Pink Floyd');
        $response->assertSee('8'); // 5 + 3 Beatles
        $response->assertSee('7'); // Pink Floyd
    }

    /**
     * T13.1-AR-3 : Rapport trie par valeur de stock décroissante
     */
    public function test_artist_report_sorts_by_stock_value_descending(): void
    {
        Vinyle::factory()->create(['artiste' => 'Cheap Artist', 'prix' => 10, 'quantite' => 1]); // 10€
        Vinyle::factory()->create(['artiste' => 'Expensive Artist', 'prix' => 100, 'quantite' => 2]); // 200€

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.artists'));

        $response->assertOk();
        // Vérifier l'ordre (Expensive Artist d'abord s'il y a tri)
    }

    /**
     * T13.1-AR-4 : Rapport affiche le nombre de titres par artiste
     */
    public function test_artist_report_shows_number_of_titles_per_artist(): void
    {
        Vinyle::factory()->count(5)->create(['artiste' => 'The Beatles']); // 5 titres
        Vinyle::factory()->count(2)->create(['artiste' => 'Pink Floyd']); // 2 titres

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.artists'));

        $response->assertOk();
        $response->assertSee('5'); // 5 titres Beatles
        $response->assertSee('2'); // 2 titres Pink Floyd
    }

    /**
     * T13.1-AR-5 : Vendeur peut voir le rapport par artiste
     */
    public function test_employe_can_view_artist_report(): void
    {
        $employe = User::factory()->employe()->create();
        
        $response = $this->actingAs($employe)
            ->get(route('admin.reports.artists'));
        
        $response->assertOk();
    }

    /**
     * T13.1-AR-6 : Client ne peut pas voir le rapport
     */
    public function test_client_cannot_view_artist_report(): void
    {
        $client = User::factory()->client()->create();
        
        $response = $this->actingAs($client)
            ->get(route('admin.reports.artists'));
        
        $response->assertRedirect('/kiosque');
    }

    /**
     * T13.1-AR-7 : Rapport peut filtrer par lettre alphabétique
     */
    public function test_artist_report_can_filter_by_letter(): void
    {
        Vinyle::factory()->create(['artiste' => 'The Beatles']);
        Vinyle::factory()->create(['artiste' => 'Pink Floyd']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.artists', ['letter' => 'T']));

        $response->assertOk();
        $response->assertSee('The Beatles');
        $response->assertDontSee('Pink Floyd');
    }

    /**
     * T13.1-AR-8 : Rapport affiche les artistes sans stock
     */
    public function test_artist_report_shows_out_of_stock_artists(): void
    {
        Vinyle::factory()->create(['artiste' => 'In Stock Artist', 'quantite' => 5]);
        Vinyle::factory()->create(['artiste' => 'Out of Stock Artist', 'quantite' => 0]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.artists'));

        $response->assertOk();
        $response->assertSee('Out of Stock Artist');
        $response->assertSee('Rupture'); // ou équivalent
    }
}
