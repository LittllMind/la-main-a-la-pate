<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\User;
use App\Models\VisibilityLevel;
use App\Services\DocumentStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests de non-fuite du contenu sensible selon le niveau de visibilité.
 *
 * Les marqueurs secrets sont choisis suffisamment uniques pour qu'aucune
 * collision ne vienne fausser les assertions `assertDontSee`.
 */
class SubjectVisibilityLeakTest extends TestCase
{
    use RefreshDatabase;

    private const WORKING_SECRET = 'WORKING_SECRET_8F93X';
    private const CITIZEN_SECRET = 'CITIZEN_SECRET_72ABC';
    private const PUBLIC_VISIBLE = 'PUBLIC_VISIBLE_39ZZ';

    private Subject $subject;
    private Category $category;
    private SubCategory $subCategory;
    private SubjectDocument $publicDoc;
    private SubjectDocument $citizenDoc;
    private SubjectDocument $workingDoc;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('documents');

        $owner = User::factory()->create(['role' => 'citoyen']);
        $this->category = Category::factory()->create();
        $this->subCategory = SubCategory::factory()->create(['category_id' => $this->category->id]);

        $this->subject = Subject::factory()->for($owner)->create([
            'category_id' => $this->category->id,
            'sub_category_id' => $this->subCategory->id,
            'title' => 'Sujet test visibilité',
            'body' => "# Travail\n\n" . self::WORKING_SECRET,
            'citizen_body' => "# Citoyen\n\n" . self::CITIZEN_SECRET,
            'public_body' => "# Public\n\n" . self::PUBLIC_VISIBLE,
            'status' => 'draft',
            'citizen_status' => 'published',
            'public_status' => 'published',
        ]);

        $service = app(DocumentStorageService::class);

        $this->publicDoc = $this->createDocument('public-doc.pdf', VisibilityLevel::Public, $service);
        $this->citizenDoc = $this->createDocument('citizen-doc.pdf', VisibilityLevel::Citizen, $service);
        $this->workingDoc = $this->createDocument('working-doc.pdf', VisibilityLevel::Working, $service);
    }

    private function createDocument(string $filename, VisibilityLevel $level, DocumentStorageService $service): SubjectDocument
    {
        $pdf = UploadedFile::fake()->createWithContent($filename, str_repeat('x', 200));
        $path = $service->storeEncrypted($this->subject->id, $pdf->getRealPath(), $filename);

        return SubjectDocument::create([
            'subject_id' => $this->subject->id,
            'filename' => $filename,
            'stored_filename' => basename($path),
            'path' => $path,
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => 200,
            'title' => $level->label() . ' doc',
            'category' => 'source',
            'visibility' => $level->value,
        ]);
    }

    public function test_guest_only_sees_public_body_and_public_document(): void
    {
        $guest = $this;

        $guest->get(route('subjects.index'))
            ->assertRedirect('/')
            ->assertDontSee(self::CITIZEN_SECRET)
            ->assertDontSee(self::WORKING_SECRET);

        $guest->get(route('subjects.show', $this->subject->slug))
            ->assertOk()
            ->assertSee(self::PUBLIC_VISIBLE)
            ->assertDontSee(self::CITIZEN_SECRET)
            ->assertDontSee(self::WORKING_SECRET)
            ->assertSee($this->publicDoc->title)
            ->assertDontSee($this->citizenDoc->title)
            ->assertDontSee($this->workingDoc->title);

        $guest->getJson('/recherche?q=' . urlencode(self::WORKING_SECRET))
            ->assertOk()
            ->assertJsonCount(0, 'subjects')
            ->assertJsonCount(0, 'documents');

        $guest->getJson('/recherche?q=' . urlencode(self::CITIZEN_SECRET))
            ->assertOk()
            ->assertJsonCount(0, 'subjects')
            ->assertJsonCount(0, 'documents');

        $guest->get(route('site.map'))
            ->assertOk()
            ->assertDontSee(self::WORKING_SECRET)
            ->assertDontSee(self::CITIZEN_SECRET);

        $guest->get(route('subjects.pdf.show', $this->subject->slug))
            ->assertRedirect('/');

        $guest->get(route('subjects.documents.download', [$this->subject->slug, $this->publicDoc->id]))
            ->assertOk();
    }

    public function test_other_citizen_sees_citizen_and_public_but_not_working(): void
    {
        $other = User::factory()->create(['role' => 'citoyen']);

        // index : le résumé affiche le contenu citizen (fallback public s'il n'y en a pas)
        $this->actingAs($other)->get(route('subjects.index'))
            ->assertOk()
            ->assertSee(self::CITIZEN_SECRET)
            ->assertDontSee(self::WORKING_SECRET);

        // show : body citizen, documents citizen + public
        $this->actingAs($other)->get(route('subjects.show', $this->subject->slug))
            ->assertOk()
            ->assertSee(self::CITIZEN_SECRET)
            ->assertDontSee(self::WORKING_SECRET)
            ->assertSee($this->publicDoc->title)
            ->assertSee($this->citizenDoc->title)
            ->assertDontSee($this->workingDoc->title);

        $this->actingAs($other)
            ->get(route('subjects.documents.download', [$this->subject->slug, $this->publicDoc->id]))
            ->assertOk();

        $this->actingAs($other)
            ->get(route('subjects.documents.download', [$this->subject->slug, $this->citizenDoc->id]))
            ->assertOk();

        $this->actingAs($other)
            ->get(route('subjects.documents.download', [$this->subject->slug, $this->workingDoc->id]))
            ->assertNotFound();
    }

    public function test_admin_sees_all_three_levels(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('subjects.show', $this->subject->slug))
            ->assertOk()
            ->assertSee(self::WORKING_SECRET)
            ->assertDontSee(self::CITIZEN_SECRET)
            ->assertDontSee(self::PUBLIC_VISIBLE)
            ->assertSee($this->workingDoc->title);

        $this->actingAs($admin)
            ->get(route('subjects.documents.download', [$this->subject->slug, $this->workingDoc->id]))
            ->assertOk();

        $this->actingAs($admin)
            ->get('/documents/arbre-data')
            ->assertOk()
            ->assertJsonPath('0.subCategories.0.subjects.0.title', $this->subject->title);
    }
}
