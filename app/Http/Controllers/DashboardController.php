<?php

namespace App\Http\Controllers;

use App\Models\Subject;
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
            ->orderByDesc('last_activity_at')
            ->orderByDesc('subjects.id')
            ->with(['user', 'category', 'subCategory'])
            ->limit(5)
            ->get();

        $comments = $user->subjectComments()
            ->with('subject')
            ->latest()
            ->limit(5)
            ->get();

        $activity = \App\Models\ActivityLog::recent(20);

        return view('dashboard', compact('user', 'recentSubjects', 'comments', 'activity'));
    }
}
