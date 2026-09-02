<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class AnalyticsRecorder
{
    private AnalyticsVisitorResolver $resolver;

    public function __construct(Request $request)
    {
        $this->resolver = new AnalyticsVisitorResolver((string) Config::get('analytics.hmac_key', ''));
    }

    public function shouldRecord(Request $request): bool
    {
        if (! (bool) Config::get('analytics.enabled', false)) {
            return false;
        }

        if (in_array(app()->environment(), (array) Config::get('analytics.excluded_environments', []), true)) {
            return false;
        }

        if (! in_array(strtoupper($request->method()), array_map('strtoupper', (array) Config::get('analytics.allowed_methods', ['GET'])), true)) {
            return false;
        }

        $ua = strtolower((string) $request->userAgent());
        if ($this->resolver->isBot($ua)) {
            return false;
        }

        if (! $request->isMethodCacheable() || $request->ajax()) {
            return false;
        }

        $path = $this->normalisePath($request);
        foreach ((array) Config::get('analytics.excluded_path_prefixes', []) as $prefix) {
            if (str_starts_with($path, '/' . $prefix) || str_starts_with($path, $prefix)) {
                return false;
            }
        }

        return true;
    }

    public function record(string $eventType, ?string $documentKey = null, ?string $overridePath = null, Request $request = null): ?AnalyticsEvent
    {
        $request ??= request();

        if (! $this->shouldRecord($request)) {
            return null;
        }

        try {
            $ip = $request->ip() ?? '0.0.0.0';
            $ua = (string) $request->userAgent();
            $resolved = $this->resolver->resolve($ip, $ua);

            return AnalyticsEvent::create([
                'event_type' => $eventType,
                'visitor_key' => $resolved['key'],
                'path' => $overridePath ?? $this->normalisePath($request),
                'document_key' => $documentKey,
                'referrer_host' => $this->extractReferrerHost($request),
                'user_agent_family' => $resolved['family'],
                'device_family' => $resolved['device'],
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Analytics recording failed', [
                'event' => $eventType,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function recordPageView(Request $request): ?AnalyticsEvent
    {
        return $this->record('page_view', request: $request);
    }

    public function recordDossierView(string $dossierPath, Request $request): ?AnalyticsEvent
    {
        return $this->record('dossier_view', overridePath: $dossierPath, request: $request);
    }

    public function recordDocumentView(string $documentKey, Request $request): ?AnalyticsEvent
    {
        return $this->record('document_view', documentKey: $documentKey, request: $request);
    }

    public function recordDocumentDownload(string $documentKey, Request $request): ?AnalyticsEvent
    {
        return $this->record('document_download', documentKey: $documentKey, request: $request);
    }

    /**
     * Renvoie le path normalisé sans query string et borné à 500 caractères.
     */
    public function normalisePath(Request $request): string
    {
        $path = $request->path();
        $path = '/' . ltrim($path, '/');

        return substr($path, 0, 500);
    }

    private function extractReferrerHost(Request $request): ?string
    {
        $referer = $request->headers->get('referer');
        if (! is_string($referer) || $referer === '') {
            return null;
        }

        $host = parse_url($referer, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = strtolower($host);

        return substr($host, 0, 200);
    }
}
