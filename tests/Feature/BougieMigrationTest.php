<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use App\Models\Bougie;

class BougieMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 2.2.1: La table bougies existe
     */
    public function test_table_bougies_existe()
    {
        $this->artisan('migrate');
        
        $this->assertTrue(
            Schema::hasTable('bougies'),
            'La table bougies doit exister'
        );
    }

    /**
     * Test 2.2.2: Les colonnes correctes sont présentes
     */
    public function test_table_bougies_a_les_colonnes_correctes()
    {
        $this->artisan('migrate');
        
        $expectedColumns = [
            'id',
            'reference',
            'parfum',
            'nom',
            'collection',
            'format',
            'type_cire',
            'temps_brulure',
            'notes',
            'prix',
            'quantite',
            'seuil_alerte',
            'created_at',
            'updated_at',
        ];
        
        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('bougies', $column),
                "La colonne {$column} doit exister dans la table bougies"
            );
        }
    }

    /**
     * Test 2.2.3: Le modèle Bougie est fonctionnel
     */
    public function test_modele_bougie_est_fonctionnel()
    {
        $this->artisan('migrate');
        
        Bougie::unguard();
        $bougie = new Bougie([
            'reference' => 'BOUG-TEST-001',
            'parfum' => 'Vanille',
            'nom' => 'Bougie Test',
            'prix' => 25.99,
            'quantite' => 10,
        ]);
        
        $bougie->save();
        Bougie::reguard();
        
        $this->assertDatabaseHas('bougies', [
            'reference' => 'BOUG-TEST-001',
            'parfum' => 'Vanille',
            'nom' => 'Bougie Test',
        ]);
    }

    /**
     * Test 2.2.4: La factory fonctionne
     */
    public function test_factory_bougie_fonctionne()
    {
        $this->artisan('migrate');
        
        $bougie = Bougie::factory()->create();
        
        $this->assertInstanceOf(Bougie::class, $bougie);
        $this->assertNotNull($bougie->reference);
        $this->assertNotNull($bougie->parfum);
        $this->assertNotNull($bougie->nom);
        $this->assertNotNull($bougie->prix);
        $this->assertDatabaseHas('bougies', [
            'id' => $bougie->id,
        ]);
    }

    /**
     * Test 2.2.5: La référence est unique
     */
    public function test_reference_bougie_est_unique()
    {
        $this->artisan('migrate');
        
        Bougie::create([
            'reference' => 'BOUG-UNIQUE-001',
            'parfum' => 'Lavande',
            'nom' => 'Première',
            'prix' => 20.00,
            'quantite' => 5,
        ]);
        
        $this->assertDatabaseHas('bougies', [
            'reference' => 'BOUG-UNIQUE-001',
        ]);
        
        // Essayer de créer une bougie avec la même référence doit échouer
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Bougie::create([
            'reference' => 'BOUG-UNIQUE-001',
            'parfum' => 'Vanille',
            'nom' => 'Deuxième',
            'prix' => 25.00,
            'quantite' => 10,
        ]);
    }

    /**
     * Test 2.2.6: Les valeurs par défaut sont correctes
     */
    public function test_valeurs_par_defaut_sont_correctes()
    {
        $this->artisan('migrate');
        
        $bougie = Bougie::create([
            'reference' => 'BOUG-DEFAULT-001',
            'parfum' => 'Test',
            'nom' => 'Test',
            'prix' => 15.00,
        ]);
        
        // Recharger pour obtenir les valeurs par défaut de la BDD
        $bougie->refresh();
        
        $this->assertEquals(0, $bougie->quantite);
        $this->assertEquals(5, $bougie->seuil_alerte);
    }
}
