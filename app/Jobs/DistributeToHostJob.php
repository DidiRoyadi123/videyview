<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\MultiHostService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DistributeToHostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 600];

    protected $video;
    protected $host;

    /**
     * Create a new job instance.
     */
    public function __construct(Video $video, string $host)
    {
        $this->video = $video;
        $this->host = $host;
    }

    /**
     * Execute the job.
     */
    public function handle(MultiHostService $service): void
    {
        $driver = $service->driver($this->host);
        if (!$driver) {
            Log::error("MultiHost: Driver not found for {$this->host}");
            return;
        }

        try {
            $service->updateStatus($this->video, $this->host, 'uploading');

            // SMART MIRRORING: Prefer Remote Upload if source URL is available (faster, saves bandwidth)
            if (!empty($this->video->url) && filter_var($this->video->url, FILTER_VALIDATE_URL)) {
                if (method_exists($driver, 'remoteUpload')) {
                    try {
                        Log::info("MultiHost: Attempting remote upload for {$this->video->slug} to {$this->host}");
                        $remoteResult = $driver->remoteUpload($this->video, $this->video->url);
                        
                        if (isset($remoteResult['remote_id'])) {
                            $service->updateStatus($this->video, $this->host, 'remote_processing');
                            \App\Jobs\CheckRemoteUploadJob::dispatch($this->video, $this->host, $remoteResult['remote_id']);
                            return; // Exit handle, the CheckRemoteUploadJob will take over
                        }
                    } catch (\Exception $remoteEx) {
                        Log::warning("MultiHost: Remote upload failed for {$this->video->slug}, falling back to local upload: " . $remoteEx->getMessage());
                    }
                }
            }

            // FALLBACK: Traditional local file upload
            $result = $driver->upload($this->video);

            $service->updateStatus($this->video, $this->host, 'success', $result);

            // Trigger thumbnail caching if available (guard against missing file_id)
            $fileId = $result['file_id'] ?? null;
            if ($fileId) {
                $thumbUrl = $driver->getThumbnailUrl($fileId);
                if ($thumbUrl) {
                    \App\Jobs\CacheThumbnailJob::dispatch($this->video, $thumbUrl);
                }
            }

            Log::info("MultiHost: Successfully distributed video {$this->video->slug} to {$this->host}");

        } catch (\Exception $e) {
            $msg = $e->getMessage();

            // [SKIP] prefix = permanent failure (ISP block, file not found, etc.)
            // Don't retry — mark as skipped immediately.
            if (str_starts_with($msg, '[SKIP]')) {
                $service->updateStatus($this->video, $this->host, 'skipped', ['error' => $msg]);
                Log::warning("MultiHost: Skipped {$this->host} for {$this->video->slug}: $msg");
                $this->fail($e); // stops retries
                return;
            }

            $service->updateStatus($this->video, $this->host, 'failed', ['error' => $msg]);
            Log::error("MultiHost: Failed to distribute video {$this->video->slug} to {$this->host}: $msg");
            throw $e;
        }
    }
}
