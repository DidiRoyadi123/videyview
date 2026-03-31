<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ConsolidateStorage extends Command
{
    protected $signature = 'app:consolidate-storage';
    protected $description = 'Clean up "video-video-" duplicates and standardize filenames';

    public function handle()
    {
        $this->info("Starting Storage Consolidation...");

        $videoDir = storage_path('app/public/videos');
        
        if (!File::isDirectory($videoDir)) {
            $this->error("Video directory not found: $videoDir");
            return;
        }

        $files = File::files($videoDir);
        $deleted = 0;
        $renamed = 0;

        foreach ($files as $file) {
            $filename = $file->getFilename();

            if (str_starts_with($filename, 'video-video-')) {
                $correctName = str_replace('video-video-', 'video-', $filename);
                $correctPath = $videoDir . DIRECTORY_SEPARATOR . $correctName;
                $currentPath = $file->getRealPath();

                if (File::exists($correctPath)) {
                    // Duplicate exists, delete the "video-video-" version
                    $this->warn("Deleting duplicate: $filename (already have $correctName)");
                    File::delete($currentPath);
                    $deleted++;
                } else {
                    // Only the "video-video-" version exists, rename it
                    $this->info("Fixing filename: $filename -> $correctName");
                    File::move($currentPath, $correctPath);
                    $renamed++;
                }
            }
        }

        $this->newLine();
        $this->info("Consolidation complete.");
        $this->info("Deleted: $deleted duplicates.");
        $this->info("Renamed: $renamed misnamed files.");
    }
}
