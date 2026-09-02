<?php

namespace App\Console\Commands;

use App\Services\Analytics\AnalyticsDailyReportWriter;
use App\Services\Analytics\AnalyticsReportFormatter;
use App\Services\Analytics\AnalyticsReportGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class AnalyticsDailyReport extends Command
{
    protected $signature = 'analytics:daily-report {--date= : Date mesurée au format YYYY-MM-DD (défaut : hier)} {--force : Régénère le fichier existant}';

    protected $description = 'Génère le rapport analytics quotidien (J-1) au format Markdown.';

    public function handle(AnalyticsReportGenerator $generator, AnalyticsReportFormatter $formatter): int
    {
        $date = $this->resolveDate();

        if (! $date instanceof Carbon) {
            $this->error('La date doit être au format YYYY-MM-DD.');

            return self::FAILURE;
        }

        $data = $generator->generate($date);
        $markdown = $formatter->format($data);

        $directory = (string) Config::get('analytics.daily_report.directory', $this->defaultDirectory());
        $writer = new AnalyticsDailyReportWriter($directory);

        try {
            $path = $writer->write($date, $markdown);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('LMALP — Rapport audience — ' . $data['measured_at']);
        $this->info('Fichier : ' . $path);

        // Aperçu terminal lisible (limite les lignes pour rester concis).
        $preview = implode("\n", array_slice(explode("\n", $markdown), 0, 35));
        $this->line('');
        $this->info($preview);

        return self::SUCCESS;
    }

    private function resolveDate(): ?Carbon
    {
        $input = $this->option('date');

        if ($input === null || $input === '') {
            return today('Europe/Paris')->subDay();
        }

        try {
            return Carbon::createFromFormat('Y-m-d', (string) $input, 'Europe/Paris')->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function defaultDirectory(): string
    {
        return (string) Config::get('analytics.daily_report.directory', storage_path('app/private/analytics/daily'));
    }
}
