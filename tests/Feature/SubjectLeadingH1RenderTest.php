<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\CategorySubcategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SubjectLeadingH1RenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySubcategorySeeder::class);
    }

    /** @test */
    public function show_suppresses_template_h1_when_body_has_leading_h1(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::first();
        $sub = SubCategory::first();

        $subject = Subject::factory()->create([
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'sub_category_id' => $sub->id,
            'title' => 'Titre template',
            'body' => "# Titre canonique\n\nParagraphe.\n",
        ]);

        $response = $this->actingAs($admin)
            ->get('/sujets/' . $subject->slug);

        $response->assertOk();
        $this->assertSingleH1($response);
        $response->assertSee('Titre canonique');
        $this->assertSame(
            "# Titre canonique\n\nParagraphe.\n",
            $subject->fresh()->body,
            'Le body ne doit pas être modifié'
        );
    }

    /** @test */
    public function show_keeps_template_h1_when_body_has_no_leading_h1(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::first();
        $sub = SubCategory::first();

        $subject = Subject::factory()->create([
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'sub_category_id' => $sub->id,
            'title' => 'Titre template',
            'body' => "## Sous-section\n\nParagraphe.\n",
        ]);

        $response = $this->actingAs($admin)
            ->get('/sujets/' . $subject->slug);

        $response->assertOk();
        $this->assertSingleH1($response);
        $response->assertSee('Titre template');
    }

    /** @test */
    public function preview_citizen_suppresses_template_h1_when_body_has_leading_h1(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::first();
        $sub = SubCategory::first();

        $subject = Subject::factory()->create([
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'sub_category_id' => $sub->id,
            'title' => 'Titre template',
            'citizen_body' => "# Titre citoyen\n\nRésumé.\n",
        ]);

        $response = $this->actingAs($admin)
            ->get('/sujets/' . $subject->slug . '/apercu/citizen');

        $response->assertOk();
        $this->assertSingleH1($response);
        $response->assertSee('Titre citoyen');
        $this->assertSame(
            "# Titre citoyen\n\nRésumé.\n",
            $subject->fresh()->citizen_body,
            'Le citizen_body ne doit pas être modifié'
        );
    }

    /** @test */
    public function preview_citizen_keeps_template_h1_when_body_has_no_leading_h1(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::first();
        $sub = SubCategory::first();

        $subject = Subject::factory()->create([
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'sub_category_id' => $sub->id,
            'title' => 'Titre template',
            'citizen_body' => "## Résumé\n\nContenu.\n",
        ]);

        $response = $this->actingAs($admin)
            ->get('/sujets/' . $subject->slug . '/apercu/citizen');

        $response->assertOk();
        $this->assertSingleH1($response);
        $response->assertSee('Titre template');
    }

    private function assertSingleH1(TestResponse $response, ?string $expectedText = null): void
    {
        $html = $response->getContent();
        $h1Count = preg_match_all('/<h1\b/', $html);
        $this->assertSame(1, $h1Count, 'La page doit contenir exactement un H1 visuel');

        if ($expectedText !== null) {
            $response->assertSee($expectedText);
        }
    }
}
