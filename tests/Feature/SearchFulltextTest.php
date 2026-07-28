<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\User;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SearchFulltextTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // PHPUnit utilise DB_DATABASE=la_main_a_la_pate_test (MySQL).
        // Les index FULLTEXT MySQL sont necessaires.
    }

    /** @test */
    public function fulltext_migration_exists_and_runs(): void
    {
        $this->artisan('migrate:fresh')->assertSuccessful();

        $indexes = DB::select("SHOW INDEX FROM subjects WHERE Index_type = 'FULLTEXT'");
        $this->assertCount(2, $indexes, 'FULLTEXT sur subjects.title+body manquant');

        $indexes = DB::select("SHOW INDEX FROM subject_documents WHERE Index_type = 'FULLTEXT'");
        $this->assertCount(2, $indexes, 'FULLTEXT sur subject_documents.filename+description manquant');
    }

    /** @test */
    public function it_searches_subjects_by_title(): void
    {
        $this->artisan('migrate:fresh')->assertSuccessful();

        Subject::factory()->create(['title' => 'Budget communal 2026', 'body' => 'texte']);
        Subject::factory()->create(['title' => 'Conseil municipal', 'body' => 'texte']);

        $results = Subject::whereFullText(['title', 'body'], 'budget 2026')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('Budget communal 2026', $results->first()->title);
    }

    /** @test */
    public function it_searches_documents_by_filename(): void
    {
        $this->artisan('migrate:fresh')->assertSuccessful();

        $subject = Subject::factory()->create();
        SubjectDocument::factory()->create([
            'subject_id' => $subject->id,
            'filename' => 'PV reunion janvier.pdf',
            'description' => 'texte',
        ]);
        SubjectDocument::factory()->create([
            'subject_id' => $subject->id,
            'filename' => 'carte interactive.png',
            'description' => 'texte',
        ]);

        $results = SubjectDocument::whereFullText(['filename', 'description'], 'reunion janvier')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('PV reunion janvier.pdf', $results->first()->filename);
    }

    /** @test */
    public function search_endpoint_returns_json_results(): void
    {
        $this->artisan('migrate:fresh')->assertSuccessful();

        Subject::factory()->create(['title' => 'Fuite eau mairie', 'body' => 'texte']);
        Subject::factory()->create(['title' => 'Refonte site web', 'body' => 'projet']);

        $response = $this->getJson('/recherche?q=fuite');
        $response->assertOk();
        $response->assertJsonCount(1, 'subjects');
        $this->assertEquals('Fuite eau mairie', $response->json('subjects.0.title'));
    }

    /** @test */
    public function search_requires_query_parameter(): void
    {
        $response = $this->getJson('/recherche');
        $response->assertStatus(422);
    }
}
