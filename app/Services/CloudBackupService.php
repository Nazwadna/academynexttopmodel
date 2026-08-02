<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class CloudBackupService
{
    protected $s3Client;
    protected $bucket;
    protected $backupPath;
    protected $localBackupPath;

    public function __construct()
    {
        $this->backupPath = config('backup.cloud.path', 'backups');
        $this->localBackupPath = storage_path('app/backups');
        
        // Initialize S3 client if AWS credentials are configured
        if (config('backup.cloud.driver') === 's3') {
            $this->s3Client = new S3Client([
                'version' => 'latest',
                'region' => config('backup.cloud.region'),
                'credentials' => [
                    'key' => config('backup.cloud.key'),
                    'secret' => config('backup.cloud.secret'),
                ],
            ]);
            $this->bucket = config('backup.cloud.bucket');
        }
    }

    /**
     * Upload backup to cloud storage
     */
    public function uploadBackup($localFilePath, $remoteFileName = null)
    {
        try {
            if (!file_exists($localFilePath)) {
                throw new \Exception("Local backup file not found: {$localFilePath}");
            }

            $remoteFileName = $remoteFileName ?: basename($localFilePath);
            $remotePath = $this->backupPath . '/' . $remoteFileName;

            if (config('backup.cloud.driver') === 's3') {
                return $this->uploadToS3($localFilePath, $remotePath);
            } elseif (config('backup.cloud.driver') === 'google_drive') {
                return $this->uploadToGoogleDrive($localFilePath, $remoteFileName);
            } else {
                throw new \Exception('Cloud storage driver not configured');
            }
        } catch (\Exception $e) {
            Log::error('Cloud backup upload failed', [
                'file' => $localFilePath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Upload to AWS S3
     */
    protected function uploadToS3($localFilePath, $remotePath)
    {
        try {
            $result = $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $remotePath,
                'SourceFile' => $localFilePath,
                'StorageClass' => 'STANDARD_IA', // Infrequent Access for cost optimization
                'Metadata' => [
                    'backup_date' => Carbon::now()->toISOString(),
                    'original_filename' => basename($localFilePath),
                    'file_size' => filesize($localFilePath),
                ],
            ]);

            Log::info('Backup uploaded to S3', [
                'file' => $localFilePath,
                'remote_path' => $remotePath,
                'etag' => $result['ETag'] ?? null,
            ]);

            return [
                'success' => true,
                'remote_path' => $remotePath,
                'url' => $this->s3Client->getObjectUrl($this->bucket, $remotePath),
                'etag' => $result['ETag'] ?? null,
            ];
        } catch (AwsException $e) {
            Log::error('S3 upload failed', [
                'file' => $localFilePath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Upload to Google Drive
     */
    protected function uploadToGoogleDrive($localFilePath, $remoteFileName)
    {
        // This is a placeholder for Google Drive integration
        // In production, you would use Google Drive API
        
        Log::info('Google Drive upload (placeholder)', [
            'file' => $localFilePath,
            'remote_filename' => $remoteFileName,
        ]);

        return [
            'success' => true,
            'remote_path' => $remoteFileName,
            'url' => 'https://drive.google.com/file/d/placeholder',
        ];
    }

    /**
     * Download backup from cloud storage
     */
    public function downloadBackup($remotePath, $localFilePath)
    {
        try {
            if (config('backup.cloud.driver') === 's3') {
                return $this->downloadFromS3($remotePath, $localFilePath);
            } elseif (config('backup.cloud.driver') === 'google_drive') {
                return $this->downloadFromGoogleDrive($remotePath, $localFilePath);
            } else {
                throw new \Exception('Cloud storage driver not configured');
            }
        } catch (\Exception $e) {
            Log::error('Cloud backup download failed', [
                'remote_path' => $remotePath,
                'local_path' => $localFilePath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Download from AWS S3
     */
    protected function downloadFromS3($remotePath, $localFilePath)
    {
        try {
            // Ensure local directory exists
            $localDir = dirname($localFilePath);
            if (!is_dir($localDir)) {
                mkdir($localDir, 0755, true);
            }

            $result = $this->s3Client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $remotePath,
            ]);

            file_put_contents($localFilePath, $result['Body']->getContents());

            Log::info('Backup downloaded from S3', [
                'remote_path' => $remotePath,
                'local_path' => $localFilePath,
                'size' => filesize($localFilePath),
            ]);

            return [
                'success' => true,
                'local_path' => $localFilePath,
                'size' => filesize($localFilePath),
            ];
        } catch (AwsException $e) {
            Log::error('S3 download failed', [
                'remote_path' => $remotePath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * List cloud backups
     */
    public function listBackups($prefix = null)
    {
        try {
            $prefix = $prefix ?: $this->backupPath . '/';

            if (config('backup.cloud.driver') === 's3') {
                return $this->listS3Backups($prefix);
            } elseif (config('backup.cloud.driver') === 'google_drive') {
                return $this->listGoogleDriveBackups();
            } else {
                throw new \Exception('Cloud storage driver not configured');
            }
        } catch (\Exception $e) {
            Log::error('Failed to list cloud backups', [
                'prefix' => $prefix,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * List S3 backups
     */
    protected function listS3Backups($prefix)
    {
        try {
            $objects = $this->s3Client->listObjectsV2([
                'Bucket' => $this->bucket,
                'Prefix' => $prefix,
            ]);

            $backups = [];
            
            foreach ($objects['Contents'] as $object) {
                $backups[] = [
                    'key' => $object['Key'],
                    'size' => $object['Size'],
                    'last_modified' => Carbon::parse($object['LastModified']),
                    'etag' => $object['ETag'],
                    'storage_class' => $object['StorageClass'] ?? 'STANDARD',
                ];
            }

            return $backups;
        } catch (AwsException $e) {
            Log::error('S3 list failed', [
                'prefix' => $prefix,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * List Google Drive backups
     */
    protected function listGoogleDriveBackups()
    {
        // Placeholder for Google Drive implementation
        return [];
    }

    /**
     * Delete backup from cloud storage
     */
    public function deleteBackup($remotePath)
    {
        try {
            if (config('backup.cloud.driver') === 's3') {
                return $this->deleteFromS3($remotePath);
            } elseif (config('backup.cloud.driver') === 'google_drive') {
                return $this->deleteFromGoogleDrive($remotePath);
            } else {
                throw new \Exception('Cloud storage driver not configured');
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete cloud backup', [
                'remote_path' => $remotePath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete from S3
     */
    protected function deleteFromS3($remotePath)
    {
        try {
            $this->s3Client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $remotePath,
            ]);

            Log::info('Backup deleted from S3', [
                'remote_path' => $remotePath,
            ]);

            return ['success' => true];
        } catch (AwsException $e) {
            Log::error('S3 delete failed', [
                'remote_path' => $remotePath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete from Google Drive
     */
    protected function deleteFromGoogleDrive($remotePath)
    {
        // Placeholder for Google Drive implementation
        return ['success' => true];
    }

    /**
     * Sync local backups to cloud
     */
    public function syncLocalToCloud()
    {
        try {
            $localFiles = glob($this->localBackupPath . '/*.sql.gz');
            $syncedCount = 0;
            $failedCount = 0;

            foreach ($localFiles as $localFile) {
                $fileName = basename($localFile);
                
                try {
                    $this->uploadBackup($localFile);
                    $syncedCount++;
                } catch (\Exception $e) {
                    Log::error('Failed to sync local backup to cloud', [
                        'file' => $localFile,
                        'error' => $e->getMessage(),
                    ]);
                    $failedCount++;
                }
            }

            Log::info('Local to cloud sync completed', [
                'total_files' => count($localFiles),
                'synced' => $syncedCount,
                'failed' => $failedCount,
            ]);

            return [
                'success' => true,
                'total_files' => count($localFiles),
                'synced' => $syncedCount,
                'failed' => $failedCount,
            ];
        } catch (\Exception $e) {
            Log::error('Local to cloud sync failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Clean up old cloud backups
     */
    public function cleanupOldBackups($retentionDays = 30)
    {
        try {
            $backups = $this->listBackups();
            $cutoffDate = Carbon::now()->subDays($retentionDays);
            $deletedCount = 0;

            foreach ($backups as $backup) {
                if ($backup['last_modified']->lt($cutoffDate)) {
                    try {
                        $this->deleteBackup($backup['key']);
                        $deletedCount++;
                    } catch (\Exception $e) {
                        Log::error('Failed to delete old cloud backup', [
                            'backup' => $backup,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            Log::info('Cloud backup cleanup completed', [
                'retention_days' => $retentionDays,
                'deleted_count' => $deletedCount,
            ]);

            return [
                'success' => true,
                'deleted_count' => $deletedCount,
            ];
        } catch (\Exception $e) {
            Log::error('Cloud backup cleanup failed', [
                'retention_days' => $retentionDays,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get cloud storage statistics
     */
    public function getStorageStatistics()
    {
        try {
            $backups = $this->listBackups();
            $totalSize = 0;
            $totalFiles = count($backups);
            $oldestBackup = null;
            $newestBackup = null;

            foreach ($backups as $backup) {
                $totalSize += $backup['size'];
                
                if ($oldestBackup === null || $backup['last_modified']->lt($oldestBackup)) {
                    $oldestBackup = $backup['last_modified'];
                }
                
                if ($newestBackup === null || $backup['last_modified']->gt($newestBackup)) {
                    $newestBackup = $backup['last_modified'];
                }
            }

            return [
                'total_files' => $totalFiles,
                'total_size' => $totalSize,
                'total_size_human' => $this->formatBytes($totalSize),
                'oldest_backup' => $oldestBackup,
                'newest_backup' => $newestBackup,
                'storage_class_distribution' => $this->getStorageClassDistribution($backups),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get storage statistics', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get storage class distribution
     */
    protected function getStorageClassDistribution($backups)
    {
        $distribution = [];
        
        foreach ($backups as $backup) {
            $class = $backup['storage_class'] ?? 'STANDARD';
            
            if (!isset($distribution[$class])) {
                $distribution[$class] = [
                    'count' => 0,
                    'size' => 0,
                ];
            }
            
            $distribution[$class]['count']++;
            $distribution[$class]['size'] += $backup['size'];
        }

        // Format sizes
        foreach ($distribution as $class => &$data) {
            $data['size_human'] = $this->formatBytes($data['size']);
        }

        return $distribution;
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Test cloud storage connection
     */
    public function testConnection()
    {
        try {
            if (config('backup.cloud.driver') === 's3') {
                // Test S3 connection by listing bucket
                $this->s3Client->headBucket(['Bucket' => $this->bucket]);
                
                return [
                    'success' => true,
                    'message' => 'S3 connection successful',
                    'driver' => 's3',
                    'bucket' => $this->bucket,
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Cloud storage driver not configured',
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }
}
