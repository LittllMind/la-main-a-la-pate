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
        $index = $this->readPackFile('index.md');
        $ficheD = $this->readPackFile('fiche-d-sommation-24-avril-2026.md');
        $ficheE = $this->readPackFile('fiche-e-mail-maire-14-mai-2026.md');
        $ficheH = $this->readPackFile('fiche-h-demande-aot.md');
        $chronologie = $this->readPackFile('chronologie.md');
        $questions = $this->readPackFile('questions-ouvertes.md');

        $index = $this->cleanIndex($index);

        [$ficheEclean, $ficheF] = $this->splitFicheE($ficheE);

        $ficheD = $this->stripLeadingH1($ficheD);
        $ficheEclean = $this->stripLeadingH1($ficheEclean);
        $ficheF = $this->stripLeadingH1($ficheF);
        $ficheH = $this->stripLeadingH1($ficheH);

        $chronologie = $this->cleanChronologie($chronologie);
        $questions = $this->addAnchorId($questions, 'questions-ouvertes');

        $index = $this->insertAfterSection($index, '## 5.', "\n\n## Fiche — Sommation du 24 avril 2026\n\n" . $ficheD);
        $index = $this->insertAfterSection($index, '## 6.', "\n\n## Fiche — Email du maire du 14 mai 2026\n\n" . $ficheEclean);
        $index = $this->insertAfterSection($index, '## 7.', "\n\n## Fiche — Demandes de documents administratifs\n\n" . $ficheF);
        $index = $this->insertAfterSection($index, '## 8.', "\n\n## Fiche — Demande d'AOT du 16 juin 2026\n\n" . $ficheH);

        $index .= "\n\n---\n\n## Comparatif 2025 / 2026 {#comparatif-2025-2026}\n\n"
            . "Voir le tableau comparatif des conventions 2025 et 2026 (à venir).\n\n"
            . "---\n\n" . $chronologie . "\n\n---\n\n" . $questions;

        return $index;
    }

    private function assembleCitizenBody(string $publicBody): string
    {
        return $publicBody;
    }

    private function assembleWorkingBody(string $publicBody): string
    {
        // Mapping legacy des corps : le Working n'est plus fourni par le manifest PUBLIC-V1.
        return $publicBody;
    }

    private function readPackFile(string $file): string
    {
        $path = $this->packPath . '/' . $file;
        if (! file_exists($path)) {
            return "\n\n> Fichier {$file} manquant.\n";
        }

        return file_get_contents($path);
    }

    private function cleanIndex(string $markdown): string
    {
        // 1. Supprimer H1 titre du pack (même avec whitespace précédent)
        $markdown = preg_replace('/^\s*#\s+LA\s+S[ÉE]RAPHOTH[ÈE]QUE.*?\n+/ui', "\n", $markdown);

        // 2. Supprimer noms de fichiers + backticks éventuels (regex multi-formes)
        $markdown = preg_replace('/`?fiche-[a-z0-9\-]+\.md`?/iu', '', $markdown);

        // 3. Nettoyer doubles backticks résiduels et backticks isolés devenus parasites
        $markdown = str_replace('``', '', $markdown);           // `` → rien
        $markdown = str_replace("'`", "'", $markdown);            // "'`" n'existe probablement pas
        $markdown = str_replace("`\n", "\n", $markdown);          // backtick avant newline
        $markdown = str_replace("\n`", "\n", $markdown);          // backtick après newline

        // 4. Normaliser hiérarchie
        $lines = explode("\n", $markdown);
        $newLines = [];
        foreach ($lines as $line) {
            if (preg_match('/^#\s+(\d+\.\s+.*)/', $line, $m)) {
                // Section principale en H1 du pack (2-12) → H2
                $newLines[] = '## ' . $m[1];
            } elseif (preg_match('/^##\s+(\d+\.\s+.*)/', $line, $m)) {
                // Section déjà H2 → garder H2
                $newLines[] = '## ' . $m[1];
            } elseif (preg_match('/^##\s+(.+)/', $line, $m)) {
                // Sous-section texte → H3
                $newLines[] = '### ' . $m[1];
            } else {
                $newLines[] = $line;
            }
        }
        $markdown = implode("\n", $newLines);

        // 5. Substitutions éditoriales validées
        $markdown = str_replace(
            'une sommation mandatée par la commune leur demande',
            'une sommation établie à la demande de la commune et adressée à Aurélien Tisserand demande',
            $markdown
        );
        $markdown = str_replace(
            'Les demandes ne commencent pas le 6 mai.',
            'Les premières demandes documentaires actuellement retrouvées remontent au 13 avril 2026.',
            $markdown
        );
        // Neutraliser mention non attestée
        $markdown = preg_replace(
            '/le Défenseur des Droits de la Lozère en copie\.?/u',
            "l'avocat en copie.",
            $markdown
        );
        // Titre §10
        $markdown = str_replace(
            '## 10. Un engagement du programme municipal à documenter',
            '## 10. Profession de foi : source originale à obtenir',
            $markdown
        );

        // 6. Navigation liens
        $markdown = preg_replace('/→\s*\*\*Voir la chronologie\*\*/u', '→ [Voir la chronologie](#chronologie)', $markdown);
        $markdown = preg_replace('/→\s*\*\*Voir les documents\*\*/u', '→ [Voir les documents](#documents)', $markdown);
        $markdown = preg_replace('/→\s*\*\*Voir les questio[^*]*\*\*/u', '→ [Voir les questions ouvertes](#questions-ouvertes)', $markdown);

        // §12
        $markdown = preg_replace('/→\s*\*\*Chronologie\*\*/u', '→ [Chronologie](#chronologie)', $markdown);
        $markdown = preg_replace('/→\s*\*\*Comparaison convention 2025[^*]*\*\*/u', '→ [Comparaison 2025 / projet 2026](#comparatif-2025-2026)', $markdown);
        $markdown = preg_replace('/→\s*\*\*Documents et fiches documentaires\*\*/u', '→ [Documents et fiches documentaires](#documents)', $markdown);
        $markdown = preg_replace('/→\s*\*\*Questions ouvertes\*\*/u', '→ [Questions ouvertes](#questions-ouvertes)', $markdown);

        // §2
        $markdown = preg_replace('/→\s*\*\*Comparer la convention 2025[^*]*\*\*/u', '→ [Comparer la convention 2025 et le projet 2026](#comparatif-2025-2026)', $markdown);

        // §5 / §6
        $markdown = preg_replace('/→\s*\*\*Consulter la fiche documentaire[^*]*version expurgée[^*]*\*\*/u', '→ Voir la fiche ci-dessous. Version expurgée à venir.', $markdown);
        $markdown = preg_replace('/→\s*\*\*Consulter les extraits approuvés[^*]*\*\*/u', '→ Version expurgée à venir.', $markdown);

        // 7. Compact lignes vides + whitespace trailing
        $markdown = preg_replace("/[ \t]+\n/", "\n", $markdown);
        $markdown = preg_replace("/\n{3,}/", "\n\n", $markdown);

        return trim($markdown);
    }

    private function splitFicheE(string $fiche): array
    {
        if (preg_match('/^\s*#\s*[`\']*fiche-f-demandes-documents\.md[`\']*\s*$/mi', $fiche, $m, PREG_OFFSET_CAPTURE)) {
            $ePart = trim(substr($fiche, 0, $m[0][1]));
            $fPart = trim(substr($fiche, $m[0][1] + strlen($m[0][0])));
            // Nettoyer lignes artefacts restantes
            $fPart = preg_replace('/^\s*#\s*[`\']*fiche-f-demandes-documents\.md[`\']*\s*\n*/mi', '', $fPart);
            $fPart = preg_replace('/^\s*#\s+Demandes de documents administratifs\s*$/mi', '', $fPart);

            return [$ePart, trim($fPart)];
        }

        return [$fiche, ''];
    }

    private function stripLeadingH1(string $text): string
    {
        $text = preg_replace('/^\s*#\s+[^\n]+\n*/u', '', $text);

        return trim($text);
    }

    private function cleanChronologie(string $text): string
    {
        $text = preg_replace('/^\s*#\s+Chronologie.*?\n/m', "## Chronologie — La Séraphothèque {#chronologie}\n", $text);

        return trim($text);
    }

    private function addAnchorId(string $text, string $id): string
    {
        // Remove artifact filename headings first
        $text = preg_replace('/^\s*#\s*`?[^`]+?\.md`?\s*\n*/m', '', $text);

        // Add anchor ID to the first remaining heading
        $text = preg_replace('/^(\s*##?\s+[^#\n{]+)/m', "\\1 { #$id }", $text, 1);

        return trim($text);
    }

    private function insertAfterSection(string $body, string $sectionMarker, string $content): string
    {
        if (! str_contains($body, $sectionMarker)) {
            return $body . $content;
        }

        $pos = strpos($body, $sectionMarker);
        $next = strpos($body, "\n## ", $pos + strlen($sectionMarker));

        if ($next === false) {
            return $body . $content;
        }

        return substr($body, 0, $next) . $content . substr($body, $next);
    }
}
