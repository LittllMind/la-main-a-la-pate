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
            'category_id' => fn () => \App\Models\Category::factory()->create()->id,
            'sub_category_id' => fn (array $attributes) => \App\Models\SubCategory::factory()->create([
                'category_id' => $attributes['category_id'],
            ])->id,
            'theme' => fn (array $attributes) => \App\Models\Category::find($attributes['category_id'])->name,
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
            'visibility' => 'citoyen',
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}
