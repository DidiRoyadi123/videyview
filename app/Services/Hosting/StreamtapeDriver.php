<?php

namespace App\Services\Hosting;

use App\Models\Video;
use App\Helpers\VideoHelper;
use Illuminate\Support\Facades\Log;

class StreamtapeDriver extends BaseDriver
{
    protected string $login;
    protected string $key;
    protected string $baseUrl = 'https://api.streamtape.com';

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
        $uploadResponse = $this->http($uploadUrl)->attach(
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
