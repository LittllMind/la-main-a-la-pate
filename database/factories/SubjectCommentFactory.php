<?php

namespace Database\Factories;

use App\Models\Subject;
use App\Models\SubjectComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory\u003cSubjectComment\u003e
 */
class SubjectCommentFactory extends Factory
{
    protected $model = SubjectComment::class;

    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(12),
        ];
    }
}
