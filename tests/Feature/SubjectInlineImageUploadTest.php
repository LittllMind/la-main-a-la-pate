<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubjectInlineImageUploadTest extends TestCase
{
    public function test_inline_image_upload_attaches_to_subject_and_returns_url()
    {
        Storage::fake('subject_images');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

        $image = UploadedFile::fake()->image('diagramme.jpg', 800, 600);

        $response = $this->actingAs($user)
            ->postJson(route('subjects.images.upload', $subject->slug), [
                'file' => $image,
                'alt' => 'Diagramme PAC',
            ]);

        $response->assertOk()
            ->assertJsonStructure(['url', 'alt', 'filename'])
            ->assertJson(['alt' => 'Diagramme PAC']);

        $this->assertDatabaseHas('subject_images', [
            'subject_id' => $subject->id,
            'alt' => 'Diagramme PAC',
        ]);

        $dbImage = $subject->fresh()->images()->first();
        Storage::disk('subject_images')->assertExists($dbImage->getRawOriginal('path') ?: $dbImage->path);
    }

    public function test_inline_upload_requires_authenticated_owner_or_moderator()
    {
        Storage::fake('subject_images');

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $stranger = User::factory()->create(['email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $owner->id, 'status' => 'draft']);

        $image = UploadedFile::fake()->image('plan.jpg', 400, 300);

        $this->actingAs($stranger)
            ->postJson(route('subjects.images.upload', $subject->slug), ['file' => $image])
            ->assertForbidden();

        $this->assertDatabaseCount('subject_images', 0);
    }

    public function test_inline_upload_validates_file_type()
    {
        Storage::fake('subject_images');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $subject = Subject::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

        $badFile = UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf');

        $this->actingAs($user)
            ->postJson(route('subjects.images.upload', $subject->slug), ['file' => $badFile])
            ->assertUnprocessable();
    }
}
