<?php

namespace Tests\Unit;

use App\Models\Bougie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BougieTest extends TestCase
{
    use RefreshDatabase;

    public function test_peut_creer_une_bougie(): void
    {
        $bougie = Bougie::factory()->create([
            'reference' => 'BOUG-TEST-001',
            'parfum' => 'Vanille',
            'nom' => 'Douce Vanille',
            'prix' => 25.50,
        ]);

        $this->assertDatabaseHas('bougies', [
            'reference' => 'BOUG-TEST-001',
            'parfum' => 'Vanille',
            'nom' => 'Douce Vanille',
            'prix' => 25.50,
        ]);
    }

    public function test_la_factory_genere_10_bougies(): void
    {
        Bougie::factory()->count(10)->create();

        $this->assertEquals(10, Bougie::count());
    }
}