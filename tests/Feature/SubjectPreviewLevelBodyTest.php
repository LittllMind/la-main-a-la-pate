<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectPreviewLevelBodyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function preview_citizen_renders_citizen_body_not_instruction_body(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create([
            'title' => 'Test CITIZEN',
            'body' => 'MARQUEUR-INSTRUCTION',
            'citizen_body' => 'MARQUEUR-CITOYEN',
            'citizen_status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->get(route('subjects.preview', [$subject->slug, 'citizen']))
            ->assertOk()
            ->assertSee('MARQUEUR-CITOYEN')
            ->assertDontSee('MARQUEUR-INSTRUCTION');
    }

    /** @test */
    public function preview_public_renders_public_body_not_citizen_body(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create([
            'title' => 'Test PUBLIC',
            'public_body' => 'MARQUEUR-PUBLIC',
            'citizen_body' => 'MARQUEUR-CITOYEN',
            'public_status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->get(route('subjects.preview', [$subject->slug, 'public']))
            ->assertOk()
            ->assertSee('MARQUEUR-PUBLIC')
            ->assertDontSee('MARQUEUR-CITOYEN');
    }

    /** @test */
    public function preview_citizen_strips_h1_when_title_matches(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create([
            'title' => 'Test Match',
            'citizen_body' => "# Test Match\n\nL'essentiel ici.",
            'citizen_status' => 'draft',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('subjects.preview', [$subject->slug, 'citizen']));

        $response->assertOk();
        // Il doit y avoir exactement 1 H1 (le titre Blade)
        $html = $response->getContent();
        preg_match_all('/<h1[^>]*>/', $html, $matches);
        $this->assertCount(1, $matches[0], 'Expected exactly one H1 in citizen preview (Blade title only)');
        $this->assertStringContainsString("L'essentiel ici.", $html);
    }
}
