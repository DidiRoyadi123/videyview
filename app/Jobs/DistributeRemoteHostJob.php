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

class DistributeRemoteHostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 600];

    protected $video;
    protected $host;
    protected $remoteUrl;

    public function __construct(Video $video, string $host, string $remoteUrl)
    {
        $this->video = $video;
        $this->host = $host;
        $this->remoteUrl = $remoteUrl;
    }

    public function handle(MultiHostService $service): void
    {
        $driver = $service->driver($this->host);
        if (!$driver || !method_exists($driver, 'remoteUpload')) {
            Log::error("MultiHost: Driver not found or remoteUpload not supported for {$this->host}");
            return;
        }

        try {
            $service->updateStatus($this->video, $this->host, 'uploading');

            $result = $driver->remoteUpload($this->video, $this->remoteUrl);

            Log::info("MultiHost: Remote upload mapped for {$this->video->slug} to {$this->host}");

            // Dispatch async checker
            if (isset($result['remote_id'])) {
                CheckRemoteUploadJob::dispatch($this->video, $this->host, $result['remote_id'])
                    ->onQueue('checkers')
                    ->delay(now()->addSeconds(10));
            }

        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $service->updateStatus($this->video, $this->host, 'failed', ['error' => $msg]);
            Log::error("MultiHost: Failed to start remote upload for {$this->video->slug} to {$this->host}: $msg");
            throw $e;
        }
    }
}
