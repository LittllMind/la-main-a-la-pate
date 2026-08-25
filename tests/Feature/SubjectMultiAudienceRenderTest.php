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

    public function test_citizen_and_admin_on_public_route_see_public_body(): void
    {
        $subject = $this->setupSubject();
        $citizen = User::factory()->create(['role' => 'citoyen', 'email_verified_at' => now(), 'requires_setup' => false]);
        $admin = User::where('role', 'admin')->first();

        // Route canonique publique : public_body pour tout le monde.
        $this->actingAs($citizen)->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('Public Body')
            ->assertDontSee('Citizen Body')
            ->assertDontSee('Admin Only');

        $this->actingAs($admin)->get(route('subjects.show', $subject->slug))
            ->assertOk()
            ->assertSee('Public Body')
            ->assertDontSee('Citizen Body')
            ->assertDontSee('Admin Only');
    }

    public function test_citizen_sees_citizen_body_in_preview(): void
    {
        $subject = $this->setupSubject();
        $citizen = User::factory()->create(['role' => 'citoyen', 'email_verified_at' => now(), 'requires_setup' => false]);
        $admin = User::where('role', 'admin')->first();

        $this->actingAs($admin)
            ->get(route('subjects.preview', [$subject->slug, 'citizen']))
            ->assertOk()
            ->assertSee('Citizen Body')
            ->assertDontSee('Admin Only');
    }

    public function test_admin_sees_working_body_in_edit(): void
    {
        $subject = $this->setupSubject();
        $admin = User::where('role', 'admin')->first();

        $this->actingAs($admin)
            ->get(route('subjects.edit', $subject->slug))
            ->assertOk()
            ->assertSee('Admin Only');
    }
}
