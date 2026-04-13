<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Jobs\DistributeToHostJob;
use Illuminate\Console\Command;

class VideoAutoHealer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:auto-heal {--force : Force healing even for skipped videos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically rediscover and heal (re-upload) failed or missing video mirrors';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Scanning for sick videos...");

        $videos = Video::where('download_status', 'completed')->get();
        $healedCount = 0;

        foreach ($videos as $video) {
            $status = $video->hosting_status ?? [];
            $hosts = ['streamtape', 'doodstream'];
            $needsHealing = false;

            foreach ($hosts as $host) {
                $hostStatus = $status[$host] ?? 'missing';

                // Check if host needs healing
                if ($hostStatus === 'failed' || $hostStatus === 'missing' || ($this->option('force') && $hostStatus === 'skipped')) {
                    $this->warn("Healing [{$host}] for video: {$video->title} ({$video->slug})");
                    DistributeToHostJob::dispatch($video, $host);
                    $needsHealing = true;
                }
            }

            if ($needsHealing) {
                $healedCount++;
            }
        }

        if ($healedCount > 0) {
            $this->success("Sent {$healedCount} sick videos to the emergency room for mirroring.");
        } else {
            $this->info("No sick videos found. All logistics are operational.");
        }
    }
}
