<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WorkerMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:worker-monitor';
    protected $description = 'Monitor video-worker heartbeat and auto-restart if failed';

    public function handle()
    {
        $enabled = \App\Models\Setting::getValue('worker_self_healing', '0') === '1';
        
        if (!$enabled) {
            $this->warn('Self-healing is disabled in settings.');
            return;
        }

        $heartbeat = \Illuminate\Support\Facades\Cache::get('video_worker_heartbeat');
        $isAlive = $heartbeat && now()->diffInMinutes($heartbeat) < 5;

        if ($isAlive) {
            $this->info('Worker is alive. Heartbeat: ' . $heartbeat);
            return;
        }

        $this->error('Worker is dead or stuck! Attempting restart...');
        
        // Log the event
        \App\Models\Setting::setValue('last_worker_restart', now()->toDateTimeString());
        
        // On Windows, we use 'start /B' for background processes in cmd
        // or 'Start-Process' in PowerShell.
        // Since we are running in PHP, we'll try to trigger it.
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen('start /B php artisan app:video-worker --daemon', 'r'));
        } else {
            exec('php artisan app:video-worker --daemon > /dev/null 2>&1 &');
        }

        $this->info('Restart command sent.');
    }
}
