<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\MultiHostService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CheckVideoHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $video;

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
    public function handle(MultiHostService $hostService): void
    {
        $report = [
            'local' => ['status' => 'unknown', 'message' => 'Not checked'],
            'videy' => ['status' => 'unknown', 'message' => 'Not checked'],
            'streamtape' => ['status' => 'unknown', 'message' => 'Not checked'],
        ];

        // 1. Check Local Storage
        if ($this->video->download_status === 'completed' && $this->video->local_path) {
            if (Storage::disk('public')->exists($this->video->local_path)) {
                $report['local'] = ['status' => 'healthy', 'message' => 'File exists on disk'];
            } else {
                $report['local'] = ['status' => 'missing', 'message' => 'File not found on disk'];
            }
        } else {
            $report['local'] = ['status' => 'na', 'message' => 'No local record'];
        }

        // 2. Check Videy (Main URL)
        if ($this->video->url && (str_contains($this->video->url, 'videy.co') || str_contains($this->video->url, 'cdn.videy.co'))) {
            try {
                // Use ISP Bypass to check health since Videy is often blocked by local ISPs
                $client = new \App\Helpers\IspBypassClient();
                $host = parse_url($this->video->url, PHP_URL_HOST);
                $ip = \App\Helpers\IspBypassClient::getIpForHost($host) ?? '104.21.51.207';
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $this->video->url);
                curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_RESOLVE, ["$host:443:$ip", "$host:80:$ip"]);
                
                curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode >= 200 && $httpCode < 400) {
                    $report['videy'] = ['status' => 'healthy', 'message' => "CDN link alive ($httpCode OK) via ISP Bypass"];
                } else {
                    $report['videy'] = ['status' => 'dead', 'message' => "CDN link returned $httpCode via ISP Bypass"];
                }
            } catch (\Exception $e) {
                $report['videy'] = ['status' => 'error', 'message' => 'Connection failed: ' . $e->getMessage()];
            }
        } else {
            $report['videy'] = ['status' => 'na', 'message' => 'Not a Videy link'];
        }

        // 3. Check Streamtape Mirror
        $streamtapeId = $this->video->mirror_links['streamtape'] ?? null;
        if ($streamtapeId) {
            try {
                $driver = $hostService->driver('streamtape');
                $status = $driver->getStatus($streamtapeId);
                
                if ($status === 'ready' || $status === 'completed' || $status === 'success') {
                    $report['streamtape'] = ['status' => 'healthy', 'message' => 'Mirror link alive'];
                } elseif ($status === 'not_found' || $status === 'deleted' || $status === 'failed') {
                    $report['streamtape'] = ['status' => 'dead', 'message' => 'File deleted or not found'];
                    
                    // AUTO-HEALING: Re-upload if dead and local exists
                    if ($report['local']['status'] === 'healthy') {
                        \App\Jobs\DistributeToHostJob::dispatch($this->video, 'streamtape');
                        $report['streamtape']['message'] .= ' (Auto-healing triggered)';
                    }
                } else {
                    $report['streamtape'] = ['status' => 'warning', 'message' => 'Status: ' . $status];
                }
            } catch (\Exception $e) {
                $report['streamtape'] = ['status' => 'error', 'message' => 'API Error: ' . $e->getMessage()];
            }
        } else {
            $report['streamtape'] = ['status' => 'na', 'message' => 'No mirror link'];
            // OPTIONAL: Self-healing if missing link entirely but record says it should have it
            if ($this->video->hosting_status['streamtape'] ?? null === 'failed' && $report['local']['status'] === 'healthy') {
                 \App\Jobs\DistributeToHostJob::dispatch($this->video, 'streamtape');
            }
        }

        // 4. Check Doodstream Mirror
        $doodId = $this->video->mirror_links['doodstream'] ?? null;
        if ($doodId) {
            try {
                $driver = $hostService->driver('doodstream');
                $status = $driver->getStatus($doodId);
                
                if ($status === 'success' || $status === 'ready') {
                    $report['doodstream'] = ['status' => 'healthy', 'message' => 'Mirror link alive'];
                } elseif ($status === 'failed' || $status === 'deleted') {
                    $report['doodstream'] = ['status' => 'dead', 'message' => 'File dead on Doodstream'];
                    
                    // AUTO-HEALING: Re-upload if dead and local exists
                    if ($report['local']['status'] === 'healthy') {
                        \App\Jobs\DistributeToHostJob::dispatch($this->video, 'doodstream');
                        $report['doodstream']['message'] .= ' (Auto-healing triggered)';
                    }
                } else {
                    $report['doodstream'] = ['status' => 'warning', 'message' => 'Status: ' . $status];
                }
            } catch (\Exception $e) {
                $report['doodstream'] = ['status' => 'error', 'message' => 'API Error: ' . $e->getMessage()];
            }
        } else {
            $report['doodstream'] = ['status' => 'na', 'message' => 'No mirror link'];
        }

        // Update Video Model
        $this->video->update([
            'last_check_at' => now(),
            'health_report' => $report,
        ]);

        Log::info("Health check & Auto-healing audit completed for video {$this->video->slug}");
    }
}
