<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SubjectSeraphothequeAclTest extends TestCase
{
    use RefreshDatabase;

    protected function ingestSeraphotheque(): Subject
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now(), 'requires_setup' => false]);
        $category = Category::factory()->create(['id' => 10, 'name' => 'Vie du village', 'slug' => 'vie-du-village']);
        SubCategory::factory()->create(['id' => 14, 'category_id' => 10, 'name' => 'Séraphothèque', 'slug' => 'seraphotheque']);

        $pack = storage_path('framework/testing/seraphotheque-pack');
        @mkdir($pack . '/03-DOCUMENTS/PUBLIC', 0755, true);
        @mkdir($pack . '/03-DOCUMENTS/CITIZEN', 0755, true);
        @mkdir($pack . '/99-MANIFEST', 0755, true);

        file_put_contents($pack . '/03-DOCUMENTS/PUBLIC/doc-public.pdf', "%PDF-1.4 fake public\n");
        file_put_contents($pack . '/03-DOCUMENTS/CITIZEN/doc-citizen.pdf', "%PDF-1.4 fake citizen\n");

        $publicSha = hash_file('sha256', $pack . '/03-DOCUMENTS/PUBLIC/doc-public.pdf');
        $citizenSha = hash_file('sha256', $pack . '/03-DOCUMENTS/CITIZEN/doc-citizen.pdf');

        $csv = "public_id,doc_id,titre,date,type,audience,source,source_reference,original_sha256,asset_path,asset_sha256,expurgations,fiche,chronology_event,status\n"
            . 'PUB-01,DOC-ACL-PUBLIC,"Lettre ouverte Séraphothèque",2026-01-01,pdf,PUBLIC,test,seraphotheque-pack:DOC-ACL-PUBLIC,,'
            . '"03-DOCUMENTS/PUBLIC/doc-public.pdf",' . $publicSha . ",aucune,,,gelé\n"
            . 'CIT-01,DOC-ACL-CITIZEN,"Convention occupation 2025",2025-04-01,pdf,CITIZEN,test,seraphotheque-pack:DOC-ACL-CITIZEN,,'
            . '"03-DOCUMENTS/CITIZEN/doc-citizen.pdf",' . $citizenSha . "," . '"coordonnées, signatures",,,gelé' . "\n";

        file_put_contents($pack . '/99-MANIFEST/public-v1.csv', $csv);

        file_put_contents($pack . '/index.md', "## 1. Comprendre\n\n## 5. La sommation du 24 avril 2026\n\n## 6. Ce que dit la mairie\n\n## 7. Solutions\n\n## 8. Propositions\n\n## 12. Approfondir\n");
        file_put_contents($pack . '/fiche-d-sommation-24-avril-2026.md', "## Fiche sommation\n");
        file_put_contents($pack . '/fiche-e-mail-maire-14-mai-2026.md', "## Fiche email\n#fiche-f-demandes-documents.md\n## Fiche demandes\n");
        file_put_contents($pack . '/fiche-h-demande-aot.md', "## Fiche AOT\n");
        file_put_contents($pack . '/chronologie.md', "## Chronologie\n");
        file_put_contents($pack . '/questions-ouvertes.md', "## Questions ouvertes\n");

        $exitCode = Artisan::call('app:seraphotheque-ingestion', [
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
