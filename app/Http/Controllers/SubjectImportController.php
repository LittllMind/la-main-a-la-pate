<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\SubjectImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SubjectImportController extends Controller
{
    private const DISK = 'subject_images';

    public function create()
    {
        Gate::authorize('create', Subject::class);

        return view('subjects.import.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Subject::class);

        $validated = $request->validate([
            'archive' => 'required|file|mimes:zip|max:51200',
            'category_id' => 'required|integer|exists:categories,id',
            'sub_category_id' => 'required|integer|exists:sub_categories,id',
            'title' => 'required|string|max:255',
            'status' => 'required|in:draft,published',
        ]);

        $category = Category::findOrFail($validated['category_id']);
        $subCategory = SubCategory::findOrFail($validated['sub_category_id']);
        $theme = $category->name;
        $title = $validated['title'];
        $slug = $this->uniqueSlug($title);

        $archive = $request->file('archive');
        Storage::disk('local')->makeDirectory("imports/{$slug}");
        $tempDir = Storage::disk('local')->path("imports/{$slug}");

        $zip = new \ZipArchive();
        $zip->open($archive->getRealPath());
        $zip->extractTo($tempDir);
        $zip->close();

        $mdFile = $this->findMdFile($tempDir);
        $markdown = $mdFile ? file_get_contents($mdFile) : "# {$title}\n";
        $baseDir = dirname($mdFile ?? $tempDir);

        $subject = Subject::create([
            'user_id' => auth()->id(),
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'theme' => $theme,
            'title' => $title,
            'slug' => $slug,
            'body' => $markdown,
            'status' => $validated['status'],
            'visibility' => 'citoyen',
            'published_at' => $validated['status'] === 'published' ? now() : null,
        ]);

        $markdown = $this->importImages($subject, $markdown, $baseDir);
        $subject->update(['body' => $markdown]);

        Storage::disk('local')->deleteDirectory("imports/{$slug}");

        return redirect()
            ->route('subjects.show', $subject->slug)
            ->with('success', 'Sujet importé avec ses images.');
    }

    private function findMdFile(string $dir): ?string
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'md') {
                return $file->getPathname();
            }
        }

        return null;
    }

    private function importImages(Subject $subject, string $markdown, string $baseDir): string
    {
        $images = [];
        $counter = 0;

        $markdown = preg_replace_callback('/!\[([^\]]*)\]\(([^)]+)\)/', function ($matches) use ($baseDir, $subject, &$images, &$counter) {
            $alt = $matches[1];
            $src = $matches[2];

            if (preg_match('/^https?:\/\//', $src) || str_starts_with($src, '/')) {
                return $matches[0];
            }

            $fullPath = realpath($baseDir . '/' . $src);
            if (! $fullPath || ! is_file($fullPath)) {
                return $matches[0];
            }

            $counter++;
            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) ?: 'jpg';
            $filename = Str::slug($alt ?: 'image') . '-' . $counter . '.' . $ext;
            $destPath = "subjects/{$subject->id}/{$filename}";

            Storage::disk(self::DISK)->put($destPath, file_get_contents($fullPath));

            $maxPosition = SubjectImage::where('subject_id', $subject->id)->max('position') ?? 0;
            SubjectImage::firstOrCreate(
                ['subject_id' => $subject->id, 'filename' => $filename],
                [
                    'path' => $destPath,
                    'mime_type' => mime_content_type($fullPath) ?: 'image/jpeg',
                    'alt' => $alt ?: null,
                    'position' => $maxPosition + 1,
                ]
            );

            $images[$src] = Storage::disk(self::DISK)->url($destPath);

            return "![{$alt}]({$images[$src]})";
        }, $markdown);

        return $markdown;
    }

    private function uniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $counter = 1;

        while (Subject::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }
}
