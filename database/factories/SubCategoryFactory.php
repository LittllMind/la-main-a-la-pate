<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory\u003c\App\Models\SubCategory>
 */
class SubCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(2, true),
            'slug' => str(fake()->slug(2))->lower(),
            'color' => fake()->hexColor(),
            'category_id' => \App\Models\Category::factory(),
        ];
    }
}
