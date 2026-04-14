<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Video;
use App\Helpers\VideoHelper;

class GenerateLocalThumbnails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:generate-local-thumbnails';
    protected $description = 'Generate thumbnails for existing local videos';

    public function handle()
    {
        // Find local videos that don't have a local thumbnail URL
        $videos = Video::where('download_status', 'completed')
            ->get();

        $this->info("Found {$videos->count()} local videos needing or using non-local thumbnails.");

        /** @var Video $video */
        foreach ($videos as $video) {
            $this->info("Processing ID {$video->id}: {$video->title}");
            
            // Fix path normalization for storage_path
            $cleanLocalPath = ltrim(str_replace('\\', '/', $video->local_path), '/');
            $fullPath = storage_path('app/public/' . $cleanLocalPath);
            
            $this->info("  - Checking path: $fullPath");

            if (file_exists($fullPath)) {
                $success = VideoHelper::generateThumbnail($video, $fullPath);
                if ($success) {
                    $this->info("  - Generated and updated DB: " . $video->thumbnail_url);
                } else {
                    $this->error("  - Failed generation (Check laravel.log for FFmpeg errors).");
                }
            } else {
                $this->error("  - Video file NOT FOUND at $fullPath.");
            }
        }

        $this->info('Done.');
    }
}
