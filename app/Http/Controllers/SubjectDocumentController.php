<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\SubjectDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SubjectDocumentController extends Controller
{
    // Liste des documents d'un sujet
    public function index(Subject $subject)
    {
        Gate::authorize('view', $subject);

        return view('subjects.documents.index', [
            'subject' => $subject->load('documents'),
        ]);
    }

    // Upload d'un document
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
        $original = $file->getClientOriginalName();
        $stored = Str::slug(pathinfo($original, PATHINFO_FILENAME)) . '-' . Str::random(6) . '.' . $file->getClientOriginalExtension();
        $path = "subject_documents/{$subject->id}/" . $stored;

        Storage::disk('subject_documents')->put($path, file_get_contents($file->getRealPath()));

        $doc = SubjectDocument::create([
            'subject_id'      => $subject->id,
            'filename'        => $original,
            'stored_filename' => $stored,
            'path'            => $path,
            'mime_type'       => $file->getMimeType(),
            'size'            => $file->getSize(),
            'title'           => $request->title ?: $original,
            'description'     => $request->description,
            'category'        => $request->category ?: 'source',
            'position'        => $subject->documents()->count() + 1,
        ]);

        return redirect()
            ->route('subjects.documents.index', $subject->slug)
            ->with('success', 'Document ajoute : ' . $doc->title);
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

        Storage::disk('subject_documents')->delete($document->path);
        $document->delete();

        return redirect()
            ->route('subjects.documents.index', $subject->slug)
            ->with('success', 'Document supprime.');
    }

    // Telechargement d'un document (stream securise)
    public function download(Subject $subject, SubjectDocument $document)
    {
        Gate::authorize('view', $subject);

        if (! Storage::disk('subject_documents')->exists($document->path)) {
            abort(404);
        }

        return response()->streamDownload(function () use ($document) {
            $stream = Storage::disk('subject_documents')->readStream($document->path);
            fpassthru($stream);
            fclose($stream);
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

        $stored = Str::slug(pathinfo($title, PATHINFO_FILENAME)) . '-' . Str::random(6) . '.pdf';
        $path = "subject_documents/{$subject->id}/" . $stored;

        Storage::disk('subject_documents')->put($path, $pdfContent);

        $doc = SubjectDocument::create([
            'subject_id'      => $subject->id,
            'filename'        => $title . '.pdf',
            'stored_filename' => $stored,
            'path'            => $path,
            'mime_type'       => 'application/pdf',
            'size'            => strlen($pdfContent),
            'title'           => $title,
            'description'     => $request->description,
            'category'        => $request->category ?: 'annexe',
            'position'        => $subject->documents()->count() + 1,
        ]);

        return redirect()
            ->route('subjects.documents.index', $subject->slug)
            ->with('success', 'Document PDF genere : ' . $doc->title);
    }
}
