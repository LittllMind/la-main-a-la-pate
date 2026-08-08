<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectThreeVersionEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_three_version_tabs_on_edit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create([
            'user_id' => $admin->id,
            'body' => 'Body travail',
            'citizen_body' => 'Body citoyen',
            'public_body' => 'Body public',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('subjects.edit', $subject->slug));

        $response->assertOk();
        $response->assertSee('Travail');
        $response->assertSee('Citoyen');
        $response->assertSee('Public');
    }

    public function test_admin_can_update_three_bodies(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create([
            'user_id' => $admin->id,
            'title' => 'Mon sujet',
        ]);

        $response = $this->actingAs($admin)
            ->put(route('subjects.update', $subject->slug), [
                'theme' => 'Amenagement',
                'title' => 'Mon sujet',
                'body' => 'Nouveau body travail',
                'citizen_body' => 'Nouveau body citoyen',
                'public_body' => 'Nouveau body public',
                'change_summary' => 'Triple version',
            ]);

        $response->assertRedirect(route('subjects.show', $subject->slug));

        $subject->refresh();
        $this->assertEquals('Nouveau body travail', $subject->body);
        $this->assertEquals('Nouveau body citoyen', $subject->citizen_body);
        $this->assertEquals('Nouveau body public', $subject->public_body);
    }

    public function test_non_admin_cannot_see_citizen_and_public_tabs(): void
    {
        $citizen = User::factory()->create(['role' => 'citoyen']);
        $subject = Subject::factory()->create([
            'user_id' => $citizen->id,
            'status' => 'published',
            'visibility' => 'citoyen',
        ]);

        $response = $this->actingAs($citizen)
            ->get(route('subjects.edit', $subject->slug));

        $response->assertOk();
        $response->assertDontSee('name="citizen_body"');
        $response->assertDontSee('name="public_body"');
    }
}
