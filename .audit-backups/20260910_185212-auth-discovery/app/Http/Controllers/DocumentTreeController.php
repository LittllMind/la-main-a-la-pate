<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class DocumentTreeController extends Controller
{
    /* --------------------------------------------------------------------- */
    /* ARBRE DOCUMENTS                                                       */
    /* --------------------------------------------------------------------- */

    public function documentsIndex()
    {
        return view('documents.tree', [
            'mode'   => 'documents',
            'apiUrl' => route('documents.tree.documents.data'),
        ]);
    }

    public function documentsData(): JsonResponse
    {
        return response()->json(
            $this->treeData(includeDocuments: true)
        );
    }

    /* --------------------------------------------------------------------- */
    /* ARBRE SUJETS                                                          */
    /* --------------------------------------------------------------------- */

    public function subjectsIndex()
    {
        return view('documents.tree', [
            'mode'   => 'subjects',
            'apiUrl' => route('subjects.tree.data'),
        ]);
    }

    public function subjectsData(): JsonResponse
    {
        return response()->json(
            $this->treeData(includeDocuments: false)
        );
    }

    /* --------------------------------------------------------------------- */
    /* DONNÉES COMMUNES                                                    */
    /* --------------------------------------------------------------------- */

    private function treeData(bool $includeDocuments): array
    {
        $user = auth()->user();

        $categories = Category::with([
            'subCategories' => fn ($q) => $q->orderBy('name'),
            'subCategories.subjects' => fn ($q) => $q->visibleTo($user)->listedInCatalogue()->orderBy('title'),
            'subCategories.subjects.documents' => fn ($q) => $q->visibleTo($user)->orderBy('position')->orderBy('created_at'),
        ])->orderBy('display_order')->orderBy('name')->get();

        return $categories->map(function (Category $category) use ($includeDocuments, $user) {
            $subs = $category->subCategories->map(function ($sub) use ($includeDocuments, $user) {
                $subjects = $sub->subjects->map(function ($subject) use ($includeDocuments, $user) {
                    $data = [
                        'id'         => $subject->id,
                        'slug'       => $subject->slug,
                        'title'      => $subject->title,
                        'status'     => $subject->status,
                        'visibility' => $subject->visibility,
                        'can_update' => Gate::check('update', $subject),
                        'word_count' => $this->wordCount($subject->bodyFor($user)),
                        'doc_count'  => $subject->documents->count(),
                    ];

                    if ($includeDocuments) {
                        $data['documents'] = $subject->documents->map(fn ($doc) => $this->mapDocument($doc))->values();
                    }

                    return $data;
                })->values();

                if ($subjects->isEmpty()) {
                    return null;
                }

                return [
                    'id'       => $sub->id,
                    'name'     => $sub->name,
                    'slug'     => $sub->slug,
                    'subjects' => $subjects,
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
        })->filter()->values()->toArray();
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
