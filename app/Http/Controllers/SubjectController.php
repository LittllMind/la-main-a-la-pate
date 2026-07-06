<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Subject;
use App\Models\SubjectComment;
use App\Models\SubjectVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SubjectController extends Controller
{
    private array $defaultThemes = [
        'Séraphothèque',
        'Urbanisme',
        'Mémoire',
        'Nature',
        'Vie du village',
    ];

    public function index()
    {
        $user = auth()->user();

        $query = Subject::with(['user', 'comments', 'collaborators', 'subCategory'])
            ->where('status', '!=', 'archived')
            ->orderBy('created_at', 'desc');

        // Filtrage par visibilité + status pour les non-admin
        if (! $user->isModeratorOrAdmin()) {
            // Citoyen = published public + published citoyen + ses propres drafts
            $query->where(function ($q) use ($user) {
                $q->where(function ($q2) {
                    $q2->where('status', 'published')
                        ->whereIn('visibility', ['public', 'citoyen']);
                })->orWhere(function ($q2) use ($user) {
                    $q2->where('status', 'draft')
                        ->where(function ($q3) use ($user) {
                            $q3->where('user_id', $user->id)
                                ->orWhereHas('collaborators', function ($q4) use ($user) {
                                    $q4->where('users.id', $user->id);
                                });
                        });
                });
            });
        } else {
            // Admin/Mod = tout sauf archived (tous les sujets y compris les brouillons des autres)
            $query->where(function ($q) {
                $q->where('status', 'published')
                  ->orWhere('status', 'draft');
            });
        }

        if ($user && $user->isModeratorOrAdmin()) {
            $selectedTheme = request('theme');
            if ($selectedTheme) {
                $query->where('theme', $selectedTheme);
            }
        }

        $subjects = $query->paginate(20)->withQueryString();

        $existingThemes = Subject::where('status', '!=', 'archived')
            ->distinct()
            ->orderBy('theme')
            ->pluck('theme');

        $themes = collect($this->defaultThemes)
            ->merge($existingThemes)
            ->uniqueStrict()
            ->sort()
            ->values();

        return view('subjects.index', [
            'subjects' => $subjects,
            'themes' => $themes,
            'selectedTheme' => $selectedTheme ?? null,
        ]);
    }

    public function create()
    {
        return view('subjects.create', [
            'themes' => collect($this->defaultThemes)->sort()->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'theme' => 'required|string|max:120',
            'theme_other' => 'nullable|string|max:120|required_if:theme,__new__',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:50000',
        ]);

        $theme = $this->resolveTheme($validated['theme'], $validated['theme_other'] ?? null);

        $subject = Subject::create([
            'user_id' => auth()->id(),
            'theme' => $theme,
            'title' => $validated['title'],
            'slug' => $this->uniqueSlug($validated['title']),
            'body' => $validated['body'],
            'status' => 'draft',
        ]);

        ActivityLog::log(
            event: 'create',
            user: auth()->user(),
            entityType: 'subject',
            entityId: $subject->id,
            description: "Création du sujet « {$subject->title} »",
            metadata: ['theme' => $theme, 'slug' => $subject->slug]
        );

        return redirect()
            ->route('subjects.show', $subject->slug)
            ->with('success', 'Sujet cree.');
    }

    public function show(Subject $subject)
    {
        Gate::authorize('view', $subject);

        return view('subjects.show', [
            'subject' => $subject->load(['user', 'comments.user', 'versions.user', 'documents']),
        ]);
    }

    public function edit(Subject $subject)
    {
        Gate::authorize('update', $subject);

        return view('subjects.edit', [
            'subject' => $subject,
            'themes' => collect($this->defaultThemes)->sort()->values(),
        ]);
    }

    public function update(Request $request, Subject $subject)
    {
        Gate::authorize('update', $subject);

        $validated = $request->validate([
            'theme' => 'required|string|max:120',
            'theme_other' => 'nullable|string|max:120',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:50000',
            'change_summary' => 'nullable|string|max:255',
        ]);

        $theme = $this->resolveTheme($validated['theme'], $validated['theme_other'] ?? null);

        SubjectVersion::create([
            'subject_id' => $subject->id,
            'user_id' => auth()->id(),
            'body' => $subject->body,
            'change_summary' => $validated['change_summary'] ?? null,
        ]);

        $subject->update([
            'theme' => $theme,
            'title' => $validated['title'],
            'body' => $validated['body'],
        ]);

        ActivityLog::log(
            event: 'update',
            user: auth()->user(),
            entityType: 'subject',
            entityId: $subject->id,
            description: "Modification du sujet « {$subject->title} »",
            metadata: ['change_summary' => $validated['change_summary'] ?? null]
        );

        return redirect()
            ->route('subjects.show', $subject->slug)
            ->with('success', 'Document mis a jour.');
    }

    public function destroy(Subject $subject)
    {
        Gate::authorize('delete', $subject);

        ActivityLog::log(
            event: 'delete',
            user: auth()->user(),
            entityType: 'subject',
            entityId: $subject->id,
            description: "Suppression du sujet « {$subject->title} »"
        );

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
        Gate::authorize('publish', $subject);

        if (! $subject->canBePublished()) {
            return redirect()
                ->route('subjects.show', $subject->slug)
                ->with('error', 'Ce sujet nécessite l\'approbation unanime de ses collaborateurs avant publication.');
        }

        $subject->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        ActivityLog::log(
            event: 'publish',
            user: auth()->user(),
            entityType: 'subject',
            entityId: $subject->id,
            description: "Publication du sujet « {$subject->title} »",
            metadata: ['visibility' => $subject->visibility]
        );

        return redirect()
            ->route('subjects.show', $subject->slug)
            ->with('success', 'Sujet publie.');
    }

    private function resolveTheme(string $theme, ?string $other): string
    {
        if ($theme !== '__new__') {
            return $theme;
        }

        $theme = trim($other ?? '');
        if ($theme === '') {
            return '';
        }

        $theme = mb_strtolower($theme);
        $first = mb_strtoupper(mb_substr($theme, 0, 1));
        $rest = mb_substr($theme, 1);

        return $first . $rest;
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
        $allowedTags = [
            'p' => [],
            'br' => [],
            'strong' => [],
            'b' => [],
            'em' => [],
            'i' => [],
            'u' => [],
            'ul' => [],
            'ol' => [],
            'li' => [],
            'h2' => [],
            'h3' => [],
            'a' => ['href', 'title'],
            'blockquote' => [],
            'table' => ['class'],
            'thead' => [],
            'tbody' => [],
            'tr' => [],
            'th' => [],
            'td' => ['class'],
            'img' => ['src', 'alt', 'class'],
        ];

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"?>' . $html);
        libxml_clear_errors();

        $body = $dom->getElementsByTagName('body')->item(0);
        if (! $body) {
            return '';
        }

        $this->cleanNode($body, $allowedTags);

        return trim($this->domInnerHtml($body));
    }

    private function cleanNode(\DOMElement $node, array $allowedTags): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof \DOMElement) {
                continue;
            }

            $tag = strtolower($child->nodeName);

            if (! isset($allowedTags[$tag])) {
                $node->removeChild($child);
                continue;
            }

            foreach (iterator_to_array($child->attributes) as $attr) {
                if (! in_array($attr->nodeName, $allowedTags[$tag], true)) {
                    $child->removeAttribute($attr->nodeName);
                }
            }

            if ($tag === 'a') {
                $href = $child->getAttribute('href');
                if (! str_starts_with($href, 'http://') && ! str_starts_with($href, 'https://') && ! str_starts_with($href, '/')) {
                    $child->setAttribute('href', '#');
                }
            }

            $this->cleanNode($child, $allowedTags);
        }
    }

    private function domInnerHtml(\DOMElement $element): string
    {
        $html = '';
        foreach ($element->childNodes as $child) {
            $html .= $element->ownerDocument->saveHTML($child);
        }

        return $html;
    }
}
