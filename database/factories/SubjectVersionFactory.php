<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectVersionFactory extends Factory
{
    protected $model = \App\Models\SubjectVersion::class;

    public function definition(): array
    {
        return [
            'body' => $this->faker->paragraphs(2, true),
            'change_summary' => $this->faker->sentence(),
        ];
    }
}
