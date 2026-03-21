<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bougie>
 */
class BougieFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $parfums = ['Vanille', 'Lavande', 'Rose', 'Cèdre', 'Coco', 'Menthe', 'Ylang-Ylang', 'Santal', 'Figue', 'Agrumes'];
        $formats = ['120g', '200g', '300g'];
        $typesCire = ['soja', 'paraffine', 'cire végétale', 'beeswax'];

        return [
            'reference' => 'BOUG-' . strtoupper(fake()->unique()->bothify('###??')),
            'parfum' => fake()->randomElement($parfums),
            'nom' => fake()->words(2, true),
            'collection' => fake()->optional()->words(1, true),
            'format' => fake()->randomElement($formats),
            'type_cire' => fake()->randomElement($typesCire),
            'temps_brulure' => fake()->numberBetween(20, 60),
            'notes' => fake()->optional()->sentence(),
            'prix' => fake()->randomFloat(2, 15, 50),
            'quantite' => fake()->numberBetween(0, 100),
            'seuil_alerte' => 5,
        ];
    }
}
