<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Services\Analytics\AnalyticsDailyReportWriter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AnalyticsDailyReportTest extends TestCase
{
    use RefreshDatabase;

    private string $reportsDir;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('analytics.enabled', true);
        Config::set('analytics.hmac_key', 'test-hmac-key');
        Config::set('analytics.excluded_environments', []);

        $this->reportsDir = storage_path('testing/analytics-daily');
        @mkdir($this->reportsDir, 0755, true);
        $this->recursivelyRemoveDirectory($this->reportsDir);

        Config::set('analytics.daily_report.directory', $this->reportsDir);
    }

    private function recursivelyRemoveDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        foreach (array_diff(scandir($path), ['.', '..']) as $file) {
            $full = $path . '/' . $file;
            is_dir($full) ? $this->recursivelyRemoveDirectory($full) : unlink($full);
        }
    }

    private function seedDate(Carbon $parisDay, int $visitors, int $viewsPerVisitor = 1, string $path = '/'): void
    {
        $base = $parisDay->copy()->timezone('Europe/Paris')->setTime(12, 0, 0)->timezone('UTC');

        for ($i = 1; $i <= $visitors; $i++) {
            for ($j = 0; $j < $viewsPerVisitor; $j++) {
                AnalyticsEvent::create([
                    'event_type' => 'page_view',
                    'visitor_key' => hash('sha256', "visitor-{$parisDay->toDateString()}-{$i}"),
                    'path' => $path,
                    'user_agent_family' => 'Chrome',
                    'device_family' => 'Desktop',
                    'created_at' => $base->copy()->addSeconds($j * 10),
                ]);
            }
        }
    }

    private function reportPath(string $date): string
    {
        return $this->reportsDir . '/' . $date . '_LMALP-ANALYTICS.md';
    }

    public function test_command_generates_report_for_yesterday_by_default(): void
    {
        $yesterday = today('Europe/Paris')->subDay();
        $this->seedDate($yesterday, 2, 2, '/seraphotheque');

        $this->artisan('analytics:daily-report')
            ->assertSuccessful()
            ->expectsOutputToContain('LMALP — Rapport audience');

        $path = $this->reportPath($yesterday->toDateString());
        $this->assertFileExists($path);
    }

    public function test_command_accepts_date_option(): void
    {
        $date = Carbon::create(2026, 9, 2, 12, 0, 0, 'Europe/Paris');
        $this->seedDate($date, 3, 1, '/');

        $this->artisan('analytics:daily-report', ['--date' => '2026-09-02'])
            ->assertSuccessful();

        $this->assertFileExists($this->reportPath('2026-09-02'));
    }

    public function test_report_date_filename_and_content_matches_measured_day(): void
    {
        $date = Carbon::create(2026, 9, 2, 8, 0, 0, 'Europe/Paris');
        $this->seedDate($date, 5, 1, '/');

        $this->artisan('analytics:daily-report', ['--date' => '2026-09-02'])->assertSuccessful();

        $path = $this->reportPath('2026-09-02');
        $this->assertFileExists($path);
        $this->assertStringContainsString('2 septembre 2026', file_get_contents($path));
    }

    public function test_command_idempotence_regenerates_identical_report(): void
    {
        $date = today('Europe/Paris')->subDay();
        $this->seedDate($date, 2, 2, '/seraphotheque');

        $this->artisan('analytics:daily-report')->assertSuccessful();
        $path = $this->reportPath($date->toDateString());
        $first = file_get_contents($path);

        sleep(1);

        $this->artisan('analytics:daily-report')->assertSuccessful();
        $second = file_get_contents($path);

        $normalize = function (string $content): string {
            return preg_replace('/^> Généré le .*$/m', '> Généré le [REDACTED]', $content) ?? $content;
        };

        $this->assertSame($normalize($first), $normalize($second));
    }

    public function test_report_includes_comparisons_with_previous_day(): void
    {
        $prev = today('Europe/Paris')->subDays(2);
        $day = today('Europe/Paris')->subDay();

        $this->seedDate($prev, 1, 1, '/');
        $this->seedDate($day, 4, 1, '/');

        $this->artisan('analytics:daily-report', ['--date' => $day->toDateString()])->assertSuccessful();

        $content = file_get_contents($this->reportPath($day->toDateString()));
        $this->assertStringContainsString('**Pages vues** : 4', $content);
        $this->assertStringContainsString('**Visiteurs uniques approximatifs du jour**', $content);
        $this->assertStringContainsString('+300 %', $content);
    }

    public function test_report_handles_zero_previous_day_without_percentage(): void
    {
        $day = today('Europe/Paris')->subDay();
        $this->seedDate($day, 3, 1, '/');

        $this->artisan('analytics:daily-report', ['--date' => $day->toDateString()])->assertSuccessful();

        $content = file_get_contents($this->reportPath($day->toDateString()));
        $this->assertStringNotContainsString('NaN', $content);
        $this->assertStringNotContainsString('Inf', $content);
        $this->assertStringNotContainsString('+300 %', $content);
    }

    public function test_report_handles_no_traffic_day(): void
    {
        $day = today('Europe/Paris')->subDay();

        $this->artisan('analytics:daily-report', ['--date' => $day->toDateString()])->assertSuccessful();

        $content = file_get_contents($this->reportPath($day->toDateString()));
        $this->assertStringContainsString('**Visiteurs uniques approximatifs du jour** : 0', $content);
        $this->assertStringContainsString('**Pages vues** : 0', $content);
    }

    public function test_report_handles_zero_previous_day_shows_new_or_dash(): void
    {
        $day = today('Europe/Paris')->subDay();
        $this->seedDate($day, 3, 1, '/');

        $this->artisan('analytics:daily-report', ['--date' => $day->toDateString()])->assertSuccessful();

        $content = file_get_contents($this->reportPath($day->toDateString()));
        $this->assertStringNotContainsString('NaN', $content);
        $this->assertStringNotContainsString('Inf', $content);
        $this->assertStringNotContainsString('+300 %', $content);
        $this->assertStringContainsString('(nouveau)', $content);
        $this->assertStringContainsString('**Visiteurs uniques approximatifs du jour** : 3', $content);
    }

    public function test_no_visitor_key_or_raw_data_in_markdown(): void
    {
        $day = today('Europe/Paris')->subDay();
        $this->seedDate($day, 1, 1, '/');

        $this->artisan('analytics:daily-report', ['--date' => $day->toDateString()])->assertSuccessful();

        $content = file_get_contents($this->reportPath($day->toDateString()));
        $this->assertStringNotContainsString('visitor-', $content);
        $this->assertStringNotContainsString('127.0.0.1', $content);
        $this->assertStringNotContainsString('Mozilla/5.0', $content);
    }

    public function test_writer_rejects_invalid_date_string(): void
    {
        $this->artisan('analytics:daily-report', ['--date' => 'not-a-date'])
            ->assertFailed();
    }

    public function test_scheduler_lists_daily_report_at_0800_paris(): void
    {
        \Illuminate\Support\Facades\Artisan::call('schedule:list');
        $output = \Illuminate\Support\Facades\Artisan::output();

        $this->assertStringContainsString('0 8 * * *', $output);
        $this->assertStringContainsString('analytics:daily-report', $output);
    }

    public function test_events_around_midnight_paris_are_assigned_to_correct_day(): void
    {
        $day = Carbon::parse('2026-01-15', 'Europe/Paris');

        // 23:55 le 15 janvier (Paris) = 22:55 UTC — doit compter pour le 15.
        AnalyticsEvent::create([
            'event_type' => 'page_view',
            'visitor_key' => hash('sha256', 'late-visitor'),
            'path' => '/',
            'user_agent_family' => 'Chrome',
            'device_family' => 'Desktop',
            'created_at' => Carbon::parse('2026-01-15 23:55:00', 'Europe/Paris')->timezone('UTC'),
        ]);

        // 12:00 le 16 janvier (Paris) = 11:00 UTC le lendemain — ne doit PAS compter pour le 15.
        AnalyticsEvent::create([
            'event_type' => 'page_view',
            'visitor_key' => hash('sha256', 'next-day-visitor'),
            'path' => '/',
            'user_agent_family' => 'Chrome',
            'device_family' => 'Desktop',
            'created_at' => Carbon::parse('2026-01-16 12:00:00', 'Europe/Paris')->timezone('UTC'),
        ]);

        $this->artisan('analytics:daily-report', ['--date' => '2026-01-15'])->assertSuccessful();

        $content = file_get_contents($this->reportPath('2026-01-15'));
        $this->assertStringContainsString('**Visiteurs uniques approximatifs du jour** : 1', $content);
        $this->assertStringContainsString('**Pages vues** : 1', $content);
    }
}
