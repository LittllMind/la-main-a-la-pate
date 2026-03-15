<?php

namespace Database\Factories;

use App\Models\Fond;
use Illuminate\Database\Eloquent\Factories\Factory;

class FondFactory extends Factory
{
    protected $model = Fond::class;
    
    private static $counter = 0;

    public function definition(): array
    {
        $prixAchat = fake()->randomFloat(2, 2, 10);
        self::$counter++;
        
        return [
            'type' => 'fond_test_' . self::$counter . '_' . uniqid(),
            'quantite' => fake()->numberBetween(5, 100),
            'prix_achat' => $prixAchat,
            'prix_vente' => $prixAchat * fake()->randomFloat(2, 1.5, 3),
            'actif' => true,
        ];
    }

    /**
     * État : Stock critique
     */
    public function critique(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantite' => 0,
        ]);
    }

    /**
     * État : Miroir
     */
    public function miroir(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'miroir_test_' . uniqid(),
        ]);
    }

    /**
     * État : Doré
     */
    public function dore(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'dore_test_' . uniqid(),
        ]);
    }

    /**
     * État : Standard
     */
    public function standard(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'standard_test_' . uniqid(),
        ]);
    }
}
