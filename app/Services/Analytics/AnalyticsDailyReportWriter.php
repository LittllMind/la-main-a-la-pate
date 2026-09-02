<?php

namespace App\Services\Analytics;

use Carbon\Carbon;

class AnalyticsDailyReportWriter
{
    private string $directory;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    public function write(Carbon $date, string $content): string
    {
        if ($this->directory !== '' && ! is_dir($this->directory)) {
            if (! @mkdir($this->directory, 0755, true) && ! is_dir($this->directory)) {
                throw new \RuntimeException("Impossible de créer le répertoire de rapports : {$this->directory}");
            }
        }

        if (! is_writable($this->directory)) {
            throw new \RuntimeException("Répertoire de rapports non inscriptible : {$this->directory}");
        }

        $path = $this->directory . '/' . $date->format('Y-m-d') . '_LMALP-ANALYTICS.md';

        $bytes = file_put_contents($path, $content, LOCK_EX);

        if ($bytes === false) {
            throw new \RuntimeException("Écriture impossible du rapport : {$path}");
        }

        return $path;
    }

    public function pathFor(Carbon $date): string
    {
        return $this->directory . '/' . $date->format('Y-m-d') . '_LMALP-ANALYTICS.md';
    }
}
