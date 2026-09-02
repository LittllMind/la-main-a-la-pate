<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\SubjectDocument;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $period = in_array((int) $request->query('period'), [7, 30], true) ? (int) $request->query('period') : 0;

        $start = $period === 0
            ? now()->startOfDay()
            : now()->subDays($period)->startOfDay();
        $end = now()->endOfDay();

        // KPI jours
        $dailyVisitors = AnalyticsEvent::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as day, COUNT(DISTINCT visitor_key) as visitors')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $cumulativeVisitors = (int) $dailyVisitors->sum('visitors');
        $uniqueVisitorsToday = $period === 0
            ? $cumulativeVisitors
            : (int) (clone AnalyticsEvent::query())
                ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
                ->distinct('visitor_key')
                ->count('visitor_key');

        // Pages vues
        $pageViews = AnalyticsEvent::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('event_type', 'page_view')
            ->count();

        // Visites approximées : page_view → groupement par visitor_key / 30 min d'inactivité
        $visitsCount = $this->approximateVisits($start, $end);

        // Dossier
        $dossierViews = AnalyticsEvent::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('event_type', 'dossier_view')
            ->count();

        // Documents
        $documentViews = AnalyticsEvent::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('event_type', 'document_view')
            ->count();

        $documentDownloads = AnalyticsEvent::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('event_type', 'document_download')
            ->count();

        // Top pages
        $topPages = AnalyticsEvent::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('event_type', 'page_view')
            ->selectRaw('path, COUNT(*) as total')
            ->groupBy('path')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Top documents
        $topDocumentsViews = AnalyticsEvent::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('event_type', 'document_view')
            ->whereNotNull('document_key')
            ->selectRaw('document_key, COUNT(*) as total')
            ->groupBy('document_key')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => $this->enrichDocument($row));

        $topDocumentsDownloads = AnalyticsEvent::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('event_type', 'document_download')
            ->whereNotNull('document_key')
            ->selectRaw('document_key, COUNT(*) as total')
            ->groupBy('document_key')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => $this->enrichDocument($row));

        // Répartition
        $referrers = AnalyticsEvent::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('referrer_host')
            ->selectRaw('referrer_host, COUNT(*) as total')
            ->groupBy('referrer_host')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $devices = AnalyticsEvent::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('device_family')
            ->selectRaw('device_family as label, COUNT(*) as total')
            ->groupBy('device_family')
            ->orderByDesc('total')
            ->get();

        $browsers = AnalyticsEvent::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('user_agent_family')
            ->selectRaw('user_agent_family as label, COUNT(*) as total')
            ->groupBy('user_agent_family')
            ->orderByDesc('total')
            ->get();

        // Courbe
        $curveLabels = $dailyVisitors->pluck('day');
        $curveVisitors = $dailyVisitors->pluck('visitors');

        $curvePageViews = $this->dailyCounts('page_view', $start, $end);
        $curveDocViews = $this->dailyCounts('document_view', $start, $end);
        $curveDocDownloads = $this->dailyCounts('document_download', $start, $end);

        return view('admin.analytics.index', compact(
            'period',
            'uniqueVisitorsToday',
            'cumulativeVisitors',
            'visitsCount',
            'pageViews',
            'dossierViews',
            'documentViews',
            'documentDownloads',
            'topPages',
            'topDocumentsViews',
            'topDocumentsDownloads',
            'referrers',
            'devices',
            'browsers',
            'curveLabels',
            'curveVisitors',
            'curvePageViews',
            'curveDocViews',
            'curveDocDownloads',
        ));
    }

    private function approximateVisits(Carbon $start, Carbon $end): int
    {
        $rows = AnalyticsEvent::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('event_type', ['page_view', 'dossier_view'])
            ->orderBy('visitor_key')
            ->orderBy('created_at')
            ->get(['visitor_key', 'created_at']);

        $visits = 0;
        $last = null;
        $threshold = now()->copy()->subMinutes(30);

        foreach ($rows as $row) {
            if ($last === null || $row->visitor_key !== $last['key'] || $row->created_at->diffInMinutes($last['time']) > 30) {
                $visits++;
                $last = ['key' => $row->visitor_key, 'time' => $row->created_at];
            }
        }

        return $visits;
    }

    private function dailyCounts(string $eventType, Carbon $start, Carbon $end): array
    {
        $rows = AnalyticsEvent::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('event_type', $eventType)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day')
            ->toArray();

        $result = [];
        $cursor = $start->clone();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $result[] = $rows[$key] ?? 0;
            $cursor->addDay();
        }

        return $result;
    }

    private function enrichDocument($row): object
    {
        $resolved = SubjectDocument::query()
            ->where('source_reference', $row->document_key)
            ->first();

        $row->title = $resolved?->title;

        return $row;
    }
}
