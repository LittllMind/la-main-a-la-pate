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

        $user = auth()->user();

        $subjectQuery = Subject::visibleTo($user)
            ->select(['id', 'title', 'slug', 'status', 'created_at', 'user_id'])
            ->with('user:id,name')
            ->limit(20);

        if ($user !== null && $user->isModeratorOrAdmin()) {
            $subjectQuery->whereFullText(['title', 'body'], $q);
        } else {
            // L'index FULLTEXT ne couvre que (title, body).
            // Pour les guests et les citoyens, on recherche d'abord par titre
            // pour ne pas casser l'index ni exposer de contenu interne.
            $subjectQuery->where('title', 'like', '%' . addcslashes($q, '%_\\') . '%');
        }

        $subjects = $subjectQuery->get();

        $documentQuery = SubjectDocument::whereFullText(['filename', 'description'], $q)
            ->with(['subject:id,title,slug'])
            ->whereHas('subject', function ($sq) use ($user) {
                $sq->visibleTo($user);
            })
            ->visibleTo($user)
            ->limit(20);

        $documents = $documentQuery->get();

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
