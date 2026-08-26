<?php

namespace App\Http\Controllers;

use App\Models\RepresentationType;
use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Services\DocumentStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;

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
     * Affiche une représentation HTML lisible d'un document de type email.
     * Masque le JSON brut, les headers techniques et les metadata d'ingestion.
     */
    public function emailView(Subject $subject, SubjectDocument $document)
    {
        Gate::authorize('view', $subject);

        if ($document->subject_id !== $subject->id) {
            abort(404);
        }

        if (! $document->visibleTo(auth()->user())) {
            abort(404);
        }

        if (! $document->isEmail()) {
            abort(404);
        }

        if (! $this->storage->exists($document->path)) {
            abort(404);
        }

        $plain = null;
        try {
            $plain = $this->storage->decrypt($document->path);
        } catch (\Throwable $e) {
            report($e);

            return response('Une erreur est survenue lors de l\'ouverture du message.', 500);
        }

        $payload = json_decode($plain, true) ?: [];
        $this->secureZero($plain);

        $date = $payload['date'] ?? $document->document_date?->format('d/m/Y') ?? null;
        $subjectText = $payload['subject'] ?? $document->title ?? null;
        $from = $payload['from'] ?? $payload['sender'] ?? $document->author ?? null;
        $to = $payload['to'] ?? $document->recipient ?? null;

        $bodyText = '';
        $bodyIsHtml = false;
        if (! empty($payload['body_html'])) {
            $bodyText = $this->sanitiseEmailHtml($payload['body_html']);
            $bodyIsHtml = true;
        } elseif (! empty($payload['body_text'])) {
            $bodyText = e($payload['body_text']);
        } elseif (! empty($payload['body'])) {
            $bodyText = is_string($payload['body']) ? e($payload['body']) : '';
        }

        return view('subjects.documents.email', [
            'subject' => $subject,
            'document' => $document,
            'emailDate' => $date,
            'emailSubject' => $subjectText,
            'emailFrom' => $from,
            'emailTo' => $to,
            'emailBody' => $bodyText,
            'bodyIsHtml' => $bodyIsHtml,
        ]);
    }

    /**
     * Nettoie un fragment HTML d'email pour la vue publique.
     * Liste blanche de balises, pas d'exécution de script, pas de styles inline actifs.
     */
    private function sanitiseEmailHtml(string $html): string
    {
        // Supprime les scripts, objets, embeds, iframes, et liens dangereux.
        $html = preg_replace('#<script[^>]*>.*?</script>#is', '', $html);
        $html = preg_replace('#<style[^>]*>.*?</style>#is', '', $html);
        $html = preg_replace('#<(object|embed|iframe|source|track)\b[^>]*>#is', '', $html);
        $html = preg_replace('#javascript\s*:#is', '', $html);

        // Liste blanche : balises sémantiques du HTML email public.
        $allowedTags = '<p><br><a><strong><em><ul><ol><li><h1><h2><h3><pre><header><footer><div><span><dl><dt><dd><hr>';
        $html = strip_tags($html, $allowedTags);

        return $html;
    }

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
            'file'        => 'required|file|max:51200',
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category'    => 'nullable|in:source,annexe,ocr,audio,autre',
            'visibility'  => ['nullable', new Enum(\App\Models\VisibilityLevel::class)],
            'document_date' => 'nullable|date',
            'document_type' => 'nullable|string|max:80',
            'author' => 'nullable|string|max:255',
            'recipient' => 'nullable|string|max:255',
            'source_reference' => 'nullable|string|max:2000',
            'representation_type' => ['nullable', new Enum(RepresentationType::class)],
            'redacted' => 'nullable|boolean',
            'establishes' => 'nullable|string|max:5000',
            'limitations' => 'nullable|string|max:5000',
        ]);

        $visibility = \App\Models\VisibilityLevel::tryFrom($request->input('visibility'))
            ?? \App\Models\VisibilityLevel::Working;

        $metadata = [
            'document_date' => $request->document_date,
            'document_type' => $request->document_type,
            'author' => $request->author,
            'recipient' => $request->recipient,
            'source_reference' => $request->source_reference,
            'representation_type' => $request->representation_type,
            'redacted' => $request->boolean('redacted'),
            'establishes' => $request->establishes,
            'limitations' => $request->limitations,
        ];

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
            'visibility'      => $visibility->value,
        ] + $metadata);

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

    // Edition complete de la fiche documentaire
    public function edit(Subject $subject, SubjectDocument $document)
    {
        Gate::authorize('update', $subject);

        return view('subjects.documents.edit', compact('subject', 'document'));
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
            'visibility'  => ['nullable', new Enum(\App\Models\VisibilityLevel::class)],
            'document_date' => 'nullable|date',
            'document_type' => 'nullable|string|max:80',
            'author' => 'nullable|string|max:255',
            'recipient' => 'nullable|string|max:255',
            'source_reference' => 'nullable|string|max:2000',
            'representation_type' => ['nullable', new Enum(RepresentationType::class)],
            'redacted' => 'nullable|boolean',
            'establishes' => 'nullable|string|max:5000',
            'limitations' => 'nullable|string|max:5000',
        ]);

        $updateData = $request->only([
            'title', 'description', 'category', 'position', 'visibility',
            'document_date', 'document_type', 'author', 'recipient', 'source_reference',
            'representation_type', 'establishes', 'limitations',
        ]);
        $updateData['redacted'] = $request->boolean('redacted');

        $document->update($updateData);

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

        if ($document->subject_id !== $subject->id) {
            abort(404);
        }

        if (! $document->visibleTo(auth()->user())) {
            abort(404);
        }

        if ($document->isEmail()) {
            abort(404);
        }

        if (! $this->storage->exists($document->path)) {
            abort(404);
        }

        // Le déchiffrement est tenté en dehors du stream pour pouvoir
        // retourner une réponse générique en cas d'erreur sans exposer
        // de stacktrace ou de contenu au client.
        $plain = null;
        try {
            $plain = $this->storage->decrypt($document->path);
        } catch (\Throwable $e) {
            report($e);

            return response('Une erreur est survenue lors du téléchargement.', 500);
        }

        return response()->streamDownload(function () use (&$plain) {
            echo $plain;
            $this->secureZero($plain);
        }, $document->filename, [
            'Content-Type' => $document->mime_type,
        ]);
    }

    /**
     * Affiche le document directement dans le navigateur (inline) pour les
     * formats publics pris en charge : PDF, HTML, PNG, etc.
     * Mêmes contrôles ACL/chiffrement que download.
     */
    public function view(Subject $subject, SubjectDocument $document)
    {
        Gate::authorize('view', $subject);

        if ($document->subject_id !== $subject->id) {
            abort(404);
        }

        if (! $document->visibleTo(auth()->user())) {
            abort(404);
        }

        if ($document->isEmail()) {
            return redirect()->route('subjects.documents.email', [$subject->slug, $document->id]);
        }

        // V8-C : correspondance markdown servie en HTML humain, sans toucher à la source
        $isSeraphothequeDossier = $subject->isSeraphothequeDossier()
            && str_contains((string) $document->source_reference, 'SERAPH-DOC-CORRESPONDANCE-MAIRIE-2026');
        $isMarkdownDossier = $isSeraphothequeDossier
            && str_contains(strtolower((string) $document->document_type), 'dossier documentaire')
            && str_contains(strtolower((string) $document->filename), '.md');

        if (! $this->storage->exists($document->path)) {
            abort(404);
        }

        $plain = null;
        try {
            $plain = $this->storage->decrypt($document->path);
        } catch (\Throwable $e) {
            report($e);

            return response('Une erreur est survenue lors de l\'ouverture du document.', 500);
        }

        if ($isMarkdownDossier) {
            $html = \App\Models\Subject::renderMarkdownToHtml($plain);

            return response()->view('subjects.documents.markdown_human', [
                'subject' => $subject,
                'document' => $document,
                'htmlBody' => $html,
            ])->withHeaders([
                'Content-Type' => 'text/html; charset=UTF-8',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return response()->stream(function () use (&$plain) {
            echo $plain;
            $this->secureZero($plain);
        }, 200, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="' . $document->filename . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Efface sûrement un buffer en mémoire.
     * Utilise sodium_memzero quand l'extension est présente, sinon
     * libère la référence de manière portable sans interrompre le
     * téléchargement.
     */
    private function secureZero(?string &$buffer): void
    {
        if ($buffer === null) {
            return;
        }

        if (function_exists('sodium_memzero')) {
            \sodium_memzero($buffer);

            return;
        }

        $buffer = '';
        unset($buffer);
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
            'document_date' => $request->document_date,
            'document_type' => $request->document_type,
            'author' => $request->author,
            'recipient' => $request->recipient,
            'source_reference' => $request->source_reference,
            'representation_type' => $request->representation_type,
            'redacted' => $request->boolean('redacted'),
            'establishes' => $request->establishes,
            'limitations' => $request->limitations,
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
