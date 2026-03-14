<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $user = User::factory()->create();
        
        return [
            'numero_commande' => 'CMD-' . now()->year . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'user_id' => $user->id,
            'nom' => $user->name ?? fake()->lastName(),
            'prenom' => fake()->firstName(),
            'email' => $user->email ?? fake()->email(),
            'telephone' => fake()->phoneNumber(),
            'adresse' => fake()->streetAddress(),
            'code_postal' => fake()->postcode(),
            'ville' => fake()->city(),
            'total' => fake()->randomFloat(2, 20, 200),
            'statut' => fake()->randomElement(['en_attente', 'payee', 'en_preparation', 'prete', 'livree', 'annulee']),
        ];
    }

    /**
     * État : En attente
     */
    public function pending(): static
    {
        return $this->state(fn () => [
            'statut' => 'en_attente',
        ]);
    }

    /**
     * État : Payée (statut payee)
     */
    public function paid(): static
    {
        return $this->state(fn () => [
            'statut' => 'payee',
        ]);
    }

    /**
     * État : Prête
     */
    public function ready(): static
    {
        return $this->state(fn () => [
            'statut' => 'prete',
        ]);
    }

    /**
     * État : Livrée
     */
    public function delivered(): static
    {
        return $this->state(fn () => [
            'statut' => 'livree',
        ]);
    }

    /**
     * État : Vente mode marché
     */
    public function marche(): static
    {
        return $this->state(fn () => [
            'source' => 'marche',
            'mode_paiement_marche' => fake()->randomElement(['cash', 'cb_terminal', 'cheque', 'virement']),
        ]);
    }

    /**
     * État : Vente kiosque (en ligne)
     */
    public function kiosque(): static
    {
        return $this->state(fn () => [
            'source' => 'kiosque',
        ]);
    }
}