<?php

namespace Tests\Unit;

use App\Services\Analytics\AnalyticsDailyReportWriter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AnalyticsDailyReportWriterTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = storage_path('testing/analytics-unit');
        $this->cleanDirectory($this->baseDir);
        Config::set('analytics.daily_report.directory', $this->baseDir);
    }

    private function cleanDirectory(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0755, true);
            return;
        }

        foreach (array_diff(scandir($path), ['.', '..']) as $file) {
            $full = $path . '/' . $file;
            is_dir($full) ? $this->cleanDirectory($full) : unlink($full);
        }
    }

    public function test_creates_directory_when_missing(): void
    {
        $writer = new AnalyticsDailyReportWriter($this->baseDir . '/nested/daily');
        $writer->write(Carbon::parse('2026-09-02'), "test\n");

        $this->assertFileExists($this->baseDir . '/nested/daily/2026-09-02_LMALP-ANALYTICS.md');
    }

    public function test_overwrites_existing_report_on_regeneration(): void
    {
        $writer = new AnalyticsDailyReportWriter($this->baseDir);
        $date = Carbon::parse('2026-09-02');

        $writer->write($date, "version 1\n");
        $writer->write($date, "version 2\n");

        $this->assertSame("version 2\n", file_get_contents($this->baseDir . '/2026-09-02_LMALP-ANALYTICS.md'));
    }

    public function test_generates_expected_filename(): void
    {
        $writer = new AnalyticsDailyReportWriter($this->baseDir);
        $writer->write(Carbon::parse('2026-09-02'), "content\n");

        $this->assertFileExists($this->baseDir . '/2026-09-02_LMALP-ANALYTICS.md');
    }

    public function test_throws_when_directory_not_writable(): void
    {
        $readOnlyDir = $this->baseDir . '/readonly';
        @mkdir($readOnlyDir, 0555, true);

        $writer = new AnalyticsDailyReportWriter($readOnlyDir);

        $this->expectException(\RuntimeException::class);
        $writer->write(Carbon::parse('2026-09-02'), "content\n");
    }

    public function test_default_directory_is_private_storage(): void
    {
        Config::set(
            'analytics.daily_report.directory',
            env('ANALYTICS_DAILY_REPORT_DIRECTORY', storage_path('app/private/analytics/daily'))
        );

        $dir = (string) Config::get('analytics.daily_report.directory');

        $this->assertStringContainsString('app/private/analytics/daily', str_replace('\\', '/', $dir));
        $this->assertStringNotContainsString('/public/', $dir);
        $this->assertStringNotContainsString('/public_html/', $dir);
        $this->assertStringNotContainsString('storage/app/public', $dir);
    }
}
