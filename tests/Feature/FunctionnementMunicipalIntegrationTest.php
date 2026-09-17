<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\SubjectVersion;
use App\Models\User;
use Database\Seeders\CategorySubcategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FunctionnementMunicipalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const SLUG = 'fonctionnement-municipal';
    private const TITLE = 'Fonctionnement municipal et information des conseillers';
    private const INSTRUCTION_FILE = 'LMALP-FONCTIONNEMENT-MUNICIPAL-INSTRUCTION-V0.2.md';
    private const CITIZEN_FILE = 'LMALP-FONCTIONNEMENT-MUNICIPAL-CITOYEN-V0.2.md';

    private string $packPath;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('documents');
        $this->seed(CategorySubcategorySeeder::class);

        $this->packPath = storage_path('testing/functionnement-municipal-pack');
        @mkdir($this->packPath, 0755, true);

        // Corps canoniques minimalistes mais représentatifs
        file_put_contents($this->packPath . '/' . self::INSTRUCTION_FILE, "# Fonctionnement municipal au Rozier\n\n## Question centrale\n\nInstruction.\n");
        file_put_contents($this->packPath . '/' . self::CITIZEN_FILE, "# Fonctionnement municipal au Rozier\n\n## La question en bref\n\nCitizen.\n");
    }

    private function seedEnvironment(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Sujets à préserver pour les tests de non-régression
        $preserved = [
            'seraphotheque-situation-2026',
            'guinguette-urbanisme',
            'videoprotection',
            'monument-aux-morts-rozier-nom-fantome',
            'comptes-rendus-du-conseil-municipal-du-rozier-archive-civitas',
        ];
        $existing = collect();
        foreach ($preserved as $slug) {
            $existing->push(Subject::factory()->create(['slug' => $slug]));
        }

        return ['admin' => $admin, 'existing' => $existing];
    }

    /** @test */
    public function it_creates_subject_with_correct_taxonomy_and_status(): void
    {
        ['admin' => $admin] = $this->seedEnvironment();

        $this->artisan('app:integrate-functionnement-municipal', [
            '--pack-path' => $this->packPath,
            '--user-id' => $admin->id,
        ])->assertSuccessful();

        $subject = Subject::where('slug', self::SLUG)->firstOrFail();

        $this->assertSame(self::TITLE, $subject->title);
        $this->assertSame('draft', $subject->status);
        $this->assertSame('draft', $subject->citizen_status);
        $this->assertSame('draft', $subject->public_status);
        $this->assertFalse($subject->public_is_listed);
        $this->assertSame(Subject::ACCESS_LEVEL_STANDARD, $subject->access_level);
        $this->assertSame('conseil-municipal-gouvernance', $subject->category->slug);
        $this->assertSame('conseils-municipaux', $subject->subCategory->slug);
        $this->assertNull($subject->public_body);
    }

    /** @test */
    public function admin_can_view_instruction_body_and_edit_three_fields(): void
    {
        ['admin' => $admin] = $this->seedEnvironment();

        $this->artisan('app:integrate-functionnement-municipal', [
            '--pack-path' => $this->packPath,
            '--user-id' => $admin->id,
        ])->assertSuccessful();

        $this->actingAs($admin)
            ->get('/sujets/fonctionnement-municipal')
            ->assertOk()
            ->assertSee('Fonctionnement municipal au Rozier')
            ->assertSee('Question centrale');

        $this->actingAs($admin)
            ->get('/sujets/fonctionnement-municipal/modifier')
            ->assertOk()
            ->assertSee('body')
            ->assertSee('citizen_body');
    }

    /** @test */
    public function admin_preview_citizen_shows_citizen_body(): void
    {
        ['admin' => $admin] = $this->seedEnvironment();

        $this->artisan('app:integrate-functionnement-municipal', [
            '--pack-path' => $this->packPath,
            '--user-id' => $admin->id,
        ])->assertSuccessful();

        $this->actingAs($admin)
            ->get('/sujets/fonctionnement-municipal/apercu/citizen')
            ->assertOk()
            ->assertSee('Fonctionnement municipal au Rozier')
            ->assertSee('La question en bref');
    }

    /** @test */
    public function guest_gets_404_and_no_body_leak(): void
    {
        ['admin' => $admin] = $this->seedEnvironment();

        $this->artisan('app:integrate-functionnement-municipal', [
            '--pack-path' => $this->packPath,
            '--user-id' => $admin->id,
        ])->assertSuccessful();

        $response = $this->get('/sujets/fonctionnement-municipal');
        $response->assertStatus(404);
        $this->assertStringNotContainsString('Question centrale', $response->getContent());
        $this->assertStringNotContainsString('La question en bref', $response->getContent());
    }

    /** @test */
    public function it_stores_bodies_without_alteration_and_creates_initial_version(): void
    {
        ['admin' => $admin] = $this->seedEnvironment();

        $expectedInstructionSha = hash_file('sha256', $this->packPath . '/' . self::INSTRUCTION_FILE);
        $expectedCitizenSha = hash_file('sha256', $this->packPath . '/' . self::CITIZEN_FILE);

        $this->artisan('app:integrate-functionnement-municipal', [
            '--pack-path' => $this->packPath,
            '--user-id' => $admin->id,
        ])->assertSuccessful();

        $subject = Subject::where('slug', self::SLUG)->firstOrFail();

        $this->assertSame($expectedInstructionSha, hash('sha256', $subject->body));
        $this->assertSame($expectedCitizenSha, hash('sha256', $subject->citizen_body));
        $this->assertCount(1, $subject->versions);

        $version = $subject->versions->first();
        $this->assertSame($subject->body, $version->body);
        $this->assertSame($subject->citizen_body, $version->citizen_body);
        $this->assertNull($version->public_body);
        $this->assertSame('Initial LMALP integration after JURIDIQUE and COMMUNICATION review.', $version->change_summary);
    }

    /** @test */
    public function it_is_idempotent_and_does_not_duplicate_subject_or_version(): void
    {
        ['admin' => $admin] = $this->seedEnvironment();

        $this->artisan('app:integrate-functionnement-municipal', [
            '--pack-path' => $this->packPath,
            '--user-id' => $admin->id,
        ])->assertSuccessful();

        $subject = Subject::where('slug', self::SLUG)->firstOrFail();
        $versionCount = $subject->versions()->count();

        $this->artisan('app:integrate-functionnement-municipal', [
            '--pack-path' => $this->packPath,
            '--user-id' => $admin->id,
        ])->assertSuccessful();

        $subject->refresh();
        $this->assertCount(1, Subject::where('slug', self::SLUG)->get());
        $this->assertSame($versionCount, $subject->versions()->count());
    }

    /** @test */
    public function it_does_not_alter_other_subjects(): void
    {
        ['admin' => $admin, 'existing' => $existing] = $this->seedEnvironment();

        $before = $existing->keyBy('slug')->map(fn (Subject $s) => [
            'body' => $s->body,
            'citizen_body' => $s->citizen_body,
            'public_body' => $s->public_body,
        ]);

        $this->artisan('app:integrate-functionnement-municipal', [
            '--pack-path' => $this->packPath,
            '--user-id' => $admin->id,
        ])->assertSuccessful();

        foreach ($before as $slug => $expected) {
            $s = Subject::where('slug', $slug)->firstOrFail();
            $this->assertSame($expected['body'], $s->body, "{$slug} body modifié.");
            $this->assertSame($expected['citizen_body'], $s->citizen_body, "{$slug} citizen_body modifié.");
            $this->assertSame($expected['public_body'], $s->public_body, "{$slug} public_body modifié.");
        }
    }

    /** @test */
    public function dry_run_does_not_create_records(): void
    {
        ['admin' => $admin] = $this->seedEnvironment();

        $this->artisan('app:integrate-functionnement-municipal', [
            '--pack-path' => $this->packPath,
            '--user-id' => $admin->id,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertNull(Subject::where('slug', self::SLUG)->first());
        $this->assertCount(0, SubjectVersion::all());
    }

    /** @test */
    public function missing_pack_returns_failure(): void
    {
        $this->artisan('app:integrate-functionnement-municipal', [
            '--pack-path' => '/nonexistent/path',
        ])->assertFailed();
    }

    /** @test */
    public function missing_canonical_files_returns_failure(): void
    {
        ['admin' => $admin] = $this->seedEnvironment();

        $emptyPack = storage_path('testing/empty-functionnement-pack');
        @mkdir($emptyPack, 0755, true);

        $this->artisan('app:integrate-functionnement-municipal', [
            '--pack-path' => $emptyPack,
            '--user-id' => $admin->id,
        ])->assertFailed();
    }
}
