<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\LigneVente;
use App\Models\Vente;
use App\Models\Vinyle;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LigneVente>
 */
class LigneVenteFactory extends Factory
{
    protected $model = LigneVente::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prixUnitaire = $this->faker->randomFloat(2, 10, 100);
        $quantite = $this->faker->numberBetween(1, 5);
        
        return [
            'vente_id' => Vente::factory(),
            'vinyle_id' => Vinyle::factory(),
            'titre_vinyle' => $this->faker->sentence(3),
            'quantite' => $quantite,
            'prix_unitaire' => $prixUnitaire,
            'total' => $prixUnitaire * $quantite,
            'fond' => $this->faker->randomElement(['standard', 'miroir', 'dore']),
        ];
    }
}
