<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MultiHostService;

class DistributeVideo extends Command
{
    protected $signature = 'app:distribute-video {slug} {host?}';
    protected $description = 'Manually trigger video distribution to external hosts';

    public function handle(MultiHostService $service)
    {
        $slug = $this->argument('slug');
        $host = $this->argument('host');

        $video = \App\Models\Video::where('slug', $slug)->first();

        if (!$video) {
            $this->error("Video with slug [{$slug}] not found.");
            return 1;
        }

        $drivers = $host ? [$host] : array_keys($service->allDrivers());

        foreach ($drivers as $driverIdentifier) {
            $this->info("Dispatching distribution job for host: {$driverIdentifier}");
            \App\Jobs\DistributeToHostJob::dispatch($video, $driverIdentifier);
        }

        $this->info("All jobs dispatched successfully.");
        return 0;
    }
}
