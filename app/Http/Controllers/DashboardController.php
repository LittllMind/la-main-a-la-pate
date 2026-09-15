<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Subject;
use App\Models\SubjectDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $recentSubjects = Subject::query()
            ->subjectLastActivity()
            ->visibleTo($user)
            ->where('status', '!=', 'archived')
            ->orderByDesc('last_activity_at')
            ->orderByDesc('subjects.id')
            ->with(['user', 'category', 'subCategory'])
            ->limit(5)
            ->get();

        $comments = $user->subjectComments()
            ->whereHas('subject', fn ($query) => $query->visibleTo($user))
            ->with('subject')
            ->latest()
            ->limit(5)
            ->get();

        $visibleSubjectIds = Subject::query()
            ->visibleTo($user)
            ->pluck('subjects.id');
        $visibleDocumentIds = SubjectDocument::query()
            ->visibleTo($user)
            ->pluck('subject_documents.id');

        $activity = ActivityLog::with('user')
            ->where(function ($query) use ($visibleSubjectIds, $visibleDocumentIds) {
                $query->whereNull('entity_type')
                    ->orWhereNotIn('entity_type', ['subject', 'subject_document'])
                    ->orWhere(function ($subjectQuery) use ($visibleSubjectIds) {
                        $subjectQuery->where('entity_type', 'subject')
                            ->whereIn('entity_id', $visibleSubjectIds);
                    })
                    ->orWhere(function ($documentQuery) use ($visibleDocumentIds) {
                        $documentQuery->where('entity_type', 'subject_document')
                            ->whereIn('entity_id', $visibleDocumentIds);
                    });
            })
            ->latest()
            ->limit(20)
            ->get();

        return view('dashboard', compact('user', 'recentSubjects', 'comments', 'activity'));
    }
}
