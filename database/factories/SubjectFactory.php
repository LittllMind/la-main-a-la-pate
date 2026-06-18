<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Str;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(4);
        $slugBase = Str::slug($title);

        return [
            'user_id' => User::factory(),
            'theme' => collect(['Séraphothèque', 'Urbanisme', 'Mémoire', 'Nature', 'Vie du village'])->random(),
            'title' => $title,
            'slug' => function (array $attributes) use ($slugBase) {
                $counter = 1;
                $slug = $slugBase;
                while (Subject::where('slug', $slug)->exists()) {
                    $slug = $slugBase . '-' . $counter++;
                }
                return $slug;
            },
            'body' => '<p>' . $this->faker->paragraph() . '</p>',
            'status' => 'published',
        ];
    }
}
