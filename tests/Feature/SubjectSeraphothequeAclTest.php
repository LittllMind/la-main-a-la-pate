<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectSeraphothequeAclTest extends TestCase
{
    use RefreshDatabase;

    protected function ingestSeraphotheque(): Subject
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now(), 'requires_setup' => false]);
        $category = \App\Models\Category::factory()->create(['id' => 10, 'name' => 'Vie du village', 'slug' => 'vie-du-village']);
        \App\Models\SubCategory::factory()->create(['id' => 14, 'category_id' => 10, 'name' => 'Séraphothèque', 'slug' => 'seraphotheque']);

        $pack = storage_path('framework/testing/seraphotheque-pack');
        @mkdir($pack, 0755, true);
        @mkdir($pack . '/archives-LEX/OPS-originaux-LEX/04-procedure', 0755, true);
        @mkdir($pack . '/archives-LEX/LEX-26-042', 0755, true);

        file_put_contents($pack . '/archives-LEX/OPS-originaux-LEX/04-procedure/sommation-huissier.pdf', "%PDF-1.4 fake\n");
        file_put_contents($pack . '/archives-LEX/LEX-26-042/recommande-AR.pdf', "%PDF-1.4 fake\n");
        file_put_contents($pack . '/archives-LEX/LEX-26-042/bail-boutique-2025.pdf', "%PDF-1.4 fake\n");
        file_put_contents($pack . '/archives-LEX/LEX-26-042/bail tisserand - el agri.pdf', "%PDF-1.4 fake\n");
        file_put_contents($pack . '/archives-LEX/LEX-26-042/-DELEGATION MAIRE.doc', "fake doc\n");

        file_put_contents($pack . '/index.md', "## 1. Comprendre\n\n## 5. La sommation\n\n## 6. Ce que dit la mairie\n\n## 7. Solutions\n\n## 8. Propositions\n\n## 12. Approfondir\n");
        file_put_contents($pack . '/fiche-d-sommation-24-avril-2026.md', "## Fiche sommation\n");
        file_put_contents($pack . '/fiche-e-mail-maire-14-mai-2026.md', "## Fiche email\n#fiche-f-demandes-documents.md\n## Fiche demandes\n");
        file_put_contents($pack . '/fiche-h-demande-aot.md', "## Fiche AOT\n");
        file_put_contents($pack . '/chronologie.md', "## Chronologie\n");
        file_put_contents($pack . '/questions-ouvertes.md', "## Questions ouvertes\n");

        \Illuminate\Support\Facades\Artisan::call('app:seraphotheque-ingestion', [
            '--user-id' => $admin->id,
            '--pack-path' => $pack,
        ]);

        return Subject::where('slug', 'seraphotheque-situation-2026')->firstOrFail();
    }

    public function test_guest_cannot_see_draft_seraphotheque(): void
    {
        $this->ingestSeraphotheque();

        $this->get(route('subjects.show', 'seraphotheque-situation-2026'))
            ->assertNotFound();

        $this->get(route('subjects.preview', ['seraphotheque-situation-2026', 'public']))
            ->assertRedirect('/');

        $this->get(route('subjects.preview', ['seraphotheque-situation-2026', 'citizen']))
            ->assertRedirect('/');
    }

    public function test_citizen_cannot_see_draft_seraphotheque(): void
    {
        $this->ingestSeraphotheque();
        $citizen = User::factory()->create(['role' => 'citoyen', 'email_verified_at' => now(), 'requires_setup' => false]);

        $this->actingAs($citizen)
            ->get(route('subjects.show', 'seraphotheque-situation-2026'))
            ->assertNotFound();

        $this->actingAs($citizen)
            ->get(route('subjects.preview', ['seraphotheque-situation-2026', 'public']))
            ->assertForbidden();
    }

    public function test_admin_sees_working_body_and_all_working_documents(): void
    {
        $this->ingestSeraphotheque();
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)
            ->get(route('subjects.show', 'seraphotheque-situation-2026'));

        $response->assertOk();
        $response->assertSee('La Séraphothèque');
        $response->assertSee('Sommation du 24 avril 2026');
        $response->assertSee('Convention occupation 2025');
    }

    public function test_admin_can_preview_public_and_citizen_without_leak(): void
    {
        $this->ingestSeraphotheque();
        $admin = User::where('role', 'admin')->first();

        $this->actingAs($admin)
            ->get(route('subjects.preview', ['seraphotheque-situation-2026', 'public']))
            ->assertOk()
            ->assertSee('La Séraphothèque')
            ->assertSee('Sommation du 24 avril 2026');

        $this->actingAs($admin)
            ->get(route('subjects.preview', ['seraphotheque-situation-2026', 'citizen']))
            ->assertOk()
            ->assertSee('La Séraphothèque');
    }

    public function test_published_public_body_is_visible_to_guest(): void
    {
        $subject = $this->ingestSeraphotheque();
        $subject->update(['public_status' => 'published', 'public_published_at' => now()]);

        $this->get(route('subjects.show', 'seraphotheque-situation-2026'))
            ->assertOk()
            ->assertSee('La Séraphothèque')
            ->assertSee('Sommation du 24 avril 2026')
            ->assertSee('Comprendre la situation');

        $this->get(route('subjects.preview', ['seraphotheque-situation-2026', 'public']))
            ->assertRedirect('/');
    }
}
