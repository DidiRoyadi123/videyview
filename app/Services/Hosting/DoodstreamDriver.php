<?php
/**
 * VideyView Premium Hosting Driver: Doodstream (v3.0)
 * Optimized for Indonesian ISP Bypass using direct Cloudflare IP resolving.
 */

namespace App\Services\Hosting;

use App\Models\Video;
use Illuminate\Support\Facades\Log;

class DoodstreamDriver extends BaseDriver
{
    protected string $apiKey;
    protected string $baseUrl = 'https://doodapi.co/api/';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getIdentifier(): string
    {
        return 'doodstream';
    }

    protected function getApiBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Doodstream Remote Upload (Link Copy)
     * Highly efficient for mirroring from sources like Videy CDN.
     */
    public function upload(Video $video): array
    {
        // Prioritize Remote Upload (Link Mirroring)
        $sourceUrl = $video->cdn_url ?: $video->url;

        if (!$sourceUrl) {
            throw new \Exception("No source URL available for Doodstream mirroring.");
        }

        Log::info("Doodstream: Initiating remote upload for video {$video->id} from source: {$sourceUrl}");

        $response = $this->http($this->baseUrl)
            ->get($this->baseUrl . 'upload/url', [
                'key' => $this->apiKey,
                'url' => $sourceUrl,
                'new_title' => $video->title ?? "VideyView_{$video->slug}"
            ]);

        if ($response->failed()) {
            throw new \Exception("Doodstream API connection failed: " . $response->body());
        }

        $data = $response->json();
        
        // Doodstream usually returns the file_code immediately for remote uploads
        if (!isset($data['result'][0]['filecode'])) {
             // Sometimes it's in a different format
             if (isset($data['result']['filecode'])) {
                 $fileCode = $data['result']['filecode'];
             } else {
                 throw new \Exception("Doodstream failed to provide file_code: " . json_encode($data));
             }
        } else {
            $fileCode = $data['result'][0]['filecode'];
        }

        return [
            'file_id' => $fileCode,
            'embed_url' => "https://doodapi.co/e/{$fileCode}",
            'status' => 'success'
        ];
    }

    /**
     * Check if the file is still 'Live' or has been deleted.
     */
    public function getStatus(string $fileId): string
    {
        try {
            $response = $this->http($this->baseUrl)
                ->get($this->baseUrl . 'file/info', [
                    'key' => $this->apiKey,
                    'file_code' => $fileId
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['result'][0]['status']) && $data['result'][0]['status'] === 'OK') {
                    return 'success';
                }
            }
            return 'failed';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    /**
     * Doodstream provides high-quality generated thumbnails.
     */
    public function getThumbnailUrl(string $fileId): ?string
    {
        try {
            $response = $this->http($this->baseUrl)
                ->get($this->baseUrl . 'file/info', [
                    'key' => $this->apiKey,
                    'file_code' => $fileId
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['result'][0]['protected_embed'] ?? null;
            }
        } catch (\Exception $e) {
            // Silently fail thumbnail fetch
        }
        return null;
    }

    /**
     * Check the status of a remote upload on Doodstream.
     */
    public function checkRemoteStatus(string $remoteId): array
    {
        try {
            $response = $this->http($this->baseUrl)
                ->get($this->baseUrl . 'urlupload/status', [
                    'key' => $this->apiKey,
                    'id' => $remoteId
                ]);

            if ($response->successful()) {
                $data = $response->json();
                // Doodstream usually returns an array of results or a single result
                $result = $data['result'][0] ?? $data['result'] ?? null;
                
                if ($result) {
                    // Normalize to common format: success means it has a filecode
                    if (isset($result['filecode']) && $result['filecode']) {
                        return [
                            'status' => 'success',
                            'extid' => $result['filecode'],
                            'remote_id' => $remoteId
                        ];
                    }
                    return [
                        'status' => $result['status'] ?? 'processing',
                        'remote_id' => $remoteId
                    ];
                }
            }
            return ['status' => 'error', 'msg' => 'API failure'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'msg' => $e->getMessage()];
        }
    }
}
