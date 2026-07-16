<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\SubjectImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubjectMarkdownImageWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_body_is_stored_as_markdown(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $subCategory = SubCategory::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($user)->post('/sujets', [
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'title' => 'Compte rendu',
            'body' => "# Titre\n\nUn paragraphe avec **gras**.",
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('subjects', [
            'title' => 'Compte rendu',
            'body' => "# Titre\n\nUn paragraphe avec **gras**.",
        ]);
    }

    public function test_subject_show_renders_markdown_as_html(): void
    {
        $user = User::factory()->create();
        $subject = Subject::factory()->create([
            'user_id' => $user->id,
            'status' => 'published',
            'body' => "# Titre\n\nDu texte et un [lien](https://example.com).",
        ]);

        $this->actingAs($user)->get("/sujets/{$subject->slug}")
            ->assertOk()
            ->assertSee('Titre')
            ->assertSee('Du texte et un')
            ->assertSee('https://example.com');
    }

    public function test_image_upload_attaches_to_subject(): void
    {
        Storage::fake('subject_images');

        $user = User::factory()->create();
        $subject = Subject::factory()->create(['user_id' => $user->id, 'status' => 'published']);

        $response = $this->actingAs($user)->post("/sujets/{$subject->slug}/images", [
            'image' => UploadedFile::fake()->image('plan.jpg', 800, 600),
            'alt' => 'Plan du chauffage',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('subject_images', [
            'subject_id' => $subject->id,
            'alt' => 'Plan du chauffage',
        ]);

        $storedFiles = Storage::disk('subject_images')->files("subjects/{$subject->id}");
        $this->assertCount(1, $storedFiles, 'No image stored for subject');
        $this->assertStringContainsString('plan', $storedFiles[0]);
    }

    public function test_gallery_lists_subject_images(): void
    {
        Storage::fake('subject_images');

        $user = User::factory()->create();
        $subject = Subject::factory()->create(['user_id' => $user->id, 'status' => 'published']);

        SubjectImage::create([
            'subject_id' => $subject->id,
            'filename' => 'image1.jpg',
            'path' => 'subjects/' . $subject->id . '/image1.jpg',
            'mime_type' => 'image/jpeg',
            'alt' => 'Image A',
        ]);

        $this->actingAs($user)->get("/sujets/{$subject->slug}/images")
            ->assertOk()
            ->assertSee('Image A')
            ->assertSee($subject->title);
    }

    public function test_zip_import_creates_subject_with_markdown_and_images(): void
    {
        Storage::fake('subject_images');

        $user = User::factory()->create(['role' => 'moderator']);
        $category = Category::factory()->create(['name' => 'Infrastructures']);
        $subCategory = SubCategory::factory()->create(['category_id' => $category->id, 'name' => 'Bâtiments communaux']);

        $zipPath = $this->fakeImportZip([
            'sujet.md' => "# PAC\n\n![plan](plan.jpg)\n",
            'plan.jpg' => UploadedFile::fake()->image('plan.jpg', 400, 300),
        ]);

        $response = $this->actingAs($user)->postJson('/sujets/importer', [
            'archive' => new UploadedFile($zipPath, 'import.zip', 'application/zip', null, true),
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'title' => 'PAC Import',
            'status' => 'published',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('subjects', [
            'title' => 'PAC Import',
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'theme' => 'Infrastructures',
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('subject_images', [
            'alt' => 'plan',
        ]);
    }

    public function test_html_legacy_subject_is_auto_converted_to_markdown(): void
    {
        $user = User::factory()->create();
        $subject = Subject::factory()->create([
            'user_id' => $user->id,
            'status' => 'published',
            'body' => '<h2>Ancien contenu</h2><p>Du texte.</p>',
        ]);

        $this->artisan('subjects:convert-html-to-markdown', ['subject' => $subject->id])
            ->assertSuccessful();

        $subject->refresh();
        $this->assertStringContainsString('Ancien contenu', $subject->body);
        $this->assertStringContainsString('Du texte.', $subject->body);
        $this->assertStringNotContainsString('<h2>', $subject->body);
        $this->assertStringNotContainsString('<p>', $subject->body);
    }

    private function fakeImportZip(array $files): string
    {
        $zipPath = sys_get_temp_dir() . '/import-' . uniqid() . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        foreach ($files as $name => $content) {
            if ($content instanceof UploadedFile) {
                $zip->addFile($content->path(), $name);
            } else {
                $zip->addFromString($name, $content);
            }
        }
        $zip->close();

        return $zipPath;
    }
}
