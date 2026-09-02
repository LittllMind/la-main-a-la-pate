<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Hash;

class AnalyticsVisitorResolver
{
    private string $key;

    public function __construct(string $hmacKey)
    {
        $this->key = $hmacKey;
    }

    /**
     * Résolue un visiteur en un identifiant pseudonyme journalier.
     * L'IP et le User-Agent bruts ne sont pas conservés.
     */
    public function resolve(string $ip, string $userAgent): array
    {
        $today = now()->toDateString();
        $payload = $ip . "\n" . $userAgent . "\n" . $today;

        $visitorKey = hash_hmac('sha256', $payload, $this->key);

        return [
            'key' => $visitorKey,
            'family' => $this->family($userAgent),
            'device' => $this->device($userAgent),
        ];
    }

    private function family(string $ua): string
    {
        $ua = strtolower($ua);

        if (str_contains($ua, 'firefox')) return 'Firefox';
        if (str_contains($ua, 'edge') || str_contains($ua, 'edg/')) return 'Edge';
        if (str_contains($ua, 'chrome') || str_contains($ua, 'chromium')) return 'Chrome';
        if (str_contains($ua, 'safari')) return 'Safari';

        if ($this->isBot($ua)) return 'Bot';

        return 'Other';
    }

    private function device(string $ua): string
    {
        $ua = strtolower($ua);

        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad') || preg_match('/android(?!.*mobile)/', $ua)) {
            return 'Tablet';
        }

        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    public function isBot(string $ua): bool
    {
        $ua = strtolower($ua);
        $botSignatures = [
            'bot', 'crawler', 'spider', 'slurp', 'googlebot', 'bingbot', 'yandexbot',
            'duckduckbot', 'baiduspider', 'facebookexternalhit', 'linkedinbot',
            'twitterbot', 'whatsapp', 'preview', 'uptime', 'monitor', 'health',
            'archive.org', 'ahrefsbot', 'semrushbot', 'mj12bot',
        ];

        foreach ($botSignatures as $sig) {
            if (str_contains($ua, $sig)) {
                return true;
            }
        }

        return false;
    }
}
