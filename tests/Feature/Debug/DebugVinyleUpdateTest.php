<?php

namespace Tests\Feature\Debug;

use App\Models\Vinyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugVinyleUpdateTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function debug_update_vinyle(): void
    {
        $admin = $this->adminUser();
        
        $vinyle = Vinyle::factory()->create([
            'artiste' => 'Old Artist',
            'modele' => 'Old Model',
            'prix' => 20.00,
        ]);
        
        dump("Vinyle created: " . $vinyle->id);
        
        try {
            $response = $this->actingAs($admin)
                ->patch(route('vinyles.update', $vinyle), [
                    'reference' => $vinyle->reference,
                    'artiste' => 'New Artist',
                    'modele' => 'New Model',
                    'prix' => 35.00,
                    'quantite' => 10,
                ]);
            
            dump("Response status: " . $response->getStatusCode());
            
            if ($response->getStatusCode() === 500) {
                dump("Response content:", $response->getContent());
            }
        } catch (\Exception $e) {
            dump("Exception: " . $e->getMessage());
            dump($e->getTraceAsString());
            throw $e;
        }
        
        $response->assertRedirect();
    }
}
