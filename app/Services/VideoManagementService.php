<?php

namespace App\Services;

use App\Models\Video;
use App\Jobs\DistributeToHostJob;
use App\Jobs\DistributeRemoteHostJob;
use App\Jobs\DownloadVideoJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class VideoManagementService
{
    public function storeVideo(array $data)
    {
        $title = $data['title'] ?? null;
        $is_premium = $data['is_premium'] ?? true;
        $category_id = $data['category_id'] ?? null;
        $skip_download = $data['skip_download'] ?? false;
        $slug = Str::slug($title ?: 'video-' . Str::random(5)) . '-' . Str::random(5);

        if (isset($data['video_file'])) {
            $file = $data['video_file'];
            $filename = $slug . '.' . $file->getClientOriginalExtension();
            
            if (!$title) {
                $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            }

            $path = $file->storeAs('videos', $filename, 'public');
            $video = Video::create([
                'title' => $title,
                'slug' => $slug,
                'url' => asset('storage/' . $path),
                'local_path' => $path,
                'download_status' => 'completed',
                'is_premium' => $is_premium,
                'category_id' => $category_id,
            ]);

            DistributeToHostJob::dispatch($video, 'streamtape');
            DistributeToHostJob::dispatch($video, 'videy');
        } else {
            $url = $data['url'];
            if (!$title) {
                $filename = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME);
                $title = 'Video ' . ($filename ?: Str::random(8));
            }

            $video = Video::create([
                'title' => $title,
                'slug' => $slug,
                'url' => $url,
                'download_status' => $skip_download ? 'completed' : null,
                'is_premium' => $is_premium,
                'category_id' => $category_id,
            ]);

            if (!$skip_download) {
                DownloadVideoJob::dispatch($video);
                $video->update(['download_status' => 'pending']);
            }
        }

        $this->applyAutoFreeLogic($video);
        return $video;
    }

    public function updateVideo(Video $video, array $data)
    {
        $video->update($data);
        Cache::forget("video_show_{$video->slug}");
        return $video;
    }

    public function deleteVideo(Video $video)
    {
        if ($video->download_status === 'completed' && $video->local_path) {
            $fileToDelete = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('app/public/' . $video->local_path));
            if (is_file($fileToDelete)) {
                unlink($fileToDelete);
            }

            $thumbSlug = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('app/public/thumbnails/' . $video->slug . '.jpg'));
            $thumbId = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('app/public/thumbnails/' . $video->id . '.jpg'));
            
            if (is_file($thumbSlug)) unlink($thumbSlug);
            if (is_file($thumbId)) unlink($thumbId);
        }

        $video->delete();
        Cache::forget("video_show_{$video->slug}");
        Cache::forget('admin_stats');
    }

    public function createVideoFromData($data)
    {
        $url = is_array($data) ? ($data['url'] ?? $data[0] ?? null) : $data;
        
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        if (Video::where('url', $url)->exists()) {
            return null;
        }

        $title = is_array($data) ? ($data['title'] ?? null) : null;
        if (!$title) {
            $filename = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME);
            $title = 'Video ' . ($filename ?: Str::random(8));
        }

        $is_premium = is_array($data) ? ($data['is_premium'] ?? true) : true;
        $thumbnail_url = is_array($data) ? ($data['thumbnail_url'] ?? null) : null;

        $video = Video::create([
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(5),
            'url' => $url,
            'thumbnail_url' => $thumbnail_url,
            'is_premium' => filter_var($is_premium, FILTER_VALIDATE_BOOLEAN),
        ]);

        if (Str::contains($video->url, 'videy.co')) {
            $video->update(['download_status' => 'pending']);
            DistributeRemoteHostJob::dispatch($video, 'streamtape', $video->url);
        }

        $this->applyAutoFreeLogic($video);
        return $video;
    }

    public function applyAutoFreeLogic(Video $video)
    {
        $totalVideos = Video::count();
        if ($totalVideos % 10 === 0) {
            $video->update(['is_free_to_all' => true, 'is_premium' => false]);
        }
    }
}
