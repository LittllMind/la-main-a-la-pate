<?php

namespace Tests\Feature;

use App\Console\Commands\Ingestion\MonumentAlbertCurvelierIngestion;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\CategorySubcategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Non-régression ingestion Monument Albert Curvelier :
 * - collaborateurs exacts Aurélien + Patrice Denjean
 * - échec si introuvable ou ambigu
 * - idempotence sans duplication
 */
class MonumentAlbertCurvelierIngestionTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CategorySubcategorySeeder::class);
        $this->author = User::factory()->create(['name' => 'Hermès', 'role' => 'admin']);
        $this->createCollaborators();
    }

    public function test_ingestion_attaches_exact_collaborators(): void
    {
        $this->artisan('app:monument-albert-curvelier-ingestion', [
            '--user-id' => $this->author->id,
            '--force' => true,
        ])->assertSuccessful();

        $subject = Subject::where('slug', 'monument-aux-morts-albert-curvelier')->first();
        $this->assertNotNull($subject);
        $this->assertSame(Subject::ACCESS_LEVEL_COLLABORATORS_ONLY, $subject->access_level);
        $this->assertNull($subject->public_body);
        $this->assertFalse($subject->public_is_listed);

        $collaboratorIds = $subject->collaborators->pluck('id')->sort()->values()->all();
        $this->assertSame([$this->aurelien()->id, $this->patrice()->id], $collaboratorIds);
    }

    public function test_ingestion_is_idempotent_and_does_not_duplicate_collaborators(): void
    {
        $this->artisan('app:monument-albert-curvelier-ingestion', [
            '--user-id' => $this->author->id,
            '--force' => true,
        ])->assertSuccessful();

        $this->artisan('app:monument-albert-curvelier-ingestion', [
            '--user-id' => $this->author->id,
            '--force' => true,
        ])->assertSuccessful();

        $subject = Subject::where('slug', 'monument-aux-morts-albert-curvelier')->first();
        $this->assertCount(2, $subject->collaborators);
        $this->assertCount(1, $subject->versions);
    }

    public function test_ingestion_fails_when_patrice_is_missing(): void
    {
        User::where('name', 'Patrice Denjean')->delete();

        $this->artisan('app:monument-albert-curvelier-ingestion', [
            '--user-id' => $this->author->id,
            '--force' => true,
        ])->assertFailed();

        $this->assertNull(Subject::where('slug', 'monument-aux-morts-albert-curvelier')->first());
    }

    public function test_ingestion_fails_when_patrice_lookup_is_ambiguous(): void
    {
        User::factory()->create([
            'name' => 'Patrice Denjean',
            'email' => 'patrice.dup@example.org',
            'username' => 'patrice_dup',
        ]);

        $this->artisan('app:monument-albert-curvelier-ingestion', [
            '--user-id' => $this->author->id,
            '--force' => true,
        ])->assertFailed();
    }

    public function test_ingestion_fails_when_aurelien_is_missing(): void
    {
        User::where('name', 'Aurélien')->delete();

        $this->artisan('app:monument-albert-curvelier-ingestion', [
            '--user-id' => $this->author->id,
            '--force' => true,
        ])->assertFailed();
    }

    public function test_ingestion_fails_when_aurelien_lookup_is_ambiguous(): void
    {
        User::factory()->create([
            'name' => 'Aurélien',
            'email' => 'aurelien.dup@example.org',
            'username' => 'aurelien_dup',
        ]);

        $this->artisan('app:monument-albert-curvelier-ingestion', [
            '--user-id' => $this->author->id,
            '--force' => true,
        ])->assertFailed();
    }

    public function test_ingestion_does_not_alter_other_subjects(): void
    {
        $otherSubject = Subject::factory()->create([
            'title' => 'Séraphothèque',
            'slug' => 'seraphotheque',
            'access_level' => Subject::ACCESS_LEVEL_STANDARD,
        ]);

        $this->artisan('app:monument-albert-curvelier-ingestion', [
            '--user-id' => $this->author->id,
            '--force' => true,
        ])->assertSuccessful();

        $otherSubject->refresh();
        $this->assertSame(Subject::ACCESS_LEVEL_STANDARD, $otherSubject->access_level);
    }

    private function createCollaborators(): void
    {
        User::factory()->create([
            'name' => 'Aurélien',
            'email' => 'aurelien.tisserand18@example.com',
            'username' => 'aurelien',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Patrice Denjean',
            'email' => 'patrice.denjean@example.com',
            'username' => 'patrice_denjean',
            'role' => 'moderator',
        ]);
    }

    private function aurelien(): User
    {
        return User::where('name', 'Aurélien')->first();
    }

    private function patrice(): User
    {
        return User::where('name', 'Patrice Denjean')->first();
    }
}
