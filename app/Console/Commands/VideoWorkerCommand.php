<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Video;
use App\Helpers\VideoHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class VideoWorkerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:video-worker {--daemon : Keep the worker running}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unified background worker for Video Downloading, Thumbnailing, and Mirroring';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 VideyView Worker Started...');
        
        do {
            try {
                \Illuminate\Support\Facades\Cache::put('video_worker_heartbeat', now(), 120);
                
                $this->processPendingDownloads();
                $this->processPendingMirrors();
                
                if ($this->option('daemon')) {
                    $this->info('   Waiting for new tasks (3s)...');
                    sleep(3);
                }
            } catch (\Exception $e) {
                $this->error("🔥 Worker Loop Error: " . $e->getMessage());
                sleep(10); // Cool down on error
            }
        } while ($this->option('daemon'));

        \Illuminate\Support\Facades\Cache::forget('video_worker_heartbeat');
        $this->info('🏁 Worker Finished.');
    }

    protected function processPendingDownloads()
    {
        /** @var \Illuminate\Database\Eloquent\Collection<Video> $videos */
        $videos = Video::where(function($q) {
            $q->whereNull('download_status')
              ->orWhere('download_status', 'pending')
              ->orWhere('download_status', 'downloading'); // Resume/Retry
        })->whereNotNull('url')->take(5)->get();

        if ($videos->isEmpty()) return;

        foreach ($videos as $video) {
            // Skip and mark completed if URL is already a local path
            if (str_starts_with($video->url, '/') || str_starts_with($video->url, 'storage/')) {
                $video->update(['download_status' => 'completed']);
                $this->info("   ⏩ Skipping local path for: {$video->slug}");
                continue;
            }

            $this->info("⏬ Downloading: {$video->title}");
            $this->downloadVideo($video);
        }
    }

    protected function downloadVideo($video)
    {
        // Final validation before attempt
        if (!filter_var($video->url, FILTER_VALIDATE_URL)) {
            $video->update(['download_status' => 'failed']);
            $this->error("   ❌ Invalid URL: {$video->url}");
            return;
        }

        $video->update(['download_status' => 'downloading']);
        
        $filename = "video-{$video->slug}.mp4";
        $path = "videos/{$filename}";
        $fullPath = storage_path("app/public/{$path}");

        // Direct Download with ISP Bypass and UA
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $video->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 3600); // 1 hour
        
        // ISP BYPASS (Only for videy)
        if (str_contains($video->url, 'videy.co')) {
            $host = 'cdn.videy.co';
            if (!str_contains($video->url, 'cdn.')) {
                $host = 'videy.co';
            }
            $ip = \App\Helpers\IspBypassClient::getIpForHost($host) ?? '172.67.73.18';
            curl_setopt($ch, CURLOPT_RESOLVE, ["{$host}:443:{$ip}", "{$host}:80:{$ip}"]);
        }

        curl_setopt($ch, CURLOPT_LOW_SPEED_LIMIT, 1024); // 1KB/s
        curl_setopt($ch, CURLOPT_LOW_SPEED_TIME, 30);    // for 30 seconds
        
        $fp = fopen($fullPath, 'w+');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        
        // Progress callback
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($resource, $download_size, $downloaded) use ($video) {
            if ($download_size > 0 && ($downloaded / $download_size * 100) % 10 == 0) {
                // Update progress every 10%
                \Illuminate\Support\Facades\Cache::put("download_progress_{$video->id}", round($downloaded / $download_size * 100), 60);
            }
        });

        curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($status == 200 && filesize($fullPath) > 0) {
            $video->update([
                'download_status' => 'completed',
                'local_path' => $path
            ]);
            $this->info("✅ Downloaded: {$video->slug}");
            
            // Generate Thumbnail
            $this->generateThumbnail($video);
        } else {
            $video->update(['download_status' => 'failed']);
            $this->error("❌ Failed Download: {$video->slug} (HTTP {$status})");
            if (file_exists($fullPath)) unlink($fullPath);
        }
    }

    protected function generateThumbnail($video)
    {
        $video_path = storage_path("app/public/{$video->local_path}");
        $thumb_path = storage_path("app/public/thumbnails/{$video->slug}.jpg");
        
        if (!file_exists(dirname($thumb_path))) mkdir(dirname($thumb_path), 0755, true);

        $ffmpeg = base_path('bin/ffmpeg.exe');
        if (!file_exists($ffmpeg)) $ffmpeg = 'ffmpeg';

        $process = new Process([
            $ffmpeg, '-y', '-ss', '0.1', '-i', $video_path, '-vframes', '1', '-q:v', '2', $thumb_path
        ]);

        $process->run();

        if ($process->isSuccessful()) {
            $this->info("🖼️ Thumbnail Generated: {$video->slug}");
        } else {
            $this->warn("⚠️ Thumbnail Failed: " . $process->getErrorOutput());
        }
    }

    protected function processPendingMirrors()
    {
        /** @var \Illuminate\Database\Eloquent\Collection<Video> $videos */
        $videos = Video::where('download_status', 'completed')
            ->where(function($q) {
                $q->whereNull('hosting_status')
                  ->orWhere('hosting_status', 'not like', '%"success"%');
            })->take(5)->get();

        if ($videos->isEmpty()) return;

        $multiHost = app(\App\Services\MultiHostService::class);

        foreach ($videos as $video) {
            $this->info("📤 Mirroring: {$video->title}");
            // For now, prioritize Streamtape via Dispatch
            \App\Jobs\DistributeToHostJob::dispatch($video, 'streamtape');
            
            // Note: In worker-command mode, we might want to do it synchronously 
            // but dispatching is safer for logging.
        }
    }
}
