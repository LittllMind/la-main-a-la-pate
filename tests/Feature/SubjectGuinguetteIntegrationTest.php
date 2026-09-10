<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\SubjectVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectGuinguetteIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const INSTRUCTION_SHA = '68cb1b4a1dd96b683c7e1756d368b62e484ce705644e8af9850983ff5510a0e8';
    private const CITIZEN_SHA = '5ffa64889b8214c5cd5bbb97e5eeb867187bbda93f1138e83949f5914ebc720e';

    private function ingestGuinguette(): Subject
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
            'requires_setup' => false,
        ]);

        $cat = Category::factory()->create(['id' => 6, 'name' => 'Patrimoine & Mémoire', 'slug' => 'patrimoine-memoire']);
        $sub = SubCategory::factory()->create(['id' => 21, 'category_id' => 6, 'name' => 'Foncier & cessions', 'slug' => 'foncier-cessions']);

        $base = "/home/aur-lien/Obsidian-Vault/LMLaP/Journal/2026-09-10-guinguette-staging";
        $body = file_get_contents($base.'/LMALP-GUINGUETTE-INSTRUCTION-V0.2.md');
        $citizen = file_get_contents($base.'/LMALP-GUINGUETTE-CITOYEN-V0.1.md');

        $subject = Subject::create([
            'user_id' => $admin->id,
            'theme' => 'Patrimoine & Mémoire',
            'category_id' => $cat->id,
            'sub_category_id' => $sub->id,
            'title' => 'La Guinguette / La Baraquita',
            'slug' => 'guinguette-urbanisme',
            'body' => $body,
            'citizen_body' => $citizen,
            'public_body' => null,
            'status' => 'draft',
            'citizen_status' => 'draft',
            'public_status' => 'draft',
            'public_is_listed' => false,
        ]);

        SubjectVersion::create([
            'subject_id' => $subject->id,
            'user_id' => $admin->id,
            'body' => $body,
            'citizen_body' => $citizen,
            'public_body' => null,
            'change_summary' => 'Ingestion initiale INSTRUCTION + CITOYEN',
        ]);

        return $subject->fresh();
    }

    // ============================================================
    // 1. BODIES — intégrité db
    // ============================================================

    public function test_body_is_instruction(): void
    {
        $subject = $this->ingestGuinguette();
        $this->assertEquals(self::INSTRUCTION_SHA, hash('sha256', $subject->body));
    }

    public function test_citizen_body_is_citizen(): void
    {
        $subject = $this->ingestGuinguette();
        $this->assertEquals(self::CITIZEN_SHA, hash('sha256', $subject->citizen_body));
    }

    public function test_public_body_is_null(): void
    {
        $subject = $this->ingestGuinguette();
        $this->assertNull($subject->public_body);
    }

    public function test_statuses_are_draft(): void
    {
        $subject = $this->ingestGuinguette();
        $this->assertEquals('draft', $subject->status);
        $this->assertEquals('draft', $subject->citizen_status);
        $this->assertEquals('draft', $subject->public_status);
    }

    // ============================================================
    // 2. VERSIONING
    // ============================================================

    public function test_versioning_creates_record(): void
    {
        $subject = $this->ingestGuinguette();
        $versions = $subject->versions;
        $this->assertCount(1, $versions);

        $v = $versions->first();
        $this->assertEquals(self::INSTRUCTION_SHA, hash('sha256', $v->body));
        $this->assertEquals(self::CITIZEN_SHA, hash('sha256', $v->citizen_body));
        $this->assertNull($v->public_body);
        $this->assertStringContainsString('Ingestion', $v->change_summary);
    }

    // ============================================================
    // 3. ACL INSTRUCTION (admin / working)
    // ============================================================

    public function test_admin_sees_working_body(): void
    {
        $subject = $this->ingestGuinguette();
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)
            ->get(route('subjects.show', 'guinguette-urbanisme'));

        $response->assertOk();
        // La couche de présentation supprime les provenances techniques.
        $response->assertDontSee('source_unique');
        $response->assertDontSee('thread `1a07ac892386db3a`');
        $response->assertDontSee('PDF local, canonisé dans CIVITAS');
        // Les références documentaires utiles restent visibles.
        $response->assertSee('Jugement TA n°2202355');
        $response->assertSee('CR-CM 2021-10-12');
    }

    public function test_admin_can_preview_citizen(): void
    {
        $subject = $this->ingestGuinguette();
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)
            ->get(route('subjects.preview', ['guinguette-urbanisme', 'citizen']));

        $response->assertOk();
    }

    public function test_admin_can_edit_all_fields(): void
    {
        $subject = $this->ingestGuinguette();
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)
            ->get(route('subjects.edit', 'guinguette-urbanisme'));

        $response->assertOk();
    }

    // ============================================================
    // 4. ACL CITOYEN — draft = 404
    // ============================================================

    public function test_citizen_on_draft_gets_404(): void
    {
        $subject = $this->ingestGuinguette();
        $citizen = User::factory()->create([
            'role' => 'citoyen',
            'email_verified_at' => now(),
            'requires_setup' => false,
        ]);

        $this->actingAs($citizen)
            ->get(route('subjects.show', 'guinguette-urbanisme'))
            ->assertNotFound();
    }

    // ============================================================
    // 5. GUEST / PUBLIC — sans published = 404, pas de fuite
    // ============================================================

    public function test_guest_gets_404_no_public(): void
    {
        $subject = $this->ingestGuinguette();

        $this->get(route('subjects.show', 'guinguette-urbanisme'))
            ->assertNotFound();
    }

    public function test_guest_cannot_preview_citizen(): void
    {
        $subject = $this->ingestGuinguette();

        $this->get(route('subjects.preview', ['guinguette-urbanisme', 'citizen']))
            ->assertRedirect('/');
    }

    public function test_guest_cannot_preview_public(): void
    {
        $subject = $this->ingestGuinguette();

        $this->get(route('subjects.preview', ['guinguette-urbanisme', 'public']))
            ->assertRedirect('/');
    }

    // ============================================================
    // 6. ABSENCE DE FALLBACK CITOYEN — INSTRUCTION
    // ============================================================

    public function test_no_fallback_citizen_to_public_body(): void
    {
        $subject = $this->ingestGuinguette();
        $citizen = User::factory()->create([
            'role' => 'citoyen',
            'email_verified_at' => now(),
            'requires_setup' => false,
        ]);

        // Sans public_body publié, bodyFor doit retourner null (pas public_body, pas citizen_body draft)
        $resolved = $subject->bodyFor($citizen);
        $this->assertNull($resolved);
    }

    public function test_no_fallback_guest_to_citizen_body(): void
    {
        $subject = $this->ingestGuinguette();
        $resolved = $subject->bodyFor(null);
        $this->assertNull($resolved);
    }

    public function test_no_leak_of_working_body_to_guest(): void
    {
        $subject = $this->ingestGuinguette();
        $body = $subject->bodyFor(null);
        $this->assertNotEquals(self::INSTRUCTION_SHA, $body === null ? '' : hash('sha256', $body));
    }

    public function test_no_leak_of_citizen_body_to_guest(): void
    {
        $subject = $this->ingestGuinguette();
        $body = $subject->bodyFor(null);
        $this->assertNotEquals(self::CITIZEN_SHA, $body === null ? '' : hash('sha256', $body));
    }
}
