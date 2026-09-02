<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\SubjectDocument;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsReportGenerator
{
    /**
     * Génère le rapport audience pour un jour donné en Europe/Paris.
     *
     * @return array{
     *     date: string,
     *     measured_at: string,
     *     visitors: int,
     *     visits: int,
     *     page_views: int,
     *     seraphotheque_landing: int,
     *     seraphotheque_dossier: int,
     *     document_views: int,
     *     document_downloads: int,
     *     top_pages: array<int, array{path: string, total: int}>,
     *     top_document_views: array<int, array{document_key: string|null, title: string|null, total: int}>,
     *     top_document_downloads: array<int, array{document_key: string|null, title: string|null, total: int}>,
     *     referrers: array<int, array{host: string|null, total: int}>,
     *     devices: array<int, array{label: string|null, total: int}>,
     *     browsers: array<int, array{label: string|null, total: int}>,
     *     previous: array{visitors: int, page_views: int},
     * }
     */
    public function generate(Carbon $parisDay): array
    {
        \Carbon\Carbon::setLocale('fr');
        \setlocale(LC_TIME, 'fr_FR.utf8', 'fr_FR', 'fr');

        $start = $parisDay->copy()->startOfDay()->timezone('UTC');
        $end = $parisDay->copy()->endOfDay()->timezone('UTC');

        $previousDay = $parisDay->copy()->subDay();

        return [
            'date' => $parisDay->toDateString(),
            'measured_at' => $parisDay->isoFormat('D MMMM YYYY'),
            'visitors' => $this->uniqueVisitors($start, $end),
            'visits' => $this->approximateVisits($start, $end),
            'page_views' => $this->countEvent('page_view', $start, $end),
            'seraphotheque_landing' => $this->countEvent('page_view', $start, $end, ['path' => '/seraphotheque']),
            'seraphotheque_dossier' => $this->countEvent('dossier_view', $start, $end, ['path' => '/seraphotheque']),
            'document_views' => $this->countEvent('document_view', $start, $end),
            'document_downloads' => $this->countEvent('document_download', $start, $end),
            'top_pages' => $this->topPaths($start, $end),
            'top_document_views' => $this->topDocuments('document_view', $start, $end),
            'top_document_downloads' => $this->topDocuments('document_download', $start, $end),
            'referrers' => $this->topReferrers($start, $end),
            'devices' => $this->topDimension('device_family', $start, $end),
            'browsers' => $this->topDimension('user_agent_family', $start, $end),
            'previous' => $this->computePrevious($previousDay),
        ];
    }

    private function uniqueVisitors(Carbon $start, Carbon $end): int
    {
        return (int) AnalyticsEvent::query()
            ->whereBetween('created_at', [$start, $end])
            ->distinct('visitor_key')
            ->count('visitor_key');
    }

    private function countEvent(string $eventType, Carbon $start, Carbon $end, array $extra = []): int
    {
        return (int) AnalyticsEvent::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('event_type', $eventType)
            ->when($extra['path'] ?? null, fn ($q, $path) => $q->where('path', $path))
            ->count();
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

        foreach ($rows as $row) {
            $time = $row->created_at instanceof Carbon ? $row->created_at : Carbon::parse($row->created_at);

            if ($last === null
                || $row->visitor_key !== $last['key']
                || $time->diffInMinutes($last['time']) > 30
            ) {
                $visits++;
                $last = ['key' => $row->visitor_key, 'time' => $time];
            }
        }

        return $visits;
    }

    private function topPaths(Carbon $start, Carbon $end): array
    {
        return AnalyticsEvent::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('event_type', 'page_view')
            ->selectRaw('path, COUNT(*) as total')
            ->groupBy('path')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => ['path' => $row->path, 'total' => (int) $row->total])
            ->toArray();
    }

    private function topDocuments(string $eventType, Carbon $start, Carbon $end): array
    {
        $rows = AnalyticsEvent::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('event_type', $eventType)
            ->whereNotNull('document_key')
            ->selectRaw('document_key, COUNT(*) as total')
            ->groupBy('document_key')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $keys = $rows->pluck('document_key')->filter()->unique()->values()->toArray();
        $titles = $this->resolveDocumentTitles($keys);

        return $rows->map(fn ($row) => [
            'document_key' => $row->document_key,
            'title' => $titles[$row->document_key] ?? null,
            'total' => (int) $row->total,
        ])->toArray();
    }

    /**
     * @param array<string> $keys
     *
     * @return array<string, string|null>
     */
    private function resolveDocumentTitles(array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        return SubjectDocument::query()
            ->whereIn('source_reference', $keys)
            ->pluck('title', 'source_reference')
            ->toArray();
    }

    private function topReferrers(Carbon $start, Carbon $end): array
    {
        $rows = AnalyticsEvent::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('referrer_host')
            ->selectRaw('referrer_host as host, COUNT(*) as total')
            ->groupBy('referrer_host')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => ['host' => $row->host, 'total' => (int) $row->total])
            ->toArray();

        $directHits = (int) AnalyticsEvent::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('referrer_host')
            ->count();

        if ($directHits > 0) {
            $rows[] = ['host' => 'Direct', 'total' => $directHits];
        }

        usort($rows, fn ($a, $b) => $b['total'] <=> $a['total']);

        return array_slice($rows, 0, 10);
    }

    private function topDimension(string $column, Carbon $start, Carbon $end): array
    {
        return AnalyticsEvent::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull($column)
            ->selectRaw("{$column} as label, COUNT(*) as total")
            ->groupBy($column)
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'total' => (int) $row->total])
            ->toArray();
    }

    private function computePrevious(Carbon $previousDay): array
    {
        $start = $previousDay->copy()->startOfDay()->timezone('UTC');
        $end = $previousDay->copy()->endOfDay()->timezone('UTC');

        return [
            'visitors' => $this->uniqueVisitors($start, $end),
            'page_views' => $this->countEvent('page_view', $start, $end),
        ];
    }
}
