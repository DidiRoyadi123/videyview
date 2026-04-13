<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\MultiHostService;
use App\Services\Hosting\StreamtapeDriver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckRemoteUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 200; // Check up to 200 times (~50 minutes max wait)
    public $maxExceptions = 3;

    protected $video;
    protected $host;
    protected $remoteId;

    public function __construct(Video $video, string $host, string $remoteId)
    {
        $this->video = $video;
        $this->host = $host;
        $this->remoteId = $remoteId;
    }

    public function handle(MultiHostService $service): void
    {
        $driver = $service->driver($this->host);
        
        if (!$driver || !method_exists($driver, 'checkRemoteStatus')) {
            Log::error("CheckRemoteUploadJob: host {$this->host} does not support remote status checking.");
            return;
        }

        try {
            $statusData = $driver->checkRemoteStatus($this->remoteId);
            
            if (isset($statusData['status']) && $statusData['status'] === 'error') {
                $service->updateStatus($this->video, $this->host, 'failed', ['error' => $statusData['msg'] ?? 'Unknown API error']);
                Log::error("Remote Upload API Error for {$this->video->slug}: " . ($statusData['msg'] ?? 'Unknown'));
                return; // Stop retries
            }

            // Write progress to cache for dashboard to fetch
            if (isset($statusData['bytes_total']) && $statusData['bytes_total'] > 0) {
                if (isset($statusData['bytes_loaded'])) {
                    $percent = min(100, round(($statusData['bytes_loaded'] / $statusData['bytes_total']) * 100));
                    \Illuminate\Support\Facades\Cache::put('video_upload_' . $this->video->id, $percent, 60);
                }
            }

            $state = strtolower($statusData['status'] ?? 'unknown');
            $fileId = $statusData['extid'] ?? null;

            if ($state === 'finished' || $state === 'downloaded' || $state === 'completed' || $fileId) {
                if (!$fileId) {
                    throw new \Exception("Remote upload finished but no extid found.");
                }

                $result = [
                    'file_id' => $fileId,
                    'embed_url' => "https://streamtape.com/e/{$fileId}",
                ];

                $service->updateStatus($this->video, $this->host, 'success', $result);
                Log::info("Remote upload successfully finished for {$this->video->slug} to {$this->host}");
                
                \Illuminate\Support\Facades\Cache::put('video_upload_' . $this->video->id, 100, 60);

                // Optional: trigger thumbnail indexing if Streamtape has it
                if ($this->host === 'streamtape') {
                    $thumbUrl = $driver->getThumbnailUrl($fileId);
                    if ($thumbUrl) {
                        \App\Jobs\CacheThumbnailJob::dispatch($this->video, $thumbUrl);
                    }
                }
                
                return;
            }

            if ($state === 'error' || $state === 'failed') {
                $service->updateStatus($this->video, $this->host, 'failed', ['error' => 'Remote upload failed remotely']);
                return;
            }

            // If still downloading or new, delay and check again
            $this->release(15);
            
        } catch (\Exception $e) {
            Log::error("CheckRemoteUploadJob exception for {$this->video->slug}: " . $e->getMessage());
            $this->release(30);
        }
    }
}
