<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $username = $this->faker->unique()->userName();
        return [
            'username' => $username,
            'name' => $this->faker->name(),
            'pseudonyme' => $username,
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'password',
            'commune' => 'Le Rozier',
            'role' => 'citoyen',
            'color' => sprintf('#%06X', mt_rand(0, 0xFFFFFF)),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }
}
