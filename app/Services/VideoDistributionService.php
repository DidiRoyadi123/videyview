<?php

namespace App\Services;

use App\Models\Video;
use App\Jobs\DistributeToHostJob;
use App\Services\MultiHostService;
use Illuminate\Support\Str;

class VideoDistributionService
{
    public function distribute(Video $video, $host = null)
    {
        $service = app(MultiHostService::class);
        $hosts = $host ? [$host] : array_keys($service->allDrivers());

        foreach ($hosts as $h) {
            DistributeToHostJob::dispatch($video, $h);
        }
    }

    public function mirrorSelected(array $ids, $host = null)
    {
        $service = app(MultiHostService::class);
        $hosts = $host ? [$host] : array_keys($service->allDrivers());
        $videos = Video::whereIn('id', $ids)->where('download_status', 'completed')->get();

        $count = 0;
        foreach ($videos as $index => $video) {
            foreach ($hosts as $h) {
                if ($h === 'videy' && !$host) continue;
                DistributeToHostJob::dispatch($video, $h)->delay(now()->addSeconds($index * 3));
                $count++;
            }
        }
        return $count;
    }

    public function bulkSyncLinks(string $content)
    {
        $lines = explode("\n", $content);
        $currentMarker = null;
        $matchedCount = 0;
        $multiHost = app(MultiHostService::class);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (preg_match('/streamtape\.[a-z]+\/e\/([a-z0-9]+)\/([^\s\n\r]+)/i', $line, $matches)) {
                $fileCode = $matches[1];
                $filename = $matches[2];
                $cleanName = str_ireplace('.mp4', '', $filename);
                
                $video = Video::whereRaw('? LIKE CONCAT("%", slug, "%")', [$cleanName])
                    ->orderByRaw('LENGTH(slug) DESC')
                    ->first();

                if ($video) {
                    $embedUrl = "https://streamtape.to/e/{$fileCode}/{$filename}";
                    $multiHost->updateStatus($video, 'streamtape', 'success', $embedUrl);
                    $matchedCount++;
                }
                $currentMarker = null;
                continue;
            }

            if (preg_match('/<!--\s*(?:video-)?([a-z0-9-]+)\s*-->/i', $line, $matches)) {
                $currentMarker = $matches[1];
                continue;
            }

            if ($currentMarker && filter_var($line, FILTER_VALIDATE_URL)) {
                $video = Video::whereRaw('LOWER(slug) LIKE ?', ["%$currentMarker%"])
                    ->orWhereRaw('LOWER(url) LIKE ?', ["%$currentMarker%"])
                    ->first();

                if ($video) {
                    $host = str_contains($line, 'streamtape') ? 'streamtape' : 'doodstream';
                    $multiHost->updateStatus($video, $host, 'success', $line);
                    $matchedCount++;
                }
                $currentMarker = null;
            }
        }
        return $matchedCount;
    }
}
