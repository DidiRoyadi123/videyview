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
            $path1 = "thumbnails/video-{$this->video->slug}.jpg";
            $path2 = "thumbnails/{$this->video->slug}.jpg";
            
            if (Storage::disk('public')->exists($path1) || Storage::disk('public')->exists($path2)) {
                Log::info("MultiHost: Thumbnail already exists locally for {$this->video->slug}. Skipping download.");
                return;
            }

            $response = Http::get($this->url);
            
            if ($response->successful()) {
                Storage::disk('public')->put($path2, $response->body());
                $this->video->update(['thumbnail_url' => '/storage/' . $path2]);
                
                Log::info("MultiHost: Cached thumbnail for video {$this->video->slug} from {$this->url}");
            }
        } catch (\Exception $e) {
            Log::error("MultiHost: Failed to cache thumbnail for video {$this->video->slug}: " . $e->getMessage());
        }
    }
}
