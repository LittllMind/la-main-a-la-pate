<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\SubjectDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse|Response|\Illuminate\Http\RedirectResponse
    {
        $q = $request->string('q');
        if (empty($q) || strlen($q) < 2) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Terme de recherche trop court (minimum 2 caracteres)'], 422);
            }
            return redirect()->route('home');
        }

        $subjects = Subject::whereFullText(['title', 'body'], $q)
            ->select(['id', 'title', 'slug', 'status', 'created_at', 'user_id'])
            ->with('user:id,name')
            ->limit(20)
            ->get();

        $documents = SubjectDocument::whereFullText(['filename', 'description'], $q)
            ->with(['subject:id,title,slug'])
            ->limit(20)
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'subjects' => $subjects,
                'documents' => $documents,
                'query' => $q,
            ]);
        }

        return response()->view('search.results', compact('subjects', 'documents'));
    }
}
