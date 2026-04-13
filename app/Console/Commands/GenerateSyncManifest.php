<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateSyncManifest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-sync-manifest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate sync_list.json for the standalone worker from pending videos.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating Sync Manifest...');

        $videos = Video::where(function($q) {
                        $q->where('download_status', '!=', 'completed')
                          ->orWhereNull('download_status');
                    })
                    ->whereNotNull('url')
                    ->get(['id', 'title', 'url', 'slug']);

        $count = $videos->count();

        if ($count === 0) {
            $this->warn('No pending videos found to sync.');
            // We still create an empty file to prevent sync.py crash
            File::put(base_path('sync_list.json'), json_encode([], JSON_PRETTY_PRINT));
            return 0;
        }

        $content = json_encode($videos, JSON_PRETTY_PRINT);
        
        File::put(base_path('sync_list.json'), $content);

        $this->info("Successfully generated sync_list.json with $count videos.");
        
        return 0;
    }
}
