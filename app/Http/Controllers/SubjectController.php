<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Subject;
use App\Models\SubjectComment;
use App\Models\SubjectVersion;
use App\Models\SubjectUserLastSeenVersion;
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

        $query = Subject::with(['user', 'comments', 'collaborators', 'subCategory', 'category'])
            ->where('status', '!=', 'archived')
            ->orderBy('created_at', 'desc');

        // Filtrage par visibilité + status pour les non-admin
        if (! $user->isModeratorOrAdmin()) {
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
            $query->where(function ($q) {
                $q->where('status', 'published')
                  ->orWhere('status', 'draft');
            });
        }

        $activeCategory = null;
        $selectedThemeSlug = request('theme');
        if ($selectedThemeSlug) {
            $activeCategory = \App\Models\Category::where('slug', $selectedThemeSlug)->first();
            if ($activeCategory) {
                $query->where('category_id', $activeCategory->id);
            } else {
                // fallback legacy text theme
                $query->where('theme', $selectedThemeSlug);
            }
        }

        $subjects = $query->paginate(24)->withQueryString();

        $categories = \App\Models\Category::withCount(['subjects' => function ($q) {
            $q->where('status', '!=', 'archived');
        }])->orderBy('id')->get();

        return view('subjects.index', [
            'subjects' => $subjects,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'selectedTheme' => $selectedThemeSlug,
        ]);
    }

    public function create()
    {
        $categories = \App\Models\Category::with('subCategories')->orderBy('id')->get();
        $categoriesJson = $categories->map(function($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'subs' => $c->subCategories->map(function($s) {
                    return ['id' => $s->id, 'name' => $s->name];
                })->toArray(),
            ];
        })->toJson();

        return view('subjects.create', compact('categories', 'categoriesJson'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'sub_category_id' => 'required|integer|exists:sub_categories,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:50000',
        ]);

        $category = \App\Models\Category::find($validated['category_id']);
        $subCategory = \App\Models\SubCategory::where('id', $validated['sub_category_id'])
            ->where('category_id', $validated['category_id'])
            ->first();

        if (! $subCategory) {
            return back()
                ->withInput()
                ->withErrors(['sub_category_id' => 'Le sous-thème choisi n\'appartient pas au thème sélectionné.']);
        }

        $subject = Subject::create([
            'user_id' => auth()->id(),
            'theme' => $category->name,
            'category_id' => $validated['category_id'],
            'sub_category_id' => $validated['sub_category_id'],
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
            metadata: ['theme' => $category->name, 'slug' => $subject->slug]
        );

        return redirect()
            ->route('subjects.show', $subject->slug)
            ->with('success', 'Sujet cree.');
    }

    public function show(Subject $subject)
    {
        Gate::authorize('view', $subject);

        $subject = $subject->load(['user', 'comments.user', 'versions.user', 'documents']);

        if (auth()->check()) {
            $latestVersionId = $subject->versions()->orderBy('created_at', 'desc')->value('id');
            if ($latestVersionId) {
                SubjectUserLastSeenVersion::updateOrCreate(
                    ['user_id' => auth()->id(), 'subject_id' => $subject->id],
                    ['version_id' => $latestVersionId, 'seen_at' => now()]
                );
            }
        }

        return view('subjects.show', [
            'subject' => $subject,
        ]);
    }

    public function edit(Subject $subject)
    {
        Gate::authorize('update', $subject);

        $categories = \App\Models\Category::with('subCategories')->orderBy('id')->get();
        $categoriesJson = $categories->map(function($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'subs' => $c->subCategories->map(function($s) {
                    return ['id' => $s->id, 'name' => $s->name];
                })->toArray(),
            ];
        })->toJson();

        return view('subjects.edit', [
            'subject' => $subject,
            'categories' => $categories,
            'categoriesJson' => $categoriesJson,
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
