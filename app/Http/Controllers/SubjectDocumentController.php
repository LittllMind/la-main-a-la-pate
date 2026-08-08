<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Services\DocumentStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SubjectDocumentController extends Controller
{
    private DocumentStorageService $storage;

    public function __construct(DocumentStorageService $storage)
    {
        $this->storage = $storage;
    }

    // Liste des documents d'un sujet
    public function index(Subject $subject)
    {
        Gate::authorize('view', $subject);

        return view('subjects.documents.index', [
            'subject' => $subject->load([
                'documents' => fn ($query) => $query->visibleTo(auth()->user()),
            ]),
        ]);
    }

    /**
     * Determine if a file is an image and should be stored as gallery image, not document.
     */
    private function isImageFile(\Illuminate\Http\UploadedFile $file): bool
    {
        return in_array($file->getMimeType(), [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/bmp',
            'image/svg+xml',
        ], true);
    }

    public function store(Request $request, Subject $subject)
    {
        Gate::authorize('update', $subject);

        $request->validate([
            'file'        => 'required|file|max:51200', // 50 Mo max
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category'    => 'nullable|in:source,annexe,ocr,audio,autre',
        ]);

        $file = $request->file('file');

        // Images go to gallery, not documents
        if ($this->isImageFile($file)) {
            $request->validate(['file' => 'image']);

            $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '-' . Str::random(4) . '.' . $file->getClientOriginalExtension();
            $path = "subjects/{$subject->id}/{$filename}";

            \Illuminate\Support\Facades\Storage::disk('subject_images')->put($path, file_get_contents($file->getRealPath()));

            $maxPosition = \App\Models\SubjectImage::where('subject_id', $subject->id)->max('position') ?? 0;

            \App\Models\SubjectImage::create([
                'subject_id' => $subject->id,
                'filename'   => $filename,
                'path'       => $path,
                'mime_type'  => $file->getMimeType(),
                'alt'        => $request->title ?: $file->getClientOriginalName(),
                'position'   => $maxPosition + 1,
            ]);

            return redirect()
                ->route('subjects.show', $subject->slug)
                ->with('success', 'Image ajoutée à la galerie.');
        }

        $original = $file->getClientOriginalName();
        $storedRelativePath = $this->storage->storeEncrypted(
            $subject->id,
            $file->getRealPath(),
            $original
        );

        $stored = basename($storedRelativePath);

        $doc = SubjectDocument::create([
            'subject_id'      => $subject->id,
            'filename'        => $original,
            'stored_filename' => $stored,
            'path'            => $storedRelativePath,
            'disk'            => 'documents',
            'mime_type'       => $file->getMimeType(),
            'size'            => $file->getSize(),
            'title'           => $request->title ?: $original,
            'description'     => $request->description,
            'category'        => $request->category ?: 'source',
            'position'        => $subject->documents()->count() + 1,
            'visibility'      => \App\Models\VisibilityLevel::Working->value,
        ]);

        \App\Models\ActivityLog::log(
            event: 'create',
            user: auth()->user(),
            entityType: 'subject_document',
            entityId: $doc->id,
            description: "Document attaché au sujet « {$subject->title} » : {$doc->title}",
            metadata: ['subject_id' => $subject->id, 'category' => $doc->category, 'size' => $doc->size]
        );

        return redirect()
            ->route('subjects.documents.index', $subject->slug)
            ->with('success', 'Document ajouté : ' . $doc->title);
    }

    // Mise a jour des metadonnees
    public function update(Request $request, Subject $subject, SubjectDocument $document)
    {
        Gate::authorize('update', $subject);

        $request->validate([
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category'    => 'nullable|in:source,annexe,ocr,audio,autre',
            'position'    => 'nullable|integer|min:0',
        ]);

        $document->update($request->only(['title', 'description', 'category', 'position']));

        return redirect()
            ->route('subjects.documents.index', $subject->slug)
            ->with('success', 'Document mis a jour.');
    }

    // Suppression d'un document
    public function destroy(Subject $subject, SubjectDocument $document)
    {
        Gate::authorize('update', $subject);

        $this->storage->delete($document->path);
        $document->delete();

        \App\Models\ActivityLog::log(
            event: 'delete',
            user: auth()->user(),
            entityType: 'subject_document',
            entityId: $document->id,
            description: "Document supprimé du sujet « {$subject->title} »",
            metadata: ['subject_id' => $subject->id]
        );

        return redirect()
            ->route('subjects.documents.index', $subject->slug)
            ->with('success', 'Document supprime.');
    }

    public function download(Subject $subject, SubjectDocument $document)
    {
        Gate::authorize('view', $subject);

        if (! $document->visibleTo(auth()->user())) {
            abort(404);
        }

        if (! $this->storage->exists($document->path)) {
            abort(404);
        }

        return response()->streamDownload(function () use ($document) {
            $plain = $this->storage->decrypt($document->path);
            echo $plain;
            sodium_memzero($plain);
        }, $document->filename, [
            'Content-Type' => $document->mime_type,
        ]);
    }

    // Convertir un texte Markdown en PDF et l'attacher comme document
    public function storeMarkdownPdf(Request $request, Subject $subject)
    {
        Gate::authorize('update', $subject);

        $request->validate([
            'title'       => 'required|string|max:255',
            'markdown'    => 'required|string|max:50000',
            'description' => 'nullable|string|max:1000',
            'category'    => 'nullable|in:source,annexe,ocr,audio,autre',
        ]);

        $title    = $request->title;
        $markdown = $request->markdown;

        // Render markdown to HTML via Subject renderer
        $html = $subject->renderBody();
        // Actually we need to render the provided markdown, not the subject body
        // Let's inline the conversion
        $converter = new \League\CommonMark\GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        $html = (string) $converter->convert($markdown);

        // Build a clean PDF view
        $pdfHtml = view('subjects.pdf.markdown', [
            'title'    => $title,
            'htmlBody' => $html,
            'subject'  => $subject,
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($pdfHtml);
        $pdfContent = $pdf->output();

        $tmpPath = tempnam(sys_get_temp_dir(), 'lmalp_pdf_');
        file_put_contents($tmpPath, $pdfContent);

        $storedRelativePath = $this->storage->storeEncrypted($subject->id, $tmpPath, $title . '.pdf');
        unlink($tmpPath);

        $stored = basename($storedRelativePath);

        $doc = SubjectDocument::create([
            'subject_id'      => $subject->id,
            'filename'        => $title . '.pdf',
            'stored_filename' => $stored,
            'path'            => $storedRelativePath,
            'disk'            => 'documents',
            'mime_type'       => 'application/pdf',
            'size'            => strlen($pdfContent),
            'title'           => $title,
            'description'     => $request->description,
            'category'        => $request->category ?: 'annexe',
            'position'        => $subject->documents()->count() + 1,
            'visibility'      => \App\Models\VisibilityLevel::Working->value,
        ]);

        \App\Models\ActivityLog::log(
            event: 'create',
            user: auth()->user(),
            entityType: 'subject_document',
            entityId: $doc->id,
            description: "PDF généré et attaché au sujet « {$subject->title} » : {$doc->title}",
            metadata: ['subject_id' => $subject->id, 'source' => 'markdown-pdf', 'size' => $doc->size]
        );

        return redirect()
            ->route('subjects.documents.index', $subject->slug)
            ->with('success', 'Document PDF genere : ' . $doc->title);
    }
}
