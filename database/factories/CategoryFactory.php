<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory\u003c\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->sentence(2, true);
        return [
            'name' => $name,
            'slug' => str(fake()->slug(2))->lower(),
            'color' => fake()->hexColor(),
            'icon' => fake()->emoji(),
        ];
    }
}
