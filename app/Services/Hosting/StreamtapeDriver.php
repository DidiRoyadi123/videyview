<?php

namespace App\Services\Hosting;

use App\Models\Video;
use App\Helpers\VideoHelper;
use Illuminate\Support\Facades\Log;

class StreamtapeDriver extends BaseDriver
{
    protected string $login;
    protected string $key;
    protected string $baseUrl = 'https://api.streamtape.to';

    public function __construct(string $login, string $key)
    {
        $this->login = $login;
        $this->key   = $key;
    }

    protected function getApiBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getIdentifier(): string
    {
        return 'streamtape';
    }

    public function upload(Video $video): array
    {
        // 1. Get upload URL from Streamtape API
        $response = $this->http()->get("{$this->baseUrl}/file/ul", [
            'login' => $this->login,
            'key'   => $this->key,
        ]);

        if (!$response->successful() || !isset($response->json()['result']['url'])) {
            $msg = $response->json()['msg'] ?? $response->body();
            throw new \Exception("Streamtape get upload URL failed: " . $msg);
        }

        $uploadUrl = $response->json()['result']['url'];
        Log::info("Streamtape: upload URL obtained", ['url' => $uploadUrl, 'video' => $video->slug]);

        // 2. Resolve the correct video path
        $videoPath = VideoHelper::getVideoPath($video->slug);
        if (!file_exists($videoPath)) {
            throw new \Exception("Local video file missing: {$videoPath}");
        }

        // 3. POST multipart to the tapecontent.net upload server (ISP bypass applied)
        $uploadResponse = $this->http($uploadUrl)->withOptions([
            'progress' => function ($downloadTotal, $downloadedBytes, $uploadTotal, $uploadedBytes) use ($video) {
                if ($uploadTotal > 0) {
                    $percent = min(100, round(($uploadedBytes / $uploadTotal) * 100));
                    \Illuminate\Support\Facades\Cache::put('video_upload_' . $video->id, $percent, 60);
                }
            }
        ])->attach(
            'file1', fopen($videoPath, 'r'), basename($videoPath)
        )->post($uploadUrl);

        Log::info("Streamtape: upload response", [
            'status' => $uploadResponse->status(),
            'body'   => substr($uploadResponse->body(), 0, 300),
        ]);

        if (!$uploadResponse->successful()) {
            $msg = $uploadResponse->json()['msg'] ?? $uploadResponse->body();
            throw new \Exception("Streamtape upload failed [{$uploadResponse->status()}]: " . $msg);
        }

        $result = $uploadResponse->json();

        // Status 200 with result null means file already there or server issue
        if (($result['status'] ?? 0) !== 200 || empty($result['result']['id'])) {
            $msg = $result['msg'] ?? 'No file ID returned';
            throw new \Exception("Streamtape upload error: " . $msg);
        }

        $fileId = $result['result']['id'];

        return [
            'file_id'      => $fileId,
            'embed_url'    => "https://streamtape.com/e/{$fileId}",
            'download_url' => null,
        ];
    }

    public function remoteUpload(Video $video, string $remoteUrl): array
    {
        $response = $this->http()->get("{$this->baseUrl}/remotedl/add", [
            'login' => $this->login,
            'key'   => $this->key,
            'url'   => $remoteUrl
        ]);

        if (!$response->successful() || !isset($response->json()['result']['id'])) {
            $msg = $response->json()['msg'] ?? $response->body();
            throw new \Exception("Streamtape remote upload failed: " . $msg);
        }

        $result = $response->json()['result'];
        
        Log::info("Streamtape: Dispatched remote upload", ['remote_id' => $result['id'], 'video' => $video->slug]);

        return [
            'remote_id' => $result['id'],
            'folderid'  => $result['folderid'] ?? null
        ];
    }

    public function checkRemoteStatus(string $remoteId): array
    {
        $response = $this->http()->get("{$this->baseUrl}/remotedl/status", [
            'login' => $this->login,
            'key'   => $this->key,
            'id'    => $remoteId
        ]);

        if (!$response->successful()) {
            return ['status' => 'error', 'msg' => 'HTTP ' . $response->status()];
        }

        $data = $response->json()['result'][$remoteId] ?? null;
        if (!$data) {
            return ['status' => 'error', 'msg' => 'Remote ID not found in response'];
        }

        return $data;
    }

    public function getStatus(string $fileId): string
    {
        $response = $this->http()->get("{$this->baseUrl}/file/info", [
            'login' => $this->login,
            'key'   => $this->key,
            'file'  => $fileId,
        ]);

        if (!$response->successful()) return 'unknown';

        $result = $response->json()['result'] ?? null;
        if (!$result) return 'not_found';

        return $result[$fileId]['status'] ?? 'ready';
    }

    public function getThumbnailUrl(string $fileId): ?string
    {
        return null;
    }
}
