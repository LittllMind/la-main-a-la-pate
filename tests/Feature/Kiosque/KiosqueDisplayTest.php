<?php

namespace Tests\Feature\Kiosque;

use Tests\TestCase;
use App\Models\Vinyle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class KiosqueDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_kiosque_displays_vinyles(): void
    {
        // Créer des vinyles avec stock
        $vinyle1 = Vinyle::factory()->create([
            'artiste' => 'Daft Punk',
            'modele' => 'Random Access Memories',
            'quantite' => 5,
        ]);
        
        $vinyle2 = Vinyle::factory()->create([
            'artiste' => 'Pink Floyd',
            'modele' => 'Dark Side of the Moon',
            'quantite' => 3,
        ]);

        // Visiter le kiosque
        $response = $this->get(route('kiosque.index'));
        
        // Vérifier que la page s'affiche
        $response->assertStatus(200);
        
        // Vérifier que les vinyles sont dans le HTML
        $response->assertSee('Daft Punk');
        $response->assertSee('Pink Floyd');
        $response->assertSee('Random Access Memories');
    }

    public function test_kiosque_redirects_invalid_page(): void
    {
        // Créer un seul vinyle
        Vinyle::factory()->create(['quantite' => 5]);

        // Tenter d'accéder à page 2 (qui n'existe pas)
        $response = $this->get(route('kiosque.index', ['page' => 2]));
        
        // Doit rediriger vers la page 1
        $response->assertRedirect(route('kiosque.index'));
    }

    public function test_kiosque_displays_no_results_message_when_empty(): void
    {
        // Aucun vinyle créé
        $response = $this->get(route('kiosque.index'));
        
        $response->assertStatus(200);
        $response->assertSee('Aucun vinyle trouvé');
    }

    public function test_kiosque_handles_search_filter(): void
    {
        $vinyle = Vinyle::factory()->create([
            'artiste' => 'Daft Punk',
            'quantite' => 5,
        ]);
        
        Vinyle::factory()->create([
            'artiste' => 'Autre Artiste',
            'quantite' => 5,
        ]);

        $response = $this->get(route('kiosque.index', ['search' => 'Daft']));
        
        $response->assertStatus(200);
        $response->assertSee('Daft Punk');
        $response->assertDontSee('Autre Artiste');
    }
}
