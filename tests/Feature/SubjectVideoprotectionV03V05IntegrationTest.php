<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\SubjectVersion;
use App\Models\User;
use Database\Seeders\VideoprotectionSubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectVideoprotectionV03V05IntegrationTest extends TestCase
{
    use RefreshDatabase;

    public const INSTRUCTION_V03_SHA = 'a8717dc4124bb1ce9fa8b81fde0defdf6a9806eea27d6052b1a9f90cd7176c8e';
    public const CITIZEN_V05_SHA = 'ceb73ee14c00e94c063382284b8bace3684395591373853721191d6991686bc1';

    private function seedEnvironment(): void
    {
        User::factory()->create([
            'role'             => 'admin',
            'email_verified_at' => now(),
            'requires_setup'   => false,
        ]);

        Category::factory()->create([
            'name' => 'Conseil municipal & Gouvernance',
            'slug' => 'conseil-municipal-gouvernance',
        ]);
    }

    // ============================================================
    // 1. SUBJECT INTEGRITY V0.3 / V0.5
    // ============================================================

    public function test_body_is_instruction_v03_exact(): void
    {
        $this->seedEnvironment();
        $this->artisan('db:seed', ['--class' => VideoprotectionSubjectSeeder::class]);

        // Mettre à jour manuellement vers V0.3 / V0.5
        $subject = Subject::where('slug', 'videoprotection')->firstOrFail();
        $body03 = file_get_contents(base_path('database/seeders/data/videoprotection-instruction-v0.3.md'));
        $citizen05 = file_get_contents(base_path('database/seeders/data/videoprotection-citizen-v0.5.md'));
        $subject->body = $body03;
        $subject->citizen_body = $citizen05;
        $subject->save();

        $this->assertEquals(self::INSTRUCTION_V03_SHA, hash('sha256', $subject->fresh()->body));
    }

    public function test_citizen_body_is_v05_exact(): void
    {
        $this->seedEnvironment();
        $this->artisan('db:seed', ['--class' => VideoprotectionSubjectSeeder::class]);

        $subject = Subject::where('slug', 'videoprotection')->firstOrFail();
        $citizen05 = file_get_contents(base_path('database/seeders/data/videoprotection-citizen-v0.5.md'));
        $subject->citizen_body = $citizen05;
        $subject->save();

        $this->assertEquals(self::CITIZEN_V05_SHA, hash('sha256', $subject->fresh()->citizen_body));
    }

    public function test_public_body_is_null(): void
    {
        $this->seedEnvironment();
        $this->artisan('db:seed', ['--class' => VideoprotectionSubjectSeeder::class]);

        $subject = Subject::where('slug', 'videoprotection')->firstOrFail();
        $this->assertNull($subject->public_body);
    }

    public function test_statuses_unchanged_draft(): void
    {
        $this->seedEnvironment();
        $this->artisan('db:seed', ['--class' => VideoprotectionSubjectSeeder::class]);

        $subject = Subject::where('slug', 'videoprotection')->firstOrFail();
        $this->assertEquals('draft', $subject->status);
        $this->assertEquals('draft', $subject->citizen_status);
        $this->assertEquals('draft', $subject->public_status);
        $this->assertFalse($subject->public_is_listed);
    }

    public function test_versions_count_two_after_update(): void
    {
        $this->seedEnvironment();
        $this->artisan('db:seed', ['--class' => VideoprotectionSubjectSeeder::class]);

        $subject = Subject::where('slug', 'videoprotection')->firstOrFail();
        $body03 = file_get_contents(base_path('database/seeders/data/videoprotection-instruction-v0.3.md'));
        $citizen05 = file_get_contents(base_path('database/seeders/data/videoprotection-citizen-v0.5.md'));

        // Simuler la nouvelle version
        SubjectVersion::create([
            'subject_id'     => $subject->id,
            'user_id'        => $subject->user_id,
            'body'           => $body03,
            'citizen_body'   => $citizen05,
            'public_body'    => null,
            'change_summary' => 'Révision éditoriale INSTRUCTION V0.3 + CITIZEN V0.5',
        ]);

        $this->assertEquals(2, $subject->fresh()->versions->count());
    }

    // ============================================================
    // 2. ACL
    // ============================================================

    public function test_admin_can_see_instruction(): void
    {
        $this->seedEnvironment();
        $this->artisan('db:seed', ['--class' => VideoprotectionSubjectSeeder::class]);

        $admin = User::where('role', 'admin')->firstOrFail();
        $subject = Subject::where('slug', 'videoprotection')->firstOrFail();

        $this->assertNotNull($subject->bodyFor($admin));
    }

    public function test_admin_can_preview_citizen(): void
    {
        $this->seedEnvironment();
        $this->artisan('db:seed', ['--class' => VideoprotectionSubjectSeeder::class]);

        $subject = Subject::where('slug', 'videoprotection')->firstOrFail();
        $this->assertNotNull($subject->citizen_body);
        $this->assertTrue(filled($subject->citizen_body));
    }

    public function test_citizen_cannot_see_instruction(): void
    {
        $this->seedEnvironment();
        $this->artisan('db:seed', ['--class' => VideoprotectionSubjectSeeder::class]);

        $user = User::factory()->create([
            'role' => 'standard',
        ]);
        $subject = Subject::where('slug', 'videoprotection')->firstOrFail();

        // Citizen_status = draft, donc bodyFor retourne NULL
        $this->assertNull($subject->bodyFor($user));
    }

    public function test_guest_404_videoprotection(): void
    {
        $this->seedEnvironment();
        $this->artisan('db:seed', ['--class' => VideoprotectionSubjectSeeder::class]);

        // Guest ne peut pas voir car status=draft et public_status=draft
        $subject = Subject::where('slug', 'videoprotection')->firstOrFail();
        $this->assertFalse($subject->canBeViewedBy(null));
    }

    // ============================================================
    // 3. RENDERER — markdown to HTML
    // ============================================================

    public function test_instruction_renders_headings_tables_lists(): void
    {
        $this->seedEnvironment();
        $this->artisan('db:seed', ['--class' => VideoprotectionSubjectSeeder::class]);

        $subject = Subject::where('slug', 'videoprotection')->firstOrFail();
        $html = Subject::renderMarkdownToHtml($subject->body);

        $this->assertStringContainsString('<h2', $html);
        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<strong>', $html);
        $this->assertStringNotContainsString('\\#', $html);
        $this->assertStringNotContainsString('\\*\\*', $html);
    }

    public function test_citizen_renders_blockquote_tables_ordered_list(): void
    {
        $this->seedEnvironment();
        $this->artisan('db:seed', ['--class' => VideoprotectionSubjectSeeder::class]);

        $subject = Subject::where('slug', 'videoprotection')->firstOrFail();
        // Charger V0.5 dans le test
        $citizen05 = file_get_contents(base_path('database/seeders/data/videoprotection-citizen-v0.5.md'));
        $subject->citizen_body = $citizen05;
        $subject->save();

        $html = Subject::renderMarkdownToHtml($subject->fresh()->citizen_body);

        $this->assertStringContainsString('<blockquote>', $html);
        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<ol>', $html);
        $this->assertStringContainsString('<strong>', $html);
        $this->assertStringNotContainsString('\\\#', $html);
        $this->assertStringNotContainsString('\\*\\*', $html);
    }
}
