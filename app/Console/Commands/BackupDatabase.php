<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database 
                            {--keep=7 : Number of days to keep backups}
                            {--compress : Compress backup with gzip}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup the database with automatic cleanup';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🦞 Starting database backup...');

        // Configuration
        $keepDays = $this->option('keep');
        $compress = $this->option('compress');
        $backupDir = storage_path('app/backups');
        $timestamp = Carbon::now()->format('Ymd_His');
        $filename = "vinyls_stock_{$timestamp}.sql";
        $filepath = "{$backupDir}/{$filename}";

        // Create backup directory
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // Get database configuration
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if ($connection !== 'mysql') {
            $this->error("Backup currently only supports MySQL. Detected: {$connection}");
            return Command::FAILURE;
        }

        $this->info("Database: {$config['database']}");
        $this->info("Backup file: {$filepath}");

        try {
            // Build mysqldump command
            $command = sprintf(
                'mysqldump -h%s -u%s -p%s %s > %s 2>/dev/null',
                escapeshellarg($config['host']),
                escapeshellarg($config['username']),
                escapeshellarg($config['password']),
                escapeshellarg($config['database']),
                escapeshellarg($filepath)
            );

            // Execute backup
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                $this->error('❌ Database backup failed!');
                return Command::FAILURE;
            }

            // Compress if requested
            if ($compress) {
                $this->info('Compressing backup...');
                exec("gzip {$filepath}");
                $filepath .= '.gz';
            }

            $fileSize = $this->formatBytes(filesize($filepath));
            $this->info("✅ Backup completed: {$filename}" . ($compress ? '.gz' : '') . " ({$fileSize})");

            // Cleanup old backups
            $this->cleanupOldBackups($backupDir, $keepDays);

            // Show stats
            $backupCount = count(glob("{$backupDir}/*.sql*"));
            $this->info("📊 Total backups stored: {$backupCount}");
            $this->info('🎉 Backup completed successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Remove backups older than specified days
     */
    private function cleanupOldBackups(string $backupDir, int $keepDays): void
    {
        $this->info("Cleaning up backups older than {$keepDays} days...");
        
        $deleted = 0;
        $cutoff = Carbon::now()->subDays($keepDays)->timestamp;

        foreach (glob("{$backupDir}/*.sql*") as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->info("🗑️  Deleted {$deleted} old backup(s)");
        }
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}