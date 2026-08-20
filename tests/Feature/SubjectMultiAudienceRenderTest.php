<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectMultiAudienceRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setupSubject(): Subject
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now(), 'requires_setup' => false]);
        \App\Models\Category::factory()->create(['id' => 10, 'name' => 'Vie du village', 'slug' => 'vie-du-village']);
        \App\Models\SubCategory::factory()->create(['id' => 14, 'category_id' => 10, 'name' => 'Séraphothèque', 'slug' => 'seraphotheque']);

        return Subject::create([
            'user_id' => $admin->id,
            'category_id' => 10,
            'sub_category_id' => 14,
            'theme' => 'Vie du village',
            'title' => 'Test Audience',
            'slug' => 'test-audience',
            'body' => '## Admin Only',
            'citizen_body' => '## Citizen Body',
            'public_body' => '## Public Body',
            'status' => 'published',
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);
    }

    public function test_public_user_sees_public_body(): void
    {
        $subject = $this->setupSubject();

        $response = $this->get(route('subjects.show', $subject->slug));

        $response->assertOk();
        $response->assertSee('Public Body');
        $response->assertDontSee('Citizen Body');
        $response->assertDontSee('Admin Only');
    }

    public function test_citizen_sees_citizen_body_not_admin_body(): void
    {
        $subject = $this->setupSubject();
        $citizen = User::factory()->create(['role' => 'citoyen', 'email_verified_at' => now(), 'requires_setup' => false]);

        $response = $this->actingAs($citizen)->get(route('subjects.show', $subject->slug));

        $response->assertOk();
        $response->assertSee('Citizen Body');
        $response->assertDontSee('Admin Only');
    }

    public function test_admin_sees_full_body(): void
    {
        $subject = $this->setupSubject();
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('subjects.show', $subject->slug));

        $response->assertOk();
        $response->assertSee('Admin Only');
    }
}
