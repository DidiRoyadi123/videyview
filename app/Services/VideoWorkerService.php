<?php

namespace App\Services;

use App\Models\Video;
use App\Jobs\DownloadVideoJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class VideoWorkerService
{
    public function syncStorage(Video $video)
    {
        $storageRoot = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('app/public'));
        $path1 = $storageRoot . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'video-' . $video->slug . '.mp4';
        $path2 = $storageRoot . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . $video->slug . '.mp4';

        $foundPath = null;
        if (is_file($path1)) $foundPath = 'videos/video-' . $video->slug . '.mp4';
        elseif (is_file($path2)) $foundPath = 'videos/' . $video->slug . '.mp4';

        if ($foundPath) {
            $video->download_status = 'completed';
            $video->local_path = $foundPath;
            $thumbPath = $storageRoot . DIRECTORY_SEPARATOR . 'thumbnails' . DIRECTORY_SEPARATOR . $video->slug . '.jpg';
            if (is_file($thumbPath)) $video->thumbnail_url = '/storage/thumbnails/' . $video->slug . '.jpg';
            $video->save();
            Cache::forget("video_show_{$video->slug}");
            Cache::forget('admin_stats');
            return true;
        }

        $video->update(['download_status' => null, 'local_path' => null]);
        Cache::forget('admin_stats');
        return false;
    }

    public function syncAllStorage()
    {
        set_time_limit(0);
        $videos = Video::all();
        $synced = 0; $missing = 0;

        $storageRoot = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('app/public'));
        $vDir = $storageRoot . DIRECTORY_SEPARATOR . 'videos';
        $tDir = $storageRoot . DIRECTORY_SEPARATOR . 'thumbnails';
        
        $existingVideos = is_dir($vDir) ? array_flip(scandir($vDir)) : [];
        $existingThumbs = is_dir($tDir) ? array_flip(scandir($tDir)) : [];

        foreach ($videos as $video) {
            $foundPath = null;
            $f1 = str_starts_with($video->slug, 'video-') ? $video->slug . '.mp4' : 'video-' . $video->slug . '.mp4';
            $f2 = str_starts_with($video->slug, 'video-') ? str_replace('video-', '', $video->slug) . '.mp4' : $video->slug . '.mp4';

            if (isset($existingVideos[$f1])) $foundPath = 'videos/' . $f1;
            elseif (isset($existingVideos[$f2])) $foundPath = 'videos/' . $f2;

            if ($foundPath) {
                if ($video->download_status !== 'completed' || $video->local_path !== $foundPath) {
                    $video->download_status = 'completed';
                    $video->local_path = $foundPath;
                }
                $synced++;
            } else {
                if ($video->download_status === 'completed') {
                    $video->download_status = null;
                    $video->local_path = null;
                }
                $missing++;
            }

            $tFile = $video->slug . '.jpg';
            if (isset($existingThumbs[$tFile])) $video->thumbnail_url = '/storage/thumbnails/' . $tFile;

            if ($video->isDirty()) {
                $video->save();
                Cache::forget("video_show_{$video->slug}");
            }
        }

        Cache::forget('admin_stats');
        return ['synced' => $synced, 'missing' => $missing];
    }

    public function getProgressData(array $ids)
    {
        $progress = []; $uploadProgress = []; $mirroring = [];
        $videos = Video::with('category')->whereIn('id', $ids)->get(['id', 'hosting_status']);

        foreach ($ids as $id) {
            $progress[$id] = Cache::get('video_download_' . $id, 0);
            $uploadProgress[$id] = Cache::get('video_upload_' . $id, 0);
            $video = $videos->where('id', $id)->first();
            if ($video) $mirroring[$id] = $video->hosting_status;
        }

        return [
            'progress' => $progress,
            'uploadProgress' => $uploadProgress,
            'mirroring' => $mirroring,
            'global_stats' => Cache::remember('admin_global_worker_stats', 5, function() {
                return $this->getGlobalStats();
            })
        ];
    }

    private function getGlobalStats()
    {
        $activeDownloadIds = Video::where('download_status', 'downloading')->pluck('id');
        $activeMirrorIds = Video::where('hosting_status', 'like', '%"uploading"%')
            ->orWhere('hosting_status', 'like', '%"pending"%')
            ->pluck('id');

        $globalDownloadAvg = $this->calculateAvgProgress($activeDownloadIds, 'video_download_');
        $globalUploadAvg = $this->calculateAvgProgress($activeMirrorIds, 'video_upload_');

        return [
            'total_active_downloads' => $activeDownloadIds->count(),
            'total_active_mirrors' => $activeMirrorIds->count(),
            'total_mirrored' => Video::where('hosting_status', 'like', '%"success"%')->count(),
            'total_local' => Video::where('download_status', 'completed')->count(),
            'total_download_pending' => Video::where(function($q) {
                $q->where('download_status', '!=', 'completed')->orWhereNull('download_status');
            })->whereNotNull('url')->count(),
            'global_download_avg' => $globalDownloadAvg,
            'global_upload_avg' => $globalUploadAvg,
            'host_stats' => $this->calculateHostStats(),
        ];
    }

    private function calculateAvgProgress($ids, $cachePrefix)
    {
        if ($ids->count() === 0) return 0;
        $sum = 0; $countValid = 0;
        foreach ($ids as $id) {
            $val = Cache::get($cachePrefix . $id, 0);
            if ($val > 0) { $sum += $val; $countValid++; }
        }
        return $countValid ? round($sum / $countValid) : 0;
    }

    private function calculateHostStats()
    {
        // Optimize using MySQL JSON extraction if available, fallback to pluck for SQLite/others
        if (config('database.default') === 'mysql') {
            return [
                'streamtape' => Video::where('hosting_status->streamtape', 'success')->count(),
                'doodstream' => Video::where('hosting_status->doodstream', 'success')->count(),
            ];
        }

        $allStatuses = Video::whereNotNull('hosting_status')->pluck('hosting_status');
        $hostStats = ['streamtape' => 0, 'doodstream' => 0];
        foreach ($allStatuses as $status) {
            if (is_array($status)) {
                foreach ($status as $host => $s) {
                    if ($s === 'success' && isset($hostStats[$host])) $hostStats[$host]++;
                }
            }
        }
        return $hostStats;
    }

    public function queueDownload(Video $video)
    {
        DownloadVideoJob::dispatch($video);
    }
}
