<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Exception;

class DownloadVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $video;
    public $timeout = 3600; // 1 hour timeout for large videos

    /**
     * Create a new job instance.
     */
    public function __construct(Video $video)
    {
        $this->video = $video;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Skip if already downloaded or currently downloading
        if ($this->video->download_status === 'completed' || $this->video->download_status === 'downloading') {
            return;
        }

        $this->video->update(['download_status' => 'downloading']);

        try {
            $url = $this->video->url;
            
            // Skip if the URL is already internal/local
            if (str_starts_with($url, '/storage') || str_contains($url, request()->getHost())) {
                 $this->video->update(['download_status' => 'completed']);
                 return;
            }

            $urlParts = parse_url($url);
            $host = $urlParts['host'] ?? 'cdn.videy.co';
            
            // ISP BYPASS: Use hardcoded IP for Videy to circumvent DNS/SNI hijacking
            $ip = ($host === 'videy.co' || $host === 'cdn.videy.co') ? '172.67.73.18' : gethostbyname($host);

            $filename = $this->video->slug . '.mp4';
            $storagePath = 'videos/' . $filename;
            $fullPath = storage_path('app/public/' . $storagePath);

            // Ensure directory exists
            if (!file_exists(storage_path('app/public/videos'))) {
                mkdir(storage_path('app/public/videos'), 0755, true);
            }

            $fp = fopen($fullPath, 'w+');

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36',
                'Referer: https://videy.co/',
            ]);
            curl_setopt($ch, CURLOPT_RESOLVE, ["$host:443:$ip"]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3600); // 1 hour max

            // Progress tracking
            curl_setopt($ch, CURLOPT_NOPROGRESS, false);
            curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($resource, $download_size, $downloaded, $upload_size, $uploaded) {
                if ($download_size > 0) {
                    $percent = round(($downloaded / $download_size) * 100);
                    static $lastPercent = 0;
                    // Update cache every 2% to avoid overhead
                    if ($percent - $lastPercent >= 2 || $percent == 100) {
                        \Illuminate\Support\Facades\Cache::put('video_download_' . $this->video->id, $percent, 120);
                        $lastPercent = $percent;
                    }
                }
            });

            $success = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                fclose($fp);
                throw new Exception("cURL Error: $error");
            }
            
            curl_close($ch);
            fclose($fp);

            if ($httpCode !== 200 && $httpCode !== 206) {
                unlink($fullPath);
                throw new Exception("HTTP Error: $httpCode");
            }

            $this->video->update([
                'download_status' => 'completed',
                'local_path' => $storagePath,
            ]);

            \App\Helpers\VideoHelper::generateThumbnail($this->video, $fullPath);

            // Auto-chain: mirroring to mirrors after successful download
            \App\Jobs\DistributeToHostJob::dispatch($this->video, 'streamtape');
            \App\Jobs\DistributeToHostJob::dispatch($this->video, 'doodstream');

            \Illuminate\Support\Facades\Cache::forget('video_download_' . $this->video->id);

        } catch (Exception $e) {
            $this->video->update(['download_status' => 'failed']);
            \Illuminate\Support\Facades\Cache::forget('video_download_' . $this->video->id);
            throw $e;
        }
    }
}
