<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\SubjectComment;
use App\Models\SubjectVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SubjectController extends Controller
{
    private array $themes = [
        'Séraphothèque',
        'Urbanisme',
        'Mémoire',
        'Nature',
        'Vie du village',
    ];

    public function index()
    {
        $subjects = Subject::with('user')
            ->where('status', '!=', 'archived')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('subjects.index', [
            'subjects' => $subjects,
            'themes' => $this->themes,
        ]);
    }

    public function create()
    {
        return view('subjects.create', ['themes' => $this->themes]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'theme' => 'required|in:' . implode(',', $this->themes),
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:50000',
        ]);

        $subject = Subject::create([
            'user_id' => auth()->id(),
            'theme' => $validated['theme'],
            'title' => $validated['title'],
            'slug' => $this->uniqueSlug($validated['title']),
            'body' => $this->cleanHtml($validated['body']),
            'status' => 'draft',
        ]);

        return redirect()
            ->route('subjects.show', $subject->slug)
            ->with('success', 'Sujet cree.');
    }

    public function show(Subject $subject)
    {
        Gate::authorize('view', $subject);

        return view('subjects.show', [
            'subject' => $subject->load(['user', 'comments.user', 'versions.user']),
        ]);
    }

    public function edit(Subject $subject)
    {
        Gate::authorize('update', $subject);

        return view('subjects.edit', [
            'subject' => $subject,
            'themes' => $this->themes,
        ]);
    }

    public function update(Request $request, Subject $subject)
    {
        Gate::authorize('update', $subject);

        $validated = $request->validate([
            'theme' => 'required|in:' . implode(',', $this->themes),
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:50000',
            'change_summary' => 'nullable|string|max:255',
        ]);

        SubjectVersion::create([
            'subject_id' => $subject->id,
            'user_id' => auth()->id(),
            'body' => $subject->body,
            'change_summary' => $validated['change_summary'] ?? null,
        ]);

        $subject->update([
            'theme' => $validated['theme'],
            'title' => $validated['title'],
            'body' => $this->cleanHtml($validated['body']),
        ]);

        return redirect()
            ->route('subjects.show', $subject->slug)
            ->with('success', 'Document mis a jour.');
    }

    public function destroy(Subject $subject)
    {
        Gate::authorize('delete', $subject);
        $subject->delete();

        return redirect()->route('subjects.index')->with('success', 'Sujet supprime.');
    }

    public function storeComment(Request $request, Subject $subject)
    {
        Gate::authorize('view', $subject);

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        SubjectComment::create([
            'subject_id' => $subject->id,
            'user_id' => auth()->id(),
            'body' => $validated['body'],
        ]);

        return redirect()
            ->route('subjects.show', $subject->slug . '#comments')
            ->with('success', 'Commentaire ajoute.');
    }

    public function publish(Subject $subject)
    {
        Gate::authorize('update', $subject);

        $subject->update(['status' => 'published']);

        return redirect()
            ->route('subjects.show', $subject->slug)
            ->with('success', 'Sujet publie.');
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

    private function cleanHtml(string $html): string
    {
        $allowed = '<p><br></b><i><u><strong><em><ul><ol><li><h2><h3><a href title>';

        return strip_tags($html, $allowed);
    }
}
