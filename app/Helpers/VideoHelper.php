<?php

namespace App\Helpers;

use App\Models\Video;
use Illuminate\Support\Facades\Log;

class VideoHelper
{
    /**
     * Generate a thumbnail from a video file.
     *
     * @param Video $video
     * @param string $videoFullPath Full system path to the video file.
     * @return string|null
     */
    public static function generateThumbnail(Video $video, string $videoFullPath): ?string
    {
        $ffmpegPath = base_path('bin' . DIRECTORY_SEPARATOR . 'ffmpeg.exe');
        $thumbName = "{$video->slug}.webp";
        $thumbPath = "thumbnails/{$thumbName}";
        
        $absolutePath = storage_path("app/public/{$thumbPath}");
        $videoSource = storage_path("app/public/{$video->local_path}");

        if (!file_exists(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0777, true);
        }

        if (file_exists($absolutePath)) {
            return $thumbPath;
        }

        if (!file_exists($videoSource)) {
            Log::error("Cannot generate thumbnail: source video missing at {$videoSource}");
            return null;
        }

        $videoSourceNormalized = str_replace('\\', '/', $videoSource);
        $absolutePathNormalized = str_replace('\\', '/', $absolutePath);

        // Using WebP with quality 75 for optimal compression/quality ratio
        $command = "\"{$ffmpegPath}\" -ss 00:00:02 -i \"{$videoSourceNormalized}\" -vframes 1 -c:v libwebp -lossless 0 -compression_level 6 -q:v 75 \"{$absolutePathNormalized}\" 2>&1";
        
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        if ($returnVar === 0 && file_exists($absolutePath)) {
            $video->update([
                'thumbnail_url' => '/storage/' . $thumbPath
            ]);
            return $thumbPath;
        }

        Log::error("FFmpeg failed to generate thumbnail for {$video->slug}: " . implode("\n", $output));
        return null;
    }

    /**
     * Get the full system path to a video file, handling prefixes correctly.
     */
    public static function getVideoPath(string $slug): string
    {
        $filename = str_starts_with($slug, 'video-') ? "{$slug}.mp4" : "video-{$slug}.mp4";
        return storage_path("app/public/videos/{$filename}");
    }
}
