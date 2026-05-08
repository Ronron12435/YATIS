<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupAvatars extends Command
{
    protected $signature = 'avatars:cleanup';
    protected $description = 'Remove orphaned avatar and cover photo files';

    public function handle()
    {
        // Get all profile pictures and cover photos from database
        $profilePictures = DB::table('users')
            ->whereNotNull('profile_picture')
            ->pluck('profile_picture')
            ->toArray();

        $coverPhotos = DB::table('users')
            ->whereNotNull('cover_photo')
            ->pluck('cover_photo')
            ->toArray();

        $usedFiles = array_merge($profilePictures, $coverPhotos);
        $usedFiles = array_unique($usedFiles);

        $this->info('Files referenced in database: ' . count($usedFiles));
        $this->line('Used files:');
        foreach ($usedFiles as $file) {
            $this->line("  - $file");
        }

        // Get all files in the avatars directory
        $avatarDir = public_path('uploads/avatars');
        $allFiles = array_diff(scandir($avatarDir), ['.', '..']);

        $this->line("\n\nTotal files in directory: " . count($allFiles));

        // Find orphaned files
        $orphanedFiles = [];
        foreach ($allFiles as $file) {
            $fullPath = "avatars/$file";
            if (!in_array($fullPath, $usedFiles)) {
                $orphanedFiles[] = $file;
            }
        }

        $this->line("\n\nOrphaned files (" . count($orphanedFiles) . "):");
        foreach ($orphanedFiles as $file) {
            $this->line("  - $file");
        }

        // Ask for confirmation before deleting
        if (count($orphanedFiles) > 0) {
            if ($this->confirm("\n\nDo you want to delete these orphaned files?")) {
                $deletedCount = 0;
                foreach ($orphanedFiles as $file) {
                    $filePath = $avatarDir . '/' . $file;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                        $this->line("Deleted: $file");
                        $deletedCount++;
                    }
                }
                $this->info("\n\nCleanup complete! Deleted $deletedCount files.");
            } else {
                $this->info("Cleanup cancelled.");
            }
        } else {
            $this->info("\n\nNo orphaned files found. Everything is clean!");
        }
    }
}
