<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database {--auto : Automatic backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup the database and store it securely';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting database backup...');

        try {
            // Create backup directory if it doesn't exist
            $backupPath = storage_path('app/backups');
            if (!File::exists($backupPath)) {
                File::makeDirectory($backupPath, 0755, true);
            }

            // Generate backup filename
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "backup_{$timestamp}.sql";
            $fullPath = "{$backupPath}/{$filename}";

            // Get database configuration
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');

            // Create backup using mysqldump
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s %s > %s',
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($host),
                escapeshellarg($database),
                escapeshellarg($fullPath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                // Compress the backup
                $compressedPath = "{$fullPath}.gz";
                file_put_contents($compressedPath, gzencode(file_get_contents($fullPath), 9));
                File::delete($fullPath);

                // Log backup info
                $this->info("Database backup created successfully: {$compressedPath}");
                
                // Clean old backups (keep last 7 days)
                $this->cleanOldBackups($backupPath);

                // Create backup record in database
                $this->recordBackup($compressedPath);

                // If auto backup, also upload to cloud (optional)
                if ($this->option('auto')) {
                    $this->uploadToCloud($compressedPath);
                }

                return 0;
            } else {
                $this->error('Database backup failed!');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Backup error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Clean old backups (keep last 7 days)
     */
    private function cleanOldBackups($backupPath)
    {
        $files = File::glob($backupPath . '/backup_*.sql.gz');
        
        foreach ($files as $file) {
            $fileTime = Carbon::createFromTimestamp(File::lastModified($file));
            
            if ($fileTime->diffInDays(Carbon::now()) > 7) {
                File::delete($file);
                $this->info("Deleted old backup: " . basename($file));
            }
        }
    }

    /**
     * Record backup in database
     */
    private function recordBackup($filePath)
    {
        try {
            DB::table('backup_records')->insert([
                'filename' => basename($filePath),
                'path' => $filePath,
                'size' => File::size($filePath),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        } catch (\Exception $e) {
            $this->error('Failed to record backup: ' . $e->getMessage());
        }
    }

    /**
     * Upload backup to cloud storage (optional)
     */
    private function uploadToCloud($filePath)
    {
        // This is a placeholder for cloud upload
        // You can integrate with AWS S3, Google Cloud, etc.
        $this->info('Cloud upload feature not implemented yet');
    }
}
