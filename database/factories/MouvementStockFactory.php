<?php

namespace Database\Factories;

use App\Models\MouvementStock;
use App\Models\Vinyle;
use App\Models\Fond;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MouvementStockFactory extends Factory
{
    protected $model = MouvementStock::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['entree', 'sortie']),
            'produit_type' => fake()->randomElement(['vinyle', 'miroir', 'dore', 'pochette']),
            'produit_id' => fake()->numberBetween(1, 100),
            'quantite' => fake()->numberBetween(1, 10),
            'date_mouvement' => fake()->dateTimeBetween('-1 month', 'now'),
            'user_id' => User::factory(),
            'reference' => 'CMD-' . fake()->year() . '-' . fake()->numberBetween(100, 999),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * État : Entrée
     */
    public function entree(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'entree',
        ]);
    }

    /**
     * État : Sortie
     */
    public function sortie(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'sortie',
        ]);
    }

    /**
     * État : Produit Vinyle
     */
    public function pourVinyle(): static
    {
        return $this->state(fn () => [
            'produit_type' => 'vinyle',
            'produit_id' => Vinyle::factory(),
        ]);
    }

    /**
     * État : Produit Miroir
     */
    public function pourMiroir(): static
    {
        return $this->state(fn () => [
            'produit_type' => 'miroir',
            'produit_id' => Fond::factory()->miroir(),
        ]);
    }
}
