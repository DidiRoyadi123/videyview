<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Jobs\DistributeToHostJob;
use App\Services\MultiHostService;
use Illuminate\Console\Command;

class BulkDistributeVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:distribute-bulk {host?} {--limit=50}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue mirroring for all local videos that are not yet mirrored.';

    /**
     * Execute the console command.
     */
    public function handle(MultiHostService $service)
    {
        $host = $this->argument('host');
        $limit = $this->option('limit');

        $query = Video::where('download_status', 'completed')
            ->where(function($q) {
                $q->whereNull('mirror_links')
                  ->orWhere('mirror_links', '[]');
            })
            ->latest();

        $count = $query->count();
        $videos = $query->take($limit)->get();

        if ($videos->isEmpty()) {
            $this->info("No local videos found that need mirroring.");
            return 0;
        }

        $this->info("Found {$count} local videos. Queueing the first {$videos->count()}...");

        $bar = $this->output->createProgressBar($videos->count());
        $bar->start();

        $hosts = $host ? [$host] : array_keys($service->allDrivers());

        foreach ($videos as $index => $video) {
            foreach ($hosts as $h) {
                // Skip videy simulator for bulk unless specified
                if ($h === 'videy' && !$host) continue;

                // Staggered dispatch to avoid slamming the queue/API
                DistributeToHostJob::dispatch($video, $h)->delay(now()->addSeconds($index * 5));
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully queued {$videos->count()} videos for mirroring.");

        return 0;
    }
}
