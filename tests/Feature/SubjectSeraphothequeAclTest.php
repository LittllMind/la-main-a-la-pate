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
        @mkdir($pack . '/01-SUJET', 0755, true);
        @mkdir($pack . '/02-CHRONOLOGIE', 0755, true);
        @mkdir($pack . '/03-DOCUMENTS/PUBLIC', 0755, true);
        @mkdir($pack . '/03-DOCUMENTS/CITIZEN', 0755, true);
        @mkdir($pack . '/04-FICHES', 0755, true);
        @mkdir($pack . '/05-QUESTIONS-OUVERTES', 0755, true);
        @mkdir($pack . '/06-SOURCES', 0755, true);
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

        $index = "---\ntags: [sujet-public, seraphotheque, v1, narration]\ndate: 2026-08-21\n---\n\n# La Séraphothèque — Comprendre la situation\n\n**Dernière mise à jour : août 2026**\n\nCette page permet de **comprendre la situation** entre La Séraphothèque et la commune du Rozier.\n\n---\n\n# 1. Comprendre en une minute\n\nLe local communal est occupé selon des conventions précaires.\n\n→ **Voir la chronologie**\n\n→ **Voir les documents**\n\n---\n\n# 2. Ce qui a changé dans la convention 2026\n\nLe projet 2026 conserve la durée et le loyer.\n\n→ **Comparer la convention 2025 et le projet 2026 article par article**\n\n---\n\n# 3. Pourquoi avons-nous refusé de signer ?\n\n## Position de La Séraphothèque\n\nNous avons demandé que la situation soit discutée.\n\n---\n\n# 4. Qui pouvait décider ?\n\nLe Conseil municipal a examiné un point délégation au maire.\n\n---\n\n# 5. La sommation du 24 avril 2026\n\nL'acte est daté du 24 avril 2026 et demande notamment de signer la convention.\n\n---\n\n# 6. Ce que dit la mairie sur l'avenir du local\n\nLe maire a évoqué par écrit la possibilité d'une reprise future du bâtiment.\n\n---\n\n# 7. Nos demandes de documents\n\n**Lorsqu'un document n'a pas été communiqué, cela ne signifie pas que nous affirmons qu'il n'existe pas.**\n\n---\n\n# 8. Les solutions que nous avons proposées\n\n### Régularisation des portants : demande d'AOT\n\nLe 16 juin 2026, une demande d'AOT a été déposée.\n\n---\n\n# 9. Chronologie condensée\n\n→ **Voir la chronologie complète**\n\n| Date | Événement |\n| 2026-04-24 | Sommation |\n";
        file_put_contents($pack . '/01-SUJET/index.md', $index);
        file_put_contents($pack . '/02-CHRONOLOGIE/chronologie.md', "# Chronologie documentaire — PUBLIC V1\n\n## 2026\n\n| Date | Événement |\n| 2026-04-24 | Sommation |\n");
        file_put_contents($pack . '/04-FICHES/fiche-email-01-07-2026-trottoir-prive.md', "# Fiche documentaire — Email du 1er juillet 2026\n\n## Ce qu'elle établit\n\nLes portants ont été déplacés.\n");
        file_put_contents($pack . '/05-QUESTIONS-OUVERTES/questions-ouvertes.md', "# Questions ouvertes — PUBLIC V1\n\n## AOT\n\n### Quelle suite officielle a été donnée ?\n\nAucune décision écrite n'a été retrouvée.\n");
        file_put_contents($pack . '/06-SOURCES/index.md', "# Sources — PUBLIC V1\n\n## Sources primaires (A)\n\n| Source | Identifiant |\n| Convocation CM 27 avril | SERAPH-DOC-0445 |\n");

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
        $response->assertSee('La sommation du 24 avril 2026');
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
            ->assertSee('La sommation du 24 avril 2026');

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
            ->assertSee('La sommation du 24 avril 2026')
            ->assertSee('Comprendre la situation');

        $this->get(route('subjects.preview', ['seraphotheque-situation-2026', 'public']))
            ->assertRedirect('/');
    }
}
