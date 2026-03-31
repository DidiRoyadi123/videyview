<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CacheThumbnailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $video;
    protected $url;

    /**
     * Create a new job instance.
     */
    public function __construct(Video $video, string $url)
    {
        $this->video = $video;
        $this->url = $url;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $response = Http::get($this->url);
            
            if ($response->successful()) {
                $path = "thumbnails/video-{$this->video->slug}.jpg";
                Storage::disk('public')->put($path, $response->body());
                
                Log::info("MultiHost: Cached thumbnail for video {$this->video->slug} from {$this->url}");
            }
        } catch (\Exception $e) {
            Log::error("MultiHost: Failed to cache thumbnail for video {$this->video->slug}: " . $e->getMessage());
        }
    }
}
