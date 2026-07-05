<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->input('entity_type'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('pseudonyme', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->paginate(50)->withQueryString();

        $stats = [
            'total' => ActivityLog::count(),
            'logins_today' => ActivityLog::where('event_type', 'login')->whereDate('created_at', today())->count(),
            'creations_today' => ActivityLog::where('event_type', 'create')->whereDate('created_at', today())->count(),
            'updates_today' => ActivityLog::where('event_type', 'update')->whereDate('created_at', today())->count(),
        ];

        $eventTypes = ActivityLog::distinct()->orderBy('event_type')->pluck('event_type');
        $entityTypes = ActivityLog::distinct()->orderBy('entity_type')->pluck('entity_type');

        return view('admin.activity', compact('logs', 'stats', 'eventTypes', 'entityTypes'));
    }
}
