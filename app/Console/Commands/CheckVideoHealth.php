<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckVideoHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:check-health {--limit= : Limit the number of videos to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch health check jobs for all videos to verify storage and mirror status.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $query = \App\Models\Video::query();

        if ($limit) {
            $query->take($limit);
        }

        $videos = $query->get();
        $count = $videos->count();

        $this->info("Dispatching health check jobs for {$count} videos...");
        $bar = $this->output->createProgressBar($count);

        foreach ($videos as $video) {
            \App\Jobs\CheckVideoHealthJob::dispatch($video);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done! Jobs have been dispatched to the queue.');
    }
}
