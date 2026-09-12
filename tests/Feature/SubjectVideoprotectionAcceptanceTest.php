<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\VideoprotectionSubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectVideoprotectionAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private function seedAndAuthAdmin(): User
    {
        User::factory()->create(['role' => 'admin', 'email_verified_at' => now(), 'requires_setup' => false]);
        Category::factory()->create(['name' => 'Conseil municipal & Gouvernance', 'slug' => 'conseil-municipal-gouvernance']);
        $this->artisan('db:seed', ['--class' => VideoprotectionSubjectSeeder::class]);
        return User::where('role', 'admin')->firstOrFail();
    }

    public function test_guest_subject_page_returns_404(): void
    {
        $this->seedAndAuthAdmin();
        $this->get('/sujets/videoprotection')->assertStatus(404);
    }

    public function test_guest_sujets_redirects(): void
    {
        $this->seedAndAuthAdmin();
        $response = $this->get('/sujets');
        $response->assertStatus(302);
        $this->assertNotEquals('http://127.0.0.1:8001/sujets', $response->headers->get('Location'));
    }

    public function test_guest_sujets_arbre_redirects(): void
    {
        $this->seedAndAuthAdmin();
        $response = $this->get('/sujets/arbre');
        $response->assertStatus(302);
        $this->assertNotEquals('http://127.0.0.1:8001/sujets/arbre', $response->headers->get('Location'));
    }

    public function test_guest_dashboard_redirects(): void
    {
        $this->seedAndAuthAdmin();
        $response = $this->get('/dashboard');
        $response->assertStatus(302);
        $this->assertNotEquals('http://127.0.0.1:8001/dashboard', $response->headers->get('Location'));
    }

    public function test_admin_can_view_videoprotection(): void
    {
        $admin = $this->seedAndAuthAdmin();
        $this->actingAs($admin)
            ->get('/sujets/videoprotection')
            ->assertOk()
            ->assertSee('Videoprotection au Rozier');
    }

    public function test_admin_sujets_list_contains_videoprotection(): void
    {
        $admin = $this->seedAndAuthAdmin();
        $this->actingAs($admin)
            ->get('/sujets')
            ->assertOk()
            ->assertSee('Videoprotection au Rozier');
    }

    public function test_admin_sujets_arbre_contains_category(): void
    {
        $admin = $this->seedAndAuthAdmin();
        $this->actingAs($admin)
            ->get('/sujets/arbre')
            ->assertOk();
    }

    public function test_no_new_public_subjects_created(): void
    {
        $this->seedAndAuthAdmin();
        $this->assertDatabaseCount('subjects', 1);
        $this->assertDatabaseHas('subjects', ['slug' => 'videoprotection', 'public_status' => 'draft', 'public_is_listed' => false]);
    }
}
