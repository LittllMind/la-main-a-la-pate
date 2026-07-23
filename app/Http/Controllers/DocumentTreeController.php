<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class DocumentTreeController extends Controller
{
    /**
     * Page d'arborescence documentaire.
     */
    public function index()
    {
        return view('documents.tree');
    }

    /**
     * Données JSON pour l'arborescence, filtrées selon les droits de l'utilisateur courant.
     */
    public function data(): JsonResponse
    {
        $categories = Category::with([
            'subCategories' => fn ($q) => $q->orderBy('name'),
            'subCategories.subjects' => fn ($q) => $q->orderBy('title'),
            'subCategories.subjects.documents' => fn ($q) => $q->orderBy('position')->orderBy('created_at'),
        ])->orderBy('display_order')->orderBy('name')->get();

        $tree = $categories->map(function (Category $category) {
            $subs = $category->subCategories->map(function ($sub) {
                $subjects = $sub->subjects->filter(fn ($s) => Gate::check('view', $s))->map(function ($subject) {
                    return [
                        'id'          => $subject->id,
                        'slug'        => $subject->slug,
                        'title'       => $subject->title,
                        'status'      => $subject->status,
                        'visibility'  => $subject->visibility,
                        'can_update'  => Gate::check('update', $subject),
                        'word_count'  => $this->wordCount($subject->body),
                        'documents'   => $subject->documents->map(fn ($doc) => $this->mapDocument($doc))->values(),
                    ];
                })->values();

                if ($subjects->isEmpty()) {
                    return null;
                }

                return [
                    'id'        => $sub->id,
                    'name'      => $sub->name,
                    'slug'      => $sub->slug,
                    'subjects'  => $subjects,
                ];
            })->filter()->values();

            if ($subs->isEmpty()) {
                return null;
            }

            return [
                'id'            => $category->id,
                'name'          => $category->name,
                'slug'          => $category->slug,
                'icon'          => $category->icon,
                'subCategories' => $subs,
            ];
        })->filter()->values();

        return response()->json($tree);
    }

    private function mapDocument(\App\Models\SubjectDocument $doc): array
    {
        return [
            'id'          => $doc->id,
            'title'       => $doc->title ?: $doc->filename,
            'filename'    => $doc->filename,
            'extension'   => $doc->extension(),
            'size'        => $doc->humanSize(),
            'category'    => $doc->category,
            'description' => $doc->description,
            'icon'        => $doc->icon(),
            'download_url'=> route('subjects.documents.download', [$doc->subject->slug, $doc->id]),
            'created_at'  => $doc->created_at?->format('d/m/Y'),
        ];
    }

    private function wordCount(?string $body): int
    {
        if (! $body) {
            return 0;
        }
        $text = strip_tags($body);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return str_word_count($text);
    }
}
