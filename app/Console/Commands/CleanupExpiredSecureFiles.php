<?php

namespace App\Console\Commands;

use App\Models\SecureFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupExpiredSecureFiles extends Command
{
    protected $signature = 'secure-files:cleanup {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Clean up expired secure files and their associated storage files';

    public function handle(): int
    {
        $expiredFiles = SecureFile::where('expires_at', '<', now())->get();

        if ($expiredFiles->isEmpty()) {
            $this->info('No expired files found.');
            return 0;
        }

        $this->info("Found {$expiredFiles->count()} expired files.");

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN - No files will be deleted');

            foreach ($expiredFiles as $file) {
                $this->line("- {$file->name} (expired: {$file->expires_at->format('Y-m-d H:i:s')})");
            }

            return 0;
        }

        $deletedCount = 0;
        $storageErrors = 0;

        foreach ($expiredFiles as $file) {
            try {
                // Delete from storage
                if (Storage::exists($file->file_path)) {
                    Storage::delete($file->file_path);
                }

                // Delete database record
                $file->delete();
                $deletedCount++;

                $this->line("Deleted: {$file->name}");
            } catch (\Exception $e) {
                $storageErrors++;
                $this->error("Error deleting {$file->name}: {$e->getMessage()}");
            }
        }

        $this->info("Successfully deleted {$deletedCount} files.");

        if ($storageErrors > 0) {
            $this->warn("{$storageErrors} files had storage errors.");
        }

        return 0;
    }
}

