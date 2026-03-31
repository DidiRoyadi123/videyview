<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-storage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync database status with local storage files (videos and thumbnails)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting Storage Sync (Mass Audit Optimization)...");

        $storageRoot = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('app/public'));
        $videoDir = $storageRoot . DIRECTORY_SEPARATOR . 'videos';
        $thumbDir = $storageRoot . DIRECTORY_SEPARATOR . 'thumbnails';

        // 1. Pre-scan the entire directories (Flash Audit)
        $existingVideos = is_dir($videoDir) ? array_flip(scandir($videoDir)) : [];
        $existingThumbs = is_dir($thumbDir) ? array_flip(scandir($thumbDir)) : [];

        $videos = Video::all();
        $total = $videos->count();
        $synced = 0;
        $missing = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($videos as $video) {
            $foundPath = null;
            
            // Try naming conventions based on the slug (O(1) lookup)
            // Goal: Filename must be exactly "video-{identifier}.mp4"
            if (str_starts_with($video->slug, 'video-')) {
                $file1 = $video->slug . '.mp4';
                $file2 = str_replace('video-', '', $video->slug) . '.mp4'; // Fallback to raw id
            } else {
                $file1 = 'video-' . $video->slug . '.mp4';
                $file2 = $video->slug . '.mp4';
            }

            if (isset($existingVideos[$file1])) {
                $foundPath = 'videos/' . $file1;
            } elseif (isset($existingVideos[$file2])) {
                $foundPath = 'videos/' . $file2;
            }

            if ($foundPath) {
                if ($video->download_status !== 'completed' || $video->local_path !== $foundPath) {
                    $video->download_status = 'completed';
                    $video->local_path = $foundPath;
                }
                $synced++;
            } else {
                if ($video->download_status === 'completed') {
                    $video->download_status = null;
                    $video->local_path = null;
                }
                $missing++;
            }

            // Sync thumbnail if pre-scanned (O(1) lookup)
            $thumbFile = $video->slug . '.jpg';
            if (isset($existingThumbs[$thumbFile])) {
                $video->thumbnail_url = '/storage/thumbnails/' . $thumbFile;
            }

            if ($video->isDirty()) {
                $video->save();
                \Illuminate\Support\Facades\Cache::forget("video_show_{$video->slug}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully audited $total records. Existing: $synced. Missing: $missing.");
        \Illuminate\Support\Facades\Cache::forget('admin_stats');
    }
}
