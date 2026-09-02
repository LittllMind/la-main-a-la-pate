<?php

namespace App\Services\Analytics;

use Carbon\Carbon;

class AnalyticsReportFormatter
{
    public function format(array $data): string
    {
        Carbon::setLocale('fr');
        \setlocale(LC_TIME, 'fr_FR.utf8', 'fr_FR', 'fr');

        $blocks = [];

        $blocks[] = '# LMALP — Rapport audience — ' . $data['measured_at'];
        $blocks[] = '> Généré le ' . now('Europe/Paris')->isoFormat('D MMMM YYYY à HH:mm') . ' (Europe/Paris)';
        $blocks[] = '---';

        $blocks[] = $this->section('Audience', [
            $this->kpi('Visiteurs uniques approximatifs du jour', $data['visitors'], $data['previous']['visitors']),
            $this->line('Visites approximatives', $data['visits']),
            $this->kpi('Pages vues', $data['page_views'], $data['previous']['page_views']),
        ]);

        $blocks[] = $this->section('Séraphothèque', [
            $this->line('Landing', $data['seraphotheque_landing'] . ' vue' . $this->plural($data['seraphotheque_landing'])),
            $this->line('Dossier', $data['seraphotheque_dossier'] . ' vue' . $this->plural($data['seraphotheque_dossier'])),
            $this->line('Documents consultés', $data['document_views']),
            $this->line('Téléchargements explicites', $data['document_downloads']),
        ]);

        $blocks[] = $this->section('Top pages', $this->rankedList($data['top_pages'], function (array $row): string {
            return $row['path'] . ' : ' . $row['total'] . ' vue' . $this->plural($row['total']);
        }));

        $blocks[] = $this->section('Documents les plus consultés', $this->rankedList($data['top_document_views'], function (array $row): string {
            return $this->documentLabel($row) . ' : ' . $row['total'] . ' consultation' . $this->plural($row['total']);
        }));

        $blocks[] = $this->section('Documents les plus téléchargés', $this->rankedList($data['top_document_downloads'], function (array $row): string {
            return $this->documentLabel($row) . ' : ' . $row['total'] . ' téléchargement' . $this->plural($row['total']);
        }));

        $blocks[] = $this->section('Origines', $this->rankedList($data['referrers'], function (array $row): string {
            return ($row['host'] ?? 'Inconnu') . ' : ' . $row['total'];
        }));

        $blocks[] = $this->section('Appareils', $this->rankedList($data['devices'], function (array $row): string {
            return ($row['label'] ?? 'Inconnu') . ' : ' . $row['total'];
        }));

        $blocks[] = $this->section('Navigateurs', $this->rankedList($data['browsers'], function (array $row): string {
            return ($row['label'] ?? 'Inconnu') . ' : ' . $row['total'];
        }));

        $blocks[] = '---';
        $blocks[] = '*Rapport privacy-first : aucune donnée individuelle, adresse IP ou user-agent brut ne figure dans ce fichier. Les visiteurs sont agrégés via des clés pseudonymes journalières.*';

        return $this->joinBlocks($blocks);
    }

    private function section(string $title, array $lines): string
    {
        if ($lines === []) {
            return '## ' . $title . "\n\n_Aucune donnée._";
        }

        return '## ' . $title . "\n\n" . implode("\n", $lines);
    }

    private function rankedList(array $rows, callable $formatter): array
    {
        if ($rows === []) {
            return ['_Aucune donnée._'];
        }

        $result = [];
        $rank = 1;
        foreach ($rows as $row) {
            $result[] = $rank . '. ' . $formatter($row);
            $rank++;
        }

        return $result;
    }

    private function line(string $label, int|string $value): string
    {
        return '- **' . $label . '** : ' . $value;
    }

    private function kpi(string $label, int $value, int $previous): string
    {
        return $this->line($label, $value . $this->variation($value, $previous));
    }

    private function documentLabel(array $row): string
    {
        if (! empty($row['title'])) {
            return $row['title'];
        }

        if (! empty($row['document_key'])) {
            return $row['document_key'];
        }

        return 'Document inconnu';
    }

    private function variation(int $current, int $previous): string
    {
        if ($current === 0 && $previous === 0) {
            return '';
        }

        if ($previous <= 0) {
            return ' (nouveau)';
        }

        $delta = $current - $previous;
        $percent = (int) round(($delta / $previous) * 100);
        $sign = $percent >= 0 ? '+' : '';

        return ' (' . $sign . $percent . ' %)';
    }

    private function plural(int $count): string
    {
        return $count > 1 ? 's' : '';
    }

    private function joinBlocks(array $blocks): string
    {
        $normalized = array_map(function (string|array $block): string {
            return is_array($block) ? implode("\n", $block) : $block;
        }, $blocks);

        return implode("\n\n", $normalized) . "\n";
    }
}
