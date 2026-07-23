<?php

namespace App\Console\Commands;

use App\Models\SubjectDocument;
use App\Services\DocumentStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Migre les documents existants vers le stockage privé et chiffré.
 *
 * Idempotent : si un document est déjà marqué disk=documents et que le fichier
 * existe dans le nouveau stockage, on le saute.
 */
class MigrateDocumentsToSecureStorage extends Command
{
    protected $signature = 'documents:migrate-secure
                            {--cleanup : Supprimer les fichiers source publics après migration réussie}
                            {--dry-run : Afficher les opérations sans les exécuter}';

    protected $description = 'Migre les SubjectDocument vers le stockage privé et chiffré';

    public function handle(): int
    {
        $service = app(DocumentStorageService::class);
        $dryRun = $this->option('dry-run');
        $cleanup = $this->option('cleanup');

        $docs = SubjectDocument::with('subject')->get();
        $migrated = 0;
        $skipped = 0;
        $failed = 0;
        $toCleanup = [];

        foreach ($docs as $doc) {
            $this->info("Document #{$doc->id} — {$doc->filename}");

            // Déjà dans le stockage sécurisé
            if ($doc->disk === 'documents' && $service->exists($doc->path)) {
                $this->line("  -> déjà sécurisé, skip");
                $skipped++;
                continue;
            }

            $sourcePath = $this->resolveSourcePath($doc);
            if (! $sourcePath || ! file_exists($sourcePath) || ! is_readable($sourcePath)) {
                $this->error("  -> FICHIER SOURCE INTROUVABLE : {$sourcePath}");
                $failed++;
                continue;
            }

            if ($dryRun) {
                $this->line("  -> dry-run : chiffrerait {$sourcePath}");
                continue;
            }

            try {
                $newPath = $service->storeEncrypted(
                    $doc->subject_id,
                    $sourcePath,
                    $doc->filename
                );
                $stored = basename($newPath);
                $size = filesize($sourcePath);
                $mime = mime_content_type($sourcePath) ?: $doc->mime_type;

                $doc->update([
                    'disk'            => 'documents',
                    'path'            => $newPath,
                    'stored_filename' => $stored,
                    'size'            => $size,
                    'mime_type'       => $mime,
                ]);

                $migrated++;
                $this->info("  -> migré vers {$newPath}");

                if ($cleanup) {
                    $toCleanup[] = $sourcePath;
                }
            } catch (\Throwable $e) {
                $this->error("  -> ERREUR : " . $e->getMessage());
                $failed++;
            }
        }

        if ($cleanup && ! $dryRun) {
            foreach ($toCleanup as $path) {
                if (File::exists($path)) {
                    File::delete($path);
                    $this->info("  -> nettoyé {$path}");
                }
            }

            // Nettoyer les dossiers vides dans storage/app/public/subject_documents et subjects
            $publicBase = storage_path('app/public');
            $this->cleanupEmptyDirs($publicBase . '/subject_documents');
            $this->cleanupEmptyDirs($publicBase . '/subjects');
        }

        $this->newLine();
        $this->info("Migrés : {$migrated} | Skips : {$skipped} | Échecs : {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function resolveSourcePath(SubjectDocument $doc): ?string
    {
        $path = $doc->path;

        // Chemin absolu dans la DB — normaliser
        if (str_starts_with($path, '/')) {
            return $path;
        }

        // Legacy disk subject_documents était dans storage/app/public
        $legacyBase = storage_path('app/public');
        $candidate = $legacyBase . '/' . ltrim($path, '/');

        if (file_exists($candidate)) {
            return $candidate;
        }

        // Fallback : si le disk actuel est subject_documents, on tente aussi ce root
        if ($doc->disk === 'subject_documents') {
            $diskBase = Storage::disk('subject_documents')->path('');
            $candidate2 = rtrim($diskBase, '/') . '/' . ltrim($path, '/');
            if (file_exists($candidate2)) {
                return $candidate2;
            }
        }

        return null;
    }

    private function cleanupEmptyDirs(string $root): void
    {
        if (! is_dir($root)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir() && $this->isDirEmpty($file->getPathname())) {
                @rmdir($file->getPathname());
            }
        }
    }

    private function isDirEmpty(string $dir): bool
    {
        $files = scandir($dir);
        return $files !== false && count($files) === 2;
    }
}
