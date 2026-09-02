<?php

namespace App\Http\Middleware;

use App\Services\Analytics\AnalyticsRecorder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordAnalytics
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldFireAfterResponse($request, $response)) {
            return $response;
        }

        $recorder = new AnalyticsRecorder($request);
        $recorder->recordPageView($request);

        return $response;
    }

    private function shouldFireAfterResponse(Request $request, Response $response): bool
    {
        if (! $response->isSuccessful()) {
            return false;
        }

        return $response->headers->get('Content-Type') === null
            || str_contains((string) $response->headers->get('Content-Type'), 'text/html');
    }
}
