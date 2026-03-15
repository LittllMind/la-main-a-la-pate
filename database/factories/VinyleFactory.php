<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory\<<App\Models\Vinyle>
 */
class VinyleFactory extends Factory
{
    private static int $counter = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        self::$counter++;
        
        return [
            'reference' => 'VIN-' . str_pad(self::$counter, 4, '0', STR_PAD_LEFT),
            'artiste' => fake()->name(),
            'modele' => fake()->word() . ' Edition',
            'genre' => fake()->randomElement(['Rock', 'Jazz', 'Classique', 'Pop', 'Electro']),
            'style' => fake()->randomElement(['33 Tours', '45 Tours']),
            'prix' => fake()->randomFloat(2, 10, 100),
            'quantite' => fake()->numberBetween(0, 50),
            'seuil_alerte' => 5,
        ];
    }

    /**
     * Vinyle with low stock
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantite' => fake()->numberBetween(1, 3),
            'seuil_alerte' => 5,
        ]);
    }

    /**
     * Out of stock vinyle
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantite' => 0,
        ]);
    }
}