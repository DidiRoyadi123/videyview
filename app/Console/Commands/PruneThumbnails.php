<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PruneThumbnails extends Command
{
    protected $signature = 'app:prune-thumbnails {--dry-run : Only show what would be deleted}';
    protected $description = 'Remove orphaned and legacy ID-based thumbnail files';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $this->info("Starting Thumbnail Pruning..." . ($dryRun ? " [DRY RUN]" : ""));

        $thumbDir = storage_path('app/public/thumbnails');
        
        if (!File::isDirectory($thumbDir)) {
            $this->error("Thumbnail directory not found: $thumbDir");
            return;
        }

        // 1. Get all valid slugs from DB
        $validSlugs = Video::pluck('slug')->flip()->toArray();
        $this->info("Loaded " . count($validSlugs) . " valid slugs from database.");

        // 2. Scan directory
        $files = File::files($thumbDir);
        $totalRemoved = 0;
        $totalKept = 0;

        foreach ($files as $file) {
            $filename = $file->getFilename();
            $extension = strtolower($file->getExtension());

            if ($extension !== 'jpg' && $extension !== 'jpeg' && $extension !== 'png') {
                continue;
            }

            $slugCandidate = $file->getBasename('.' . $file->getExtension());

            // Check if slugCandidate is in our valid list
            if (isset($validSlugs[$slugCandidate])) {
                $totalKept++;
                continue;
            }

            // Orphan or legacy file detected
            $totalRemoved++;
            $this->warn("Unnecessary file detected: $filename");

            if (!$dryRun) {
                File::delete($file->getRealPath());
            }
        }

        $this->newLine();
        $this->info("Pruning complete.");
        $this->info("Files kept: $totalKept");
        $this->info("Files " . ($dryRun ? "found to be " : "") . "removed: $totalRemoved");
    }
}
