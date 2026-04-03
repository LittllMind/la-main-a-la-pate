<?php

namespace Tests\Feature;

use App\Models\Vinyle;
use Tests\TestCase;

class KiosqueTest extends TestCase
{
    public function test_kiosque_page_is_accessible(): void
    {
        $response = $this->get('/kiosque');
        $response->assertStatus(200);
    }

    public function test_kiosque_displays_products(): void
    {
        Vinyle::factory()->count(3)->create();
        
        $response = $this->get('/kiosque');
        $response->assertStatus(200)
                 ->assertSee('Ajouter au panier');
    }
}
