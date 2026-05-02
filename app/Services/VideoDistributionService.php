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

    public function bulkSyncLinks(string $content, string $progressId = null)
    {
        set_time_limit(0); // Mencegah 60 seconds exceeded untuk list puluhan ribu link

        $lines = explode("\n", $content);
        $totalItems = count($lines);
        $currentMarker = null;
        $matchedCount = 0;
        $multiHost = app(MultiHostService::class);
        $cacheKey = $progressId ? "bulksync_progress_{$progressId}" : null;

        if ($cacheKey) {
            \Illuminate\Support\Facades\Cache::put($cacheKey, ['current' => 0, 'total' => $totalItems], 300);
        }

        // Performance Optimization: Cache all slugs in memory to avoid O(N) reverse LIKE queries
        $allSlugs = Video::pluck('slug', 'id')->toArray();
        // Sort by length desc to match longest slug first (most specific)
        arsort($allSlugs);

        foreach ($lines as $index => $line) {
            $line = trim($line);
            
            if ($cacheKey && $index % 20 === 0) {
                \Illuminate\Support\Facades\Cache::put($cacheKey, ['current' => $index, 'total' => $totalItems], 300);
            }

            if (empty($line)) continue;

            if (preg_match('/streamtape\.[a-z]+\/e\/([a-z0-9]+)\/([^\s\n\r]+)/i', $line, $matches)) {
                $fileCode = $matches[1];
                $filename = $matches[2];
                $cleanName = strtolower(str_ireplace('.mp4', '', $filename));
                
                $video = null;
                foreach ($allSlugs as $id => $slug) {
                    if (str_contains($cleanName, strtolower($slug))) {
                        $video = Video::find($id);
                        break;
                    }
                }

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
                $currentMarker = strtolower($currentMarker);
                $video = null;
                
                foreach ($allSlugs as $id => $slug) {
                    $slug = strtolower($slug);
                    if (str_contains($slug, $currentMarker) || str_contains($currentMarker, $slug)) {
                        $video = Video::find($id);
                        break;
                    }
                }

                if ($video) {
                    $host = str_contains($line, 'streamtape') ? 'streamtape' : 'doodstream';
                    
                    // LAKUKAN PENGECEKAN APAKAH ADA KESAMAAN JIKA SAMA ABAIKAN
                    $existingLinks = is_array($video->mirror_links) ? $video->mirror_links : [];
                    if (!isset($existingLinks[$host]) || $existingLinks[$host] !== $line) {
                        $multiHost->updateStatus($video, $host, 'success', $line);
                        $matchedCount++;
                    }
                }
                $currentMarker = null;
            }
        }
        
        if ($cacheKey) {
            \Illuminate\Support\Facades\Cache::put($cacheKey, ['current' => $totalItems, 'total' => $totalItems], 300);
        }
        
        return $matchedCount;
    }
}
