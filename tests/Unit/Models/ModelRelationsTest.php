<?php

namespace Tests\Unit\Models;

use App\Models\Fond;
use App\Models\LigneVente;
use App\Models\Vente;
use App\Models\Vinyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Un vinyle a plusieurs lignes de vente (via relation hasMany)
     */
    public function test_vinyle_has_many_ventes(): void
    {
        $vinyle = Vinyle::factory()->create();
        $vente = Vente::factory()->create();
        
        // Créer 3 lignes de vente pour ce vinyle
        LigneVente::factory()->count(3)->create([
            'vinyle_id' => $vinyle->id,
            'vente_id' => $vente->id,
        ]);

        // La relation ventes sur Vinyle retourne des LigneVente
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $vinyle->ventes());
        $this->assertCount(3, $vinyle->ventes);
        $this->assertInstanceOf(LigneVente::class, $vinyle->ventes->first());
    }

    /**
     * Test: Un fond peut avoir plusieurs vinyles vendus via les lignes de vente
     * Teste la relation hasManyThrough ou hasMany sur les lignes
     */
    public function test_fonds_has_many_vinyles(): void
    {
        $fond = Fond::factory()->create(['type' => 'miroir_test']);
        
        // Créer un vinyle
        $vinyle1 = Vinyle::factory()->create();
        $vinyle2 = Vinyle::factory()->create();
        $vente = Vente::factory()->create();
        
        // Créer des lignes de vente avec ce type de fond
        LigneVente::factory()->create([
            'vente_id' => $vente->id,
            'vinyle_id' => $vinyle1->id,
            'fond' => 'miroir_test',
        ]);
        
        LigneVente::factory()->create([
            'vente_id' => $vente->id,
            'vinyle_id' => $vinyle2->id,
            'fond' => 'miroir_test',
        ]);

        // Vérifier la relation lignesVentes existe
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $fond->lignesVentes());
        $this->assertCount(2, $fond->lignesVentes);
        
        // Vérifier la relation vinylesVendus (hasManyThrough)
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasManyThrough::class, $fond->vinylesVendus());
    }

    /**
     * Test: Une ligne de vente appartient à un vinyle
     */
    public function test_ligne_vente_belongs_to_vinyle(): void
    {
        $vinyle = Vinyle::factory()->create();
        $vente = Vente::factory()->create();
        
        $ligneVente = LigneVente::factory()->create([
            'vinyle_id' => $vinyle->id,
            'vente_id' => $vente->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $ligneVente->vinyle());
        $this->assertTrue($ligneVente->vinyle->is($vinyle));
    }

    /**
     * Test: Une vente a plusieurs lignes de vente
     */
    public function test_vente_has_many_lignes(): void
    {
        $vente = Vente::factory()->create();
        
        LigneVente::factory()->count(3)->create([
            'vente_id' => $vente->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $vente->lignes());
        $this->assertCount(3, $vente->lignes);
    }

    /**
     * Test: Une ligne de vente appartient à une vente
     */
    public function test_ligne_vente_belongs_to_vente(): void
    {
        $vente = Vente::factory()->create();
        $ligneVente = LigneVente::factory()->create([
            'vente_id' => $vente->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $ligneVente->vente());
        $this->assertTrue($ligneVente->vente->is($vente));
    }
}
