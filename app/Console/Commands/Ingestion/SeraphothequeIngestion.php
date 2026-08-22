<?php

namespace App\Console\Commands\Ingestion;

use App\Models\Category;
use App\Models\RepresentationType;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\SubjectDocument;
use App\Models\SubjectVersion;
use App\Models\User;
use App\Models\VisibilityLevel;
use App\Services\DocumentStorageService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Ingestion locale du Subject Séraphothèque selon le manifest PUBLIC-V1.
 *
 * Le manifest 99-MANIFEST/public-v1.csv est l'autorité pour l'identité
 * documentaire (doc_id), la source_reference, l'audience et les assets.
 * Aucun catalogue hardcodé n'est utilisé pour PUBLIC-V1.
 */
class SeraphothequeIngestion extends Command
{
    protected $signature = 'app:seraphotheque-ingestion {--dry-run} {--force} {--pack-path=} {--user-id=1}';

    protected $description = 'Ingère le Subject Séraphothèque et ses documents selon le manifest PUBLIC-V1.';

    private string $packPath;
    private DocumentStorageService $storage;

    public function handle(): int
    {
        $this->storage = app(DocumentStorageService::class);
        $userId = (int) $this->option('user-id', 1);

        if ($this->option('pack-path')) {
            $this->packPath = $this->option('pack-path');
        } else {
            $this->error("L'option --pack-path est obligatoire.");

            return self::FAILURE;
        }

        if (! is_dir($this->packPath)) {
            $this->error("Pack introuvable : {$this->packPath}");

            return self::FAILURE;
        }

        try {
            $manifest = $this->loadManifest();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $docIds = collect($manifest)->pluck('doc_id')->all();

        if ($this->option('dry-run')) {
            $this->info('[DRY-RUN] Manifest résolu du pack.');
            foreach ($manifest as $row) {
                $hasAsset = ! empty($row['asset_path']);
                $this->line('---');
                $this->line("doc_id          : {$row['doc_id']}");
                $this->line("source_reference: {$row['source_reference']}");
                $this->line("audience        : {$row['audience']}");
                $this->line('asset_path      : ' . ($hasAsset ? $row['asset_path'] : '[NO ASSET]'));
                $this->line('asset_sha256    : ' . ($hasAsset ? $row['asset_sha256'] : '[NO ASSET]'));
                $this->line("action          : {$row['proposed_action']}");
            }
            $this->line('---');
            $this->info('Lignes : ' . count($manifest) . ' ; PUBLIC : ' . count(array_filter($manifest, fn ($r) => $r['audience'] === VisibilityLevel::Public->value)) . ' ; CITIZEN : ' . count(array_filter($manifest, fn ($r) => $r['audience'] === VisibilityLevel::Citizen->value)) . ' ; avec asset : ' . count(array_filter($manifest, fn ($r) => ! empty($r['asset_path']))));

            return self::SUCCESS;
        }

        $author = User::find($userId);
        $category = Category::find(10);
        $subCategory = SubCategory::find(14);

        if (! $author || ! $category || ! $subCategory) {
            $this->error("Prérequis manquants : user_id={$userId}, category_id=10, sub_category_id=14.");

            return self::FAILURE;
        }

        $publicBody = $this->assemblePublicBody();
        $citizenBody = $this->assembleCitizenBody($publicBody);
        $workingBody = $this->assembleWorkingBody($publicBody);

        $existingSubject = Subject::where('slug', 'seraphotheque-situation-2026')->first();

        $subject = Subject::updateOrCreate(
            ['slug' => 'seraphotheque-situation-2026'],
            [
                'user_id' => $userId,
                'category_id' => 10,
                'sub_category_id' => 14,
                'theme' => $category->name,
                'title' => 'La Séraphothèque — Comprendre la situation',
                'body' => $workingBody,
                'citizen_body' => $citizenBody,
                'public_body' => $publicBody,
                'status' => 'draft',
                'citizen_status' => 'draft',
                'public_status' => 'draft',
            ]
        );

        $this->info("Subject créé/mis à jour : ID {$subject->id}, slug {$subject->slug}");

        // Snapshot des trois représentations uniquement si une d'elles a changé
        // ou si c'est la première création par le pipeline.
        $bodyChanged = ! $existingSubject || $existingSubject->body !== $workingBody;
        $citizenChanged = ! $existingSubject || $existingSubject->citizen_body !== $citizenBody;
        $publicChanged = ! $existingSubject || $existingSubject->public_body !== $publicBody;

        if ($bodyChanged || $citizenChanged || $publicChanged) {
            SubjectVersion::create([
                'subject_id' => $subject->id,
                'user_id' => $userId,
                'body' => $workingBody,
                'citizen_body' => $citizenBody,
                'public_body' => $publicBody,
                'change_summary' => 'Ingestion du pack Séraphothèque',
            ]);

            $this->info('SubjectVersion créée : snapshot des trois représentations.');
        } else {
            $this->info('Aucune SubjectVersion créée : représentations inchangées.');
        }

        // Suppression des documents pipeline obsolètes uniquement avec --force
        if ($this->option('force')) {
            $this->pruneStalePipelineDocuments($subject, $docIds);
        }

        $position = 0;
        $createdCount = 0;

        foreach ($manifest as $row) {
            // Ligne sans asset : pas de SubjectDocument binaire ; conservée pour mapping éditorial futur
            if (empty($row['asset_path'])) {
                $this->info("Document sans asset : {$row['doc_id']} — aucun binaire créé.");
                continue;
            }

            $sourcePath = $row['resolved_asset_path'];
            $currentSha256 = hash_file('sha256', $sourcePath);
            $sourceReference = $row['source_reference'];

            $existingDoc = SubjectDocument::where('subject_id', $subject->id)
                ->where('source_reference', $sourceReference)
                ->first();

            $shouldStore = ! $existingDoc || $existingDoc->source_sha256 !== $currentSha256;

            if ($shouldStore) {
                $newPath = $this->storage->storeEncrypted(
                    $subject->id,
                    $sourcePath,
                    basename($sourcePath)
                );
            }

            if ($existingDoc) {
                if ($shouldStore) {
                    $oldPath = $existingDoc->path;

                    try {
                        $existingDoc->update([
                            'filename' => basename($sourcePath),
                            'stored_filename' => basename($newPath),
                            'path' => $newPath,
                            'disk' => 'documents',
                            'mime_type' => mime_content_type($sourcePath) ?: 'application/octet-stream',
                            'size' => filesize($sourcePath),
                            'title' => $row['titre'],
                            'document_date' => $row['date'],
                            'document_type' => $row['type'],
                            'author' => null,
                            'recipient' => null,
                            'representation_type' => RepresentationType::Original,
                            'redacted' => false,
                            'visibility' => $row['audience'],
                            'establishes' => null,
                            'limitations' => null,
                            'position' => ++$position,
                            'source_sha256' => $currentSha256,
                        ]);
                    } catch (\Exception $e) {
                        // En cas d'échec DB, nettoyer le nouveau fichier pour éviter l'orphelin
                        if ($this->storage->exists($newPath)) {
                            $this->storage->delete($newPath);
                        }
                        throw $e;
                    }

                    // Supprimer l'ancien fichier seulement après succès de la mise à jour DB
                    if ($this->storage->exists($oldPath)) {
                        $this->storage->delete($oldPath);
                    }

                    $path = $newPath;
                } else {
                    // Contenu identique : pas de nouveau fichier, mettre à jour métadonnées seulement
                    $existingDoc->update([
                        'title' => $row['titre'],
                        'document_date' => $row['date'],
                        'document_type' => $row['type'],
                        'representation_type' => RepresentationType::Original,
                        'position' => ++$position,
                    ]);

                    $path = $existingDoc->path;
                }
            } else {
                $doc = SubjectDocument::create([
                    'subject_id' => $subject->id,
                    'filename' => basename($sourcePath),
                    'stored_filename' => basename($newPath),
                    'path' => $newPath,
                    'disk' => 'documents',
                    'mime_type' => mime_content_type($sourcePath) ?: 'application/octet-stream',
                    'size' => filesize($sourcePath),
                    'title' => $row['titre'],
                    'document_date' => $row['date'],
                    'document_type' => $row['type'],
                    'author' => null,
                    'recipient' => null,
                    'source_reference' => $sourceReference,
                    'representation_type' => RepresentationType::Original,
                    'redacted' => false,
                    'visibility' => $row['audience'],
                    'establishes' => null,
                    'limitations' => null,
                    'position' => ++$position,
                    'source_sha256' => $currentSha256,
                ]);

                $path = $newPath;
            }

            $createdCount++;
            $this->info("Document attaché : {$row['titre']} ({$row['audience']})");
        }

        $this->info("Ingestion terminée : {$createdCount} document(s) attaché(s).");

        return self::SUCCESS;
    }

    /**
     * Charge, normalise et valide le manifest du pack.
     * Fail-closed : une anomalie lève une RuntimeException avant toute mutation.
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadManifest(): array
    {
        $manifestPath = $this->packPath . '/99-MANIFEST/public-v1.csv';

        if (! is_file($manifestPath)) {
            throw new RuntimeException("Manifest introuvable : {$manifestPath}");
        }

        $handle = fopen($manifestPath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Impossible de lire le manifest : {$manifestPath}");
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            throw new RuntimeException('Manifest vide ou illisible.');
        }

        $headers = array_map(fn ($h) => trim(strtolower((string) $h)), $headers);
        $required = ['doc_id', 'audience', 'source_reference', 'asset_path', 'asset_sha256'];
        foreach ($required as $col) {
            if (! in_array($col, $headers, true)) {
                fclose($handle);
                throw new RuntimeException("Colonne obligatoire manquante : {$col}");
            }
        }
        $colIndex = array_flip($headers);

        $rows = [];
        $line = 2;
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) !== count($headers)) {
                fclose($handle);
                throw new RuntimeException("Ligne {$line} : nombre de colonnes invalide.");
            }

            $row = [];
            foreach ($colIndex as $col => $idx) {
                $row[$col] = trim($data[$idx] ?? '');
            }

            $rows[] = $row;
            $line++;
        }
        fclose($handle);

        return $this->validateManifest($rows);
    }

    /**
     * Valide le manifest et résout les assets.
     *
     * @param array<int, array<string, string>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function validateManifest(array $rows): array
    {
        if (count($rows) === 0) {
            throw new RuntimeException('Manifest sans entrée documentaire.');
        }

        $allowedAudiences = [VisibilityLevel::Public->value, VisibilityLevel::Citizen->value];
        $seenDocIds = [];
        $validated = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $docId = $row['doc_id'];

            if ($docId === '') {
                throw new RuntimeException("Ligne {$line} : doc_id vide.");
            }

            if (isset($seenDocIds[$docId])) {
                throw new RuntimeException("Ligne {$line} : doc_id dupliqué '{$docId}'.");
            }
            $seenDocIds[$docId] = true;

            $expectedRef = 'seraphotheque-pack:' . $docId;
            if ($row['source_reference'] !== $expectedRef) {
                throw new RuntimeException("Ligne {$line} : source_reference attendu '{$expectedRef}', trouvé '{$row['source_reference']}'.");
            }

            $audience = strtolower($row['audience']);
            if (! in_array($audience, $allowedAudiences, true)) {
                throw new RuntimeException("Ligne {$line} : audience invalide '{$row['audience']}' (autorisé : public, citizen).");
            }
            $visibility = VisibilityLevel::from($audience)->value;

            $hasAssetPath = $row['asset_path'] !== '';
            $hasAssetHash = $row['asset_sha256'] !== '';

            if ($hasAssetPath && ! $hasAssetHash) {
                throw new RuntimeException("Ligne {$line} : asset_sha256 manquant pour asset_path '{$row['asset_path']}'.");
            }
            if (! $hasAssetPath && $hasAssetHash) {
                throw new RuntimeException("Ligne {$line} : asset_sha256 présent sans asset_path.");
            }

            $resolvedPath = null;
            if ($hasAssetPath) {
                $resolvedPath = $this->resolveAssetPath($row['asset_path']);
                if (! is_file($resolvedPath)) {
                    throw new RuntimeException("Ligne {$line} : asset introuvable '{$row['asset_path']}'.");
                }

                $realHash = hash_file('sha256', $resolvedPath);
                if (! hash_equals($row['asset_sha256'], $realHash)) {
                    throw new RuntimeException("Ligne {$line} : hash asset invalide pour '{$row['asset_path']}' (attendu {$row['asset_sha256']}, trouvé {$realHash}).");
                }
            }

            $proposedAction = $this->proposedManifestAction($docId);

            $validated[] = [
                'doc_id' => $docId,
                'titre' => $row['titre'] ?? '',
                'date' => $this->parseDocumentDate($row['date'] ?? null),
                'type' => $row['type'] ?? 'document',
                'audience' => $visibility,
                'source_reference' => $expectedRef,
                'asset_path' => $row['asset_path'],
                'asset_sha256' => $row['asset_sha256'],
                'resolved_asset_path' => $resolvedPath,
                'proposed_action' => $proposedAction,
            ];
        }

        return $validated;
    }

    private function resolveAssetPath(string $assetPath): string
    {
        // Interdit : chemin absolu, remontée de répertoire, symlink hors pack
        if (str_starts_with($assetPath, '/')) {
            throw new RuntimeException("asset_path absolu interdit : {$assetPath}");
        }
        if (str_contains($assetPath, '..')) {
            throw new RuntimeException("asset_path interdit (contient '..') : {$assetPath}");
        }

        $resolvedPack = realpath($this->packPath);
        if ($resolvedPack === false) {
            throw new RuntimeException("Impossible de résoudre la racine du pack.");
        }

        $resolved = realpath($resolvedPack . '/' . $assetPath);
        if ($resolved === false) {
            throw new RuntimeException("Impossible de résoudre l'asset : {$assetPath}");
        }

        if (! str_starts_with($resolved, $resolvedPack . DIRECTORY_SEPARATOR) && $resolved !== $resolvedPack) {
            throw new RuntimeException("asset_path sort du pack : {$assetPath}");
        }

        return $resolved;
    }

    private function parseDocumentDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Formats acceptés : YYYY ou YYYY-MM-DD
        try {
            if (preg_match('/^\d{4}$/', $value)) {
                return Carbon::createFromDate((int) $value, 1, 1)->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    private function proposedManifestAction(string $docId): string
    {
        $subject = Subject::where('slug', 'seraphotheque-situation-2026')->first();
        if (! $subject) {
            return 'CREATE';
        }

        $existing = SubjectDocument::where('subject_id', $subject->id)
            ->where('source_reference', 'seraphotheque-pack:' . $docId)
            ->first();

        if (! $existing) {
            return 'CREATE';
        }

        // Simplification dry-run : on ne compare pas le hash ici (validation complète faite avant)
        return 'UNCHANGED';
    }

    /**
     * Supprime les documents du namespace seraphotheque-pack dont le doc_id
     * n'est plus présent dans le manifest courant. Les documents manuels sont
     * toujours préservés.
     */
    private function pruneStalePipelineDocuments(Subject $subject, array $currentDocIds): void
    {
        $namespacePrefix = 'seraphotheque-pack:';

        $subject->documents()
            ->where('source_reference', 'like', $namespacePrefix . '%')
            ->get()
            ->each(function ($doc) use ($currentDocIds, $namespacePrefix) {
                $docId = Str::after($doc->source_reference, $namespacePrefix);
                if (! in_array($docId, $currentDocIds, true)) {
                    if ($this->storage->exists($doc->path)) {
                        $this->storage->delete($doc->path);
                    }
                    $doc->delete();
                    $this->warn("Document pipeline obsolète supprimé : {$doc->source_reference}");
                }
            });
    }

    private function assemblePublicBody(): string
    {
        $index = $this->readPackFile('01-SUJET/index.md');
        $index = $this->stripFrontMatter($index);
        $index = $this->stripLeadingH1($index);
        $index = $this->normalizeNumberedHeadings($index);
        $sections = $this->splitIndexSections($index);

        $chronologie = $this->readPackFile('02-CHRONOLOGIE/chronologie.md');
        $chronologie = $this->stripFrontMatter($chronologie);
        $chronologie = $this->cleanChronologie($chronologie);

        $questions = $this->readPackFile('05-QUESTIONS-OUVERTES/questions-ouvertes.md');
        $questions = $this->stripFrontMatter($questions);
        $questions = $this->stripLeadingH1($questions);
        $questions = $this->wrapSection($questions, 'Questions ouvertes', 'questions-ouvertes');

        $documentsSection = $this->buildDocumentsSection($sections);

        $sources = $this->readPackFile('06-SOURCES/index.md');
        $sources = $this->stripFrontMatter($sources);
        $sources = $this->stripLeadingH1($sources);
        $sources = $this->wrapSection($sources, 'Lire les sources', 'lire-les-sources');

        $positions = $this->combineSections([
            $sections[4] ?? ['heading' => '', 'content' => ''],
            $sections[6] ?? ['heading' => '', 'content' => ''],
        ]);

        $body = "# La Séraphothèque — Situation du local communal\n\n"
            . "**Dossier documentaire en cours d’enrichissement — version du 21 août 2026.**\n\n"
            . $this->wrapSection($sections[1]['content'] ?? '', 'Comprendre en une minute', 'comprendre', $sections[1]['heading'] ?? null) . "\n\n---\n\n"
            . $this->wrapSection($sections[2]['content'] ?? '', 'Les principaux enjeux', 'enjeux', $sections[2]['heading'] ?? null) . "\n\n---\n\n"
            . $this->wrapSection($sections[3]['content'] ?? '', 'Ce qui change en 2026', 'changements-2026', $sections[3]['heading'] ?? null) . "\n\n---\n\n"
            . $chronologie . "\n\n---\n\n"
            . $this->wrapSection($positions, 'Positions des acteurs', 'positions') . "\n\n---\n\n"
            . $this->wrapSection($sections[5]['content'] ?? '', 'Principaux points de désaccord', 'desaccords', $sections[5]['heading'] ?? null) . "\n\n---\n\n"
            . $questions . "\n\n---\n\n"
            . $documentsSection . "\n\n---\n\n"
            . $sources;

        return $this->compactWhitespace($body);
    }

    private function assembleCitizenBody(string $publicBody): string
    {
        // Aucun texte éditorial Citizen distinct validé dans PUBLIC-V1.
        return $publicBody;
    }

    private function assembleWorkingBody(string $publicBody): string
    {
        // Aucun texte éditorial Working distinct validé dans PUBLIC-V1.
        return $publicBody;
    }

    private function wrapSection(string $content, string $title, string $anchor, ?string $originalHeading = null): string
    {
        $content = $this->rewriteNavigationLinks($content);
        $content = $this->stripTrailingHorizontalRules($content);

        $heading = '';
        if ($originalHeading !== null && $originalHeading !== '' && $originalHeading !== $title) {
            $heading = "### {$originalHeading}\n\n";
        }

        return "## {$title} {#{$anchor}}\n\n" . $heading . trim($content);
    }

    private function combineSections(array $sections): string
    {
        $parts = [];
        foreach ($sections as $section) {
            if (empty($section['content'])) {
                continue;
            }
            $heading = trim($section['heading'] ?? '');
            $text = trim($section['content']);
            if ($heading !== '') {
                $parts[] = "### {$heading}\n\n{$text}";
            } else {
                $parts[] = $text;
            }
        }

        return $this->rewriteNavigationLinks(implode("\n\n---\n\n", $parts));
    }

    /**
     * Normalise les titres de section numérotées pour le parser interne.
     * Accepte aussi bien `# 1. Titre` que `## 1. Titre`.
     */
    private function normalizeNumberedHeadings(string $text): string
    {
        return preg_replace('/^##?\s+(\d+\.\s+.*)$/m', '# $1', $text) ?? $text;
    }

    /**
     * Lit les sections numérotées de 01-SUJET/index.md.
     * Renvoie un tableau indexé par numéro de section.
     *
     * @return array<int, array{heading: string, content: string}>
     */
    private function splitIndexSections(string $index): array
    {
        preg_match_all(
            '/^#\s+(\d+)\.\s+([^\n]+)\n((?:(?!^#\s+\d+\.).)*)/ms',
            $index,
            $matches,
            PREG_SET_ORDER
        );

        $sections = [];
        foreach ($matches as $m) {
            $sections[(int) $m[1]] = [
                'heading' => trim($m[2]),
                'content' => trim($m[3]),
            ];
        }

        return $sections;
    }

    /**
     * Construit la section #documents à partir des §7 (demandes) et §8 (solutions)
     * du sujet, des fiches documentaires et de la légende issue du README.
     */
    private function buildDocumentsSection(array $sections): string
    {
        $parts = ["## Documents et fiches documentaires {#documents}"];

        $demandes = $sections[7]['content'] ?? '';
        $solutions = $sections[8]['content'] ?? '';

        if ($demandes !== '') {
            $demandes = $this->convertNumberedHeadingToH3($demandes);
            $parts[] = $this->rewriteNavigationLinks($demandes);
        }

        if ($solutions !== '') {
            $solutions = $this->convertNumberedHeadingToH3($solutions);
            $parts[] = $this->rewriteNavigationLinks($solutions);
        }

        $fiches = $this->loadFiches();
        if ($fiches !== '') {
            $parts[] = $fiches;
        }

        $legend = $this->doctrineLegend();
        if ($legend !== '') {
            $parts[] = $legend;
        }

        return implode("\n\n", $parts);
    }

    private function loadFiches(): string
    {
        $files = glob($this->packPath . '/04-FICHES/*.md');
        if ($files === false || empty($files)) {
            return '';
        }

        sort($files);

        $parts = [];
        foreach ($files as $file) {
            $text = file_get_contents($file);
            if ($text === false) {
                continue;
            }
            $text = $this->stripFrontMatter($text);
            // Transformer le H1 de fiche en H3 sous la section #documents
            $text = preg_replace('/^#\s+(.+)$/m', '### $1', $text);
            $parts[] = trim($this->rewriteNavigationLinks($text));
        }

        return empty($parts) ? '' : implode("\n\n", $parts);
    }

    private function doctrineLegend(): string
    {
        $readme = $this->readPackFile('00-LIRE-DABORD.md');
        $readme = $this->stripFrontMatter($readme);

        if (! preg_match('/##\s+Doctrine\s*\n(.*?)(?=\n##|\z)/s', $readme, $m)) {
            return "Légende : chaque assertion est classée selon son statut documentaire.";
        }

        $doctrine = trim($m[1]);
        $doctrine = str_replace(
            ['**Fait documenté**', '**Position d\'acteur**', '**Question ouverte**'],
            ['**FAIT DOCUMENTÉ**', '**POSITION / DÉCLARATION**', '**QUESTION OUVERTE**'],
            $doctrine
        );

        // Ajout de la catégorie SOURCE, provenant de l'index des sources.
        return "### Légende documentaire\n\n" . $doctrine . "\n- **SOURCE** : référencée dans l'index des sources.";
    }

    private function stripFrontMatter(string $text): string
    {
        return preg_replace('/^---\s*\n.*?\n---\s*\n/s', '', $text) ?? $text;
    }

    private function convertNumberedHeadingToH3(string $text): string
    {
        return preg_replace('/^#\s+\d+\.\s+([^\n]+)/m', '### $1', $text) ?? $text;
    }

    private function stripTrailingHorizontalRules(string $text): string
    {
        return preg_replace('/\n*---\s*\n*$/m', '', $text) ?? $text;
    }

    private function cleanChronologie(string $text): string
    {
        $text = preg_replace('/^\s*#\s+Chronologie.*?\n/m', "## Chronologie {#chronologie}\n", $text);

        return trim($text);
    }

    private function stripLeadingH1(string $text): string
    {
        return trim(preg_replace('/^\s*#\s+[^\n]+\n*/u', '', $text) ?? $text);
    }

    private function addAnchorId(string $text, string $id): string
    {
        // Supprimer les headings parasites de nom de fichier
        $text = preg_replace('/^\s*#\s*`?[^`]+?\.md`?\s*\n*/m', '', $text);

        // Ajouter l'ancre au premier heading restant
        $text = preg_replace('/^(\s*##?\s+[^#\n{]+)/m', "\\1 {#{$id}}", $text, 1);

        return trim($text);
    }

    private function rewriteNavigationLinks(string $text): string
    {
        $text = preg_replace('/→\s*\*\*Voir la chronologie( complète)?\*\*/u', '→ [Voir la chronologie$1](#chronologie)', $text);
        $text = preg_replace('/→\s*\*\*Voir les documents\*\*/u', '→ [Voir les documents](#documents)', $text);
        $text = preg_replace('/→\s*\*\*Voir les questions encore ouvertes\*\*/u', '→ [Voir les questions ouvertes](#questions-ouvertes)', $text);
        $text = preg_replace('/→\s*\*\*Voir les questio[^*]*\*\*/u', '→ [Voir les questions ouvertes](#questions-ouvertes)', $text);
        $text = preg_replace('/→\s*\*\*Comparer la convention 2025[^*]*\*\*/u', '→ [Comparer la convention 2025 et le projet 2026 article par article](#comparatif-2025-2026)', $text);

        return $text;
    }

    private function compactWhitespace(string $text): string
    {
        $text = preg_replace("/[ \t]+\n/", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }

    private function readPackFile(string $file): string
    {
        $path = $this->packPath . '/' . $file;
        if (! file_exists($path)) {
            return "\n\n> Fichier {$file} manquant.\n";
        }

        return file_get_contents($path);
    }
}
