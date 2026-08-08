<?php

namespace Database\Factories;

use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\VisibilityLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectDocumentFactory extends Factory
{
    protected $model = SubjectDocument::class;

    public function definition(): array
    {
        $filename = $this->faker->word . '.' . $this->faker->fileExtension();

        return [
            'subject_id' => Subject::factory(),
            'filename' => $filename,
            'stored_filename' => $this->faker->uuid . '_' . $filename,
            'path' => 'documents/' . $this->faker->uuid,
            'disk' => 'documents',
            'mime_type' => $this->faker->mimeType(),
            'size' => $this->faker->randomNumber(5),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'category' => 'source',
            'position' => 0,
            'visibility' => VisibilityLevel::Working->value, // defaut conservateur
        ];
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => ['visibility' => VisibilityLevel::Public->value]);
    }

    public function citizen(): static
    {
        return $this->state(fn (array $attributes) => ['visibility' => VisibilityLevel::Citizen->value]);
    }

    public function working(): static
    {
        return $this->state(fn (array $attributes) => ['visibility' => VisibilityLevel::Working->value]);
    }
}
