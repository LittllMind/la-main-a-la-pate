<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Vinyle;
use App\Models\Fond;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $quantite = fake()->numberBetween(1, 5);
        $prixUnitaire = fake()->randomFloat(2, 15, 50);
        
        return [
            'order_id' => Order::factory(),
            'vinyle_id' => Vinyle::factory(),
            'fond_id' => fake()->boolean(80) ? Fond::factory() : null,
            'titre_vinyle' => fake()->words(3, true),
            'artiste_vinyle' => fake()->name(),
            'reference_vinyle' => strtoupper(fake()->bothify('??###')),
            'quantite' => $quantite,
            'prix_unitaire' => $prixUnitaire,
            'total' => $quantite * $prixUnitaire,
        ];
    }

    /**
     * État : Avec fond
     */
    public function withFond(): static
    {
        return $this->state(fn () => [
            'fond_id' => Fond::factory(),
        ]);
    }

    /**
     * État : Sans fond
     */
    public function withoutFond(): static
    {
        return $this->state(fn () => [
            'fond_id' => null,
        ]);
    }
}
