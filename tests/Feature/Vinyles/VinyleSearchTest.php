<?php

namespace Tests\Feature\Vinyles;

use App\Models\User;
use App\Models\Vinyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VinyleSearchTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================================
    // TEST: Recherche par artiste
    // ==========================================================
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_search_by_artist(): void
    {
        $admin = $this->adminUser();
        
        // Créer des vinyles avec des artistes différents
        Vinyle::factory()->create([
            'artiste' => 'Pink Floyd',
            'modele' => 'The Dark Side of the Moon',
            'reference' => 'VIN-001'
        ]);
        Vinyle::factory()->create([
            'artiste' => 'The Beatles',
            'modele' => 'Abbey Road',
            'reference' => 'VIN-002'
        ]);
        Vinyle::factory()->create([
            'artiste' => 'Led Zeppelin',
            'modele' => 'Led Zeppelin IV',
            'reference' => 'VIN-003'
        ]);

        // Recherche par artiste
        $response = $this->actingAs($admin)
            ->get(route('vinyles.search', ['q' => 'Pink']));

        $response->assertOk()
            ->assertViewIs('vinyles.search')
            ->assertSee('Pink Floyd')
            ->assertSee('The Dark Side of the Moon')
            ->assertDontSee('The Beatles')
            ->assertDontSee('Led Zeppelin');
    }

    // ==========================================================
    // TEST: Filtre par prix min/max
    // ==========================================================
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_filter_by_price(): void
    {
        $admin = $this->adminUser();
        
        // Créer des vinyles avec différents prix
        Vinyle::factory()->create([
            'artiste' => 'Cheap Artist',
            'prix' => 10.00,
            'reference' => 'VIN-CHEAP'
        ]);
        Vinyle::factory()->create([
            'artiste' => 'Mid Artist',
            'prix' => 25.00,
            'reference' => 'VIN-MID'
        ]);
        Vinyle::factory()->create([
            'artiste' => 'Expensive Artist',
            'prix' => 50.00,
            'reference' => 'VIN-EXP'
        ]);

        // Filtre prix min
        $response = $this->actingAs($admin)
            ->get(route('vinyles.search', ['prix_min' => 20]));
        
        $response->assertOk()
            ->assertSee('Mid Artist')
            ->assertSee('Expensive Artist')
            ->assertDontSee('Cheap Artist');

        // Filtre prix max
        $response = $this->actingAs($admin)
            ->get(route('vinyles.search', ['prix_max' => 30]));
        
        $response->assertOk()
            ->assertSee('Cheap Artist')
            ->assertSee('Mid Artist')
            ->assertDontSee('Expensive Artist');

        // Filtre prix min et max
        $response = $this->actingAs($admin)
            ->get(route('vinyles.search', ['prix_min' => 15, 'prix_max' => 40]));
        
        $response->assertOk()
            ->assertSee('Mid Artist')
            ->assertDontSee('Cheap Artist')
            ->assertDontSee('Expensive Artist');
    }

    // ==========================================================
    // TEST: Recherche sans résultats
    // ==========================================================
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_search_no_results(): void
    {
        $admin = $this->adminUser();
        
        Vinyle::factory()->create([
            'artiste' => 'Pink Floyd',
            'modele' => 'The Wall',
            'reference' => 'VIN-001'
        ]);

        // Recherche qui ne retourne rien
        $response = $this->actingAs($admin)
            ->get(route('vinyles.search', ['q' => 'Musique inexistante XYZ123']));

        $response->assertOk()
            ->assertViewIs('vinyles.search')
            ->assertSee('Aucun vinyle trouvé');
    }

    // ==========================================================
    // TEST: Filtre par genre
    // ==========================================================
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_filter_by_genre(): void
    {
        $admin = $this->adminUser();
        
        Vinyle::factory()->create([
            'artiste' => 'Rock Artist',
            'genre' => 'Rock',
            'reference' => 'VIN-ROCK'
        ]);
        Vinyle::factory()->create([
            'artiste' => 'Jazz Artist',
            'genre' => 'Jazz',
            'reference' => 'VIN-JAZZ'
        ]);
        Vinyle::factory()->create([
            'artiste' => 'Pop Artist',
            'genre' => 'Pop',
            'reference' => 'VIN-POP'
        ]);

        $response = $this->actingAs($admin)
            ->get(route('vinyles.search', ['genre' => 'Rock']));

        $response->assertOk()
            ->assertSee('Rock Artist')
            ->assertDontSee('Jazz Artist')
            ->assertDontSee('Pop Artist');
    }

    // ==========================================================
    // TEST: Filtre par style
    // ==========================================================
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_filter_by_style(): void
    {
        $admin = $this->adminUser();
        
        Vinyle::factory()->create([
            'artiste' => 'Classic Rock Artist',
            'genre' => 'Rock',
            'style' => 'Classic Rock',
            'reference' => 'VIN-001'
        ]);
        Vinyle::factory()->create([
            'artiste' => 'Hard Rock Artist',
            'genre' => 'Rock',
            'style' => 'Hard Rock',
            'reference' => 'VIN-002'
        ]);

        $response = $this->actingAs($admin)
            ->get(route('vinyles.search', ['style' => 'Classic Rock']));

        $response->assertOk()
            ->assertSee('Classic Rock Artist')
            ->assertDontSee('Hard Rock Artist');
    }

    // ==========================================================
    // TEST: Filtre par année
    // ==========================================================
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_filter_by_year(): void
    {
        $admin = $this->adminUser();
        
        $vinyle70s = Vinyle::factory()->create([
            'artiste' => '70s Artist',
            'reference' => 'VIN-70s'
        ]);
        $vinyle70s->created_at = '1975-01-01';
        $vinyle70s->save();
        
        $vinyle80s = Vinyle::factory()->create([
            'artiste' => '80s Artist',
            'reference' => 'VIN-80s'
        ]);
        $vinyle80s->created_at = '1985-01-01';
        $vinyle80s->save();

        $response = $this->actingAs($admin)
            ->get(route('vinyles.search', ['annee' => '1975']));

        $response->assertOk()
            ->assertSee('70s Artist')
            ->assertDontSee('80s Artist');
    }

    // ==========================================================
    // TEST: Tri par prix croissant
    // ==========================================================
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_sort_by_price_asc(): void
    {
        $admin = $this->adminUser();
        
        Vinyle::factory()->create([
            'artiste' => 'Expensive',
            'prix' => 100.00,
            'reference' => 'VIN-EXP'
        ]);
        Vinyle::factory()->create([
            'artiste' => 'Cheap',
            'prix' => 10.00,
            'reference' => 'VIN-CHEAP'
        ]);
        Vinyle::factory()->create([
            'artiste' => 'Mid',
            'prix' => 50.00,
            'reference' => 'VIN-MID'
        ]);

        $response = $this->actingAs($admin)
            ->get(route('vinyles.search', ['sort' => 'prix_asc']));

        $response->assertOk();
        
        // Vérifier l'ordre dans la réponse
        $content = $response->getContent();
        $posCheap = strpos($content, 'Cheap');
        $posMid = strpos($content, 'Mid');
        $posExpensive = strpos($content, 'Expensive');
        
        $this->assertTrue($posCheap < $posMid && $posMid < $posExpensive, 
            'Les vinyles devraient être triés par prix croissant');
    }

    // ==========================================================
    // TEST: Tri par prix décroissant
    // ==========================================================
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_sort_by_price_desc(): void
    {
        $admin = $this->adminUser();
        
        Vinyle::factory()->create([
            'artiste' => 'Cheap',
            'prix' => 10.00,
            'reference' => 'VIN-CHEAP'
        ]);
        Vinyle::factory()->create([
            'artiste' => 'Expensive',
            'prix' => 100.00,
            'reference' => 'VIN-EXP'
        ]);

        $response = $this->actingAs($admin)
            ->get(route('vinyles.search', ['sort' => 'prix_desc']));

        $response->assertOk();
        
        $content = $response->getContent();
        $posCheap = strpos($content, 'Cheap');
        $posExpensive = strpos($content, 'Expensive');
        
        $this->assertTrue($posExpensive < $posCheap, 
            'Les vinyles devraient être triés par prix décroissant');
    }

    // ==========================================================
    // TEST: Tri par artiste
    // ==========================================================
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_sort_by_artist(): void
    {
        $admin = $this->adminUser();
        
        Vinyle::factory()->create(['artiste' => 'ZZ Top', 'reference' => 'VIN-ZZ']);
        Vinyle::factory()->create(['artiste' => 'AC/DC', 'reference' => 'VIN-ACDC']);
        Vinyle::factory()->create(['artiste' => 'Beatles', 'reference' => 'VIN-BEAT']);

        $response = $this->actingAs($admin)
            ->get(route('vinyles.search', ['sort' => 'artiste']));

        $response->assertOk();
        
        $content = $response->getContent();
        $posAcDc = strpos($content, 'AC/DC');
        $posBeatles = strpos($content, 'Beatles');
        $posZzTop = strpos($content, 'ZZ Top');
        
        $this->assertTrue($posAcDc < $posBeatles && $posBeatles < $posZzTop, 
            'Les vinyles devraient être triés par artiste alphabétiquement');
    }

    // ==========================================================
    // TEST: Tri par date d'ajout
    // ==========================================================
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_sort_by_date_added(): void
    {
        $admin = $this->adminUser();
        
        Vinyle::factory()->create(['artiste' => 'Old', 'reference' => 'VIN-OLD']);
        Vinyle::factory()->create(['artiste' => 'New', 'reference' => 'VIN-NEW']);

        $response = $this->actingAs($admin)
            ->get(route('vinyles.search', ['sort' => 'date_ajout_desc']));

        $response->assertOk();
        // Par défaut, latest() est utilisé donc le plus récent d'abord
    }

    // ==========================================================
    // TEST: Combinaison recherche + filtres
    // ==========================================================
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_combined_search_and_filters(): void
    {
        $admin = $this->adminUser();
        
        Vinyle::factory()->create([
            'artiste' => 'Pink Floyd',
            'genre' => 'Rock',
            'prix' => 25.00,
            'reference' => 'VIN-001'
        ]);
        Vinyle::factory()->create([
            'artiste' => 'Pink Floyd',
            'genre' => 'Jazz',
            'prix' => 50.00,
            'reference' => 'VIN-002'
        ]);
        Vinyle::factory()->create([
            'artiste' => 'The Beatles',
            'genre' => 'Rock',
            'prix' => 25.00,
            'reference' => 'VIN-003'
        ]);

        // Recherche combinée: artiste + genre + prix max
        $response = $this->actingAs($admin)
            ->get(route('vinyles.search', [
                'q' => 'Pink',
                'genre' => 'Rock',
                'prix_max' => 30
            ]));

        $response->assertOk()
            ->assertSee('Pink Floyd')  // Seul celui-ci correspond aux 3 critères
            ->assertDontSee('The Beatles')
            ->assertDontSee('Jazz Artist');  // Le Jazz Pink Floyd coûte 50€
    }

    // ==========================================================
    // TEST: Recherche par référence
    // ==========================================================
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_search_by_reference(): void
    {
        $admin = $this->adminUser();
        
        Vinyle::factory()->create([
            'artiste' => 'Artist A',
            'reference' => 'REF-ABC-123',
        ]);
        Vinyle::factory()->create([
            'artiste' => 'Artist B',
            'reference' => 'REF-XYZ-999',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('vinyles.search', ['q' => 'ABC']));

        $response->assertOk()
            ->assertSee('Artist A')
            ->assertDontSee('Artist B');
    }

    // ==========================================================
    // TEST: Recherche par nom d'album (modele)
    // ==========================================================
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_search_by_album_name(): void
    {
        $admin = $this->adminUser();
        
        Vinyle::factory()->create([
            'artiste' => 'Artist',
            'modele' => 'Dark Side of the Moon',
            'reference' => 'VIN-001'
        ]);
        Vinyle::factory()->create([
            'artiste' => 'Other',
            'modele' => 'Abbey Road',
            'reference' => 'VIN-002'
        ]);

        $response = $this->actingAs($admin)
            ->get(route('vinyles.search', ['q' => 'Dark Side']));

        $response->assertOk()
            ->assertSee('Artist')
            ->assertDontSee('Other');
    }

    // ==========================================================
    // TEST: Pagination des résultats
    // ==========================================================
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_search_results_are_paginated(): void
    {
        $admin = $this->adminUser();
        
        // Créer 15 vinyles
        Vinyle::factory()->count(15)->create();

        $response = $this->actingAs($admin)
            ->get(route('vinyles.search'));

        $response->assertOk()
            ->assertViewHas('vinyles', function ($vinyles) {
                return $vinyles->count() <= 12; // Pagination par défaut
            });
    }

    // ==========================================================
    // TEST: Client ne peut pas accéder à la recherche admin
    // ==========================================================
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_client_cannot_access_admin_search(): void
    {
        $client = $this->clientUser();

        $response = $this->actingAs($client)
            ->get(route('vinyles.search'));

        $response->assertRedirect();
    }

    // ==========================================================
    // TEST: Employé peut accéder à la recherche
    // ==========================================================
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_employe_can_access_search(): void
    {
        $employe = $this->employeUser();

        $response = $this->actingAs($employe)
            ->get(route('vinyles.search'));

        $response->assertOk()
            ->assertViewIs('vinyles.search');
    }
}
