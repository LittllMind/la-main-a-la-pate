<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\SubjectDocument;
use Illuminate\Http\Request;
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
        $path   = "subjects/{$subject->id}/" . $stored;

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
}
