<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $subjects = $user->subjects()
            ->latest()
            ->limit(5)
            ->get();

        $comments = $user->subjectComments()
            ->with('subject')
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard', compact('user', 'subjects', 'comments'));
    }
}
