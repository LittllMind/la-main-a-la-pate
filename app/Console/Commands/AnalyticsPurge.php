<?php

namespace App\Console\Commands;

use App\Models\AnalyticsEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class AnalyticsPurge extends Command
{
    protected $signature = 'analytics:purge';
    protected $description = 'Purge les événements analytics bruts plus anciens que la période de rétention.';

    public function handle(): int
    {
        $days = (int) Config::get('analytics.retention_days', 90);
        $cutoff = now()->subDays($days);

        $count = AnalyticsEvent::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("{$count} événements analytics antérieurs à {$cutoff->toDateString()} supprimés.");

        return self::SUCCESS;
    }
}
