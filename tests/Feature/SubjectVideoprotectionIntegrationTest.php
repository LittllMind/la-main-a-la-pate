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

class SubjectVideoprotectionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public const INSTRUCTION_SHA = 'ba4d881902e34ba879add6f79305a871d0a53b273da463207d19d0b2c7940a42';
    public const CITIZEN_SHA     = '95a8d1c35ff6160930fd59595fe2eabb4348a8de339b662b000ea692d34ad793';

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

    public function test_subcategory_is_created_under_category_5(): void
    {
        $this->seedEnvironment();
        $this->artisan('db:seed', ['--class' => VideoprotectionSubjectSeeder::class]);

        $cat = Category::where('name', 'Conseil municipal & Gouvernance')->firstOrFail();
        $sub = SubCategory::where('name', 'Sécurité & tranquillité publique')->firstOrFail();

        $this->assertEquals($cat->id, $sub->category_id);
    }

    public function test_subcategory_is_unique_after_second_run(): void
    {
        $this->seedEnvironment();
        $this->artisan('db:seed', ['--class' => VideoprotectionSubjectSeeder::class]);
        $countAfterFirst = SubCategory::where('name', 'Sécurité & tranquillité publique')->count();

        $this->artisan('db:seed', ['--class' => VideoprotectionSubjectSeeder::class]);
        $countAfterSecond = SubCategory::where('name', 'Sécurité & tranquillité publique')->count();

        $this->assertEquals(1, $countAfterFirst);
        $this->assertEquals(1, $countAfterSecond, 'La sous-categorie ne doit pas dupliquer.');
    }

    public function test_subject_integrity(): void
    {
        $this->seedEnvironment();
        $this->artisan('db:seed', ['--class' => VideoprotectionSubjectSeeder::class]);

        $subject = Subject::where('slug', 'videoprotection')->firstOrFail();

        $this->assertEquals('videoprotection', $subject->slug);
        $this->assertNotNull($subject->category_id);
        $this->assertNotNull($subject->sub_category_id);
        $this->assertEquals(self::INSTRUCTION_SHA, hash('sha256', $subject->body));
        $this->assertNotNull($subject->citizen_body);
        $this->assertEquals(self::CITIZEN_SHA, hash('sha256', $subject->citizen_body));
        $this->assertNull($subject->public_body);
        $this->assertFalse($subject->public_is_listed);
        $this->assertEquals('draft', $subject->status);
        $this->assertEquals('draft', $subject->citizen_status);
        $this->assertEquals('draft', $subject->public_status);
    }

    public function test_subject_version_snapshot_created(): void
    {
        $this->seedEnvironment();
        $this->artisan('db:seed', ['--class' => VideoprotectionSubjectSeeder::class]);

        $subject = Subject::where('slug', 'videoprotection')->firstOrFail();
        $versions = $subject->versions;

        $this->assertCount(1, $versions);

        $v = $versions->first();
        $this->assertEquals(self::INSTRUCTION_SHA, hash('sha256', $v->body));
        $this->assertNotNull($v->citizen_body);
        $this->assertEquals(self::CITIZEN_SHA, hash('sha256', $v->citizen_body));
        $this->assertNull($v->public_body);
        $this->assertStringContainsString('Ingestion', $v->change_summary);
    }

    public function test_second_run_is_noop(): void
    {
        $this->seedEnvironment();
        $this->artisan('db:seed', ['--class' => VideoprotectionSubjectSeeder::class]);

        $subject = Subject::where('slug', 'videoprotection')->firstOrFail();
        $firstId = $subject->id;
        $versionCountFirst = SubjectVersion::where('subject_id', $firstId)->count();

        $this->artisan('db:seed', ['--class' => VideoprotectionSubjectSeeder::class]);

        $subjectAfter = Subject::where('slug', 'videoprotection')->firstOrFail();
        $this->assertEquals($firstId, $subjectAfter->id, 'Le sujet ne doit pas changer ID.');

        $versionCountSecond = SubjectVersion::where('subject_id', $firstId)->count();
        $this->assertEquals($versionCountFirst, $versionCountSecond, 'Aucune version ne doit etre ajoutee.');
    }

    public function test_sha_guard_blocks_corrupted_body(): void
    {
        $this->seedEnvironment();

        $path = base_path('database/seeders/data/videoprotection-instruction-v0.2.md');
        $original = file_get_contents($path);
        file_put_contents($path, $original . "\n\nCORRUPTION");

        try {
            $this->artisan('db:seed', ['--class' => VideoprotectionSubjectSeeder::class]);
            $this->fail('Une RuntimeException aurait du etre levee.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('SHA INSTRUCTION V0.2 non conforme', $e->getMessage());
        } finally {
            file_put_contents($path, $original);
        }
    }
}
