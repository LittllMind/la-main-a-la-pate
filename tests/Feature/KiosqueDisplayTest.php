<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vinyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KiosqueDisplayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * Test que le kiosque affiche bien la liste des vinyles
     */
    public function test_kiosque_affiche_les_vinyles(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        
        // Créer 5 vinyles
        $vinyles = Vinyle::factory()->count(5)->create();
        
        $response = $this->actingAs($user)->get('/kiosque');
        
        $response->assertSuccessful();
        
        // Vérifier que vinylesData est passé à la vue
        $vinylesData = $response->viewData('vinylesData');
        $this->assertNotNull($vinylesData, 'vinylesData ne devrait pas être null');
        $this->assertIsArray($vinylesData, 'vinylesData devrait être un tableau');
        $this->assertCount(5, $vinylesData, 'vinylesData devrait contenir 5 vinyles');
        
        // Vérifier la structure du premier élément
        $first = $vinylesData[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('artiste', $first);
        $this->assertArrayHasKey('modele', $first);
        $this->assertArrayHasKey('prix', $first);
        $this->assertArrayHasKey('quantite', $first);
        $this->assertArrayHasKey('image', $first);
    }
    
    /**
     * @test
     * Test que le kiosque fonctionne sans vinyles
     */
    public function test_kiosque_fonctionne_sans_vinyles(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        
        $response = $this->actingAs($user)->get('/kiosque');
        
        $response->assertSuccessful();
        
        $vinylesData = $response->viewData('vinylesData');
        $this->assertIsArray($vinylesData);
        $this->assertEmpty($vinylesData);
    }
}
