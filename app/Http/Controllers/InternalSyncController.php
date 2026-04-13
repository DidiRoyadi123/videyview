<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InternalSyncController extends Controller
{
    /**
     * Sync video status from an external/local sync script.
     * This API is protected by a secret key and is exempt from CSRF.
     */
    public function syncVideo(Request $request)
    {
        $apiKey = config('app.internal_sync_key');
        
        // Security Check
        if (!$apiKey || $request->header('X-Internal-Sync-Key') !== $apiKey) {
            Log::warning('Unauthorized internal sync attempt from ' . $request->ip());
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'slug' => 'required|string',
            'local_path' => 'nullable|string',
            'download_status' => 'required|string', // pending, downloading, completed, failed
        ]);

        $video = Video::where('slug', $request->slug)->first();

        if (!$video) {
            return response()->json(['error' => 'Video not found'], 404);
        }

        // Update video record
        $updateData = [
            'download_status' => $request->download_status,
        ];

        if ($request->local_path) {
            $updateData['local_path'] = $request->local_path;
            // Also update the public URL and thumbnail path
            $updateData['url'] = '/storage/' . $request->local_path;
            $updateData['thumbnail_url'] = '/storage/thumbnails/' . $request->slug . '.jpg';
        }

        $video->update($updateData);

        // Flow 2: Auto-Mirroring trigger
        // If download is completed via sync.py, automatically trigger mirroring to Streamtape
        if ($request->download_status === 'completed') {
            $videoPath = storage_path('app/public/' . ($request->local_path ?: $video->local_path));
            $thumbPath = storage_path('app/public/thumbnails/' . $video->slug . '.jpg');
            
            if (file_exists($videoPath) && file_exists($thumbPath)) {
                Log::info("Flow 2 Local verification passed for {$video->slug}. Mirroring to multi-hosts.");
                \App\Jobs\DistributeToHostJob::dispatch($video, 'streamtape');
                \App\Jobs\DistributeToHostJob::dispatch($video, 'doodstream');
            } else {
                Log::warning("Flow 2 Mirroring aborted. Missing local file or thumbnail for {$video->slug}");
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Video '{$video->slug}' updated successfully.",
            'id' => $video->id
        ]);
    }

    /**
     * Sync download progress percentage.
     */
    public function syncProgress(Request $request)
    {
        $apiKey = config('app.internal_sync_key');
        if (!$apiKey || $request->header('X-Internal-Sync-Key') !== $apiKey) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'slug' => 'required|string',
            'progress' => 'required|numeric|min:0|max:100',
        ]);

        $video = Video::where('slug', $request->slug)->first();
        if ($video) {
            \Illuminate\Support\Facades\Cache::put('video_download_' . $video->id, (int)$request->progress, 60);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Sync mirror links and status from an external/standalone uploader script.
     */
    public function syncMirror(Request $request)
    {
        $apiKey = config('app.internal_sync_key');
        if (!$apiKey || $request->header('X-Internal-Sync-Key') !== $apiKey) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'slug' => 'required|string',
            'host' => 'required|string', // streamtape, doodstream
            'status' => 'required|string', // success, failed, processing
            'mirror_link' => 'nullable|string',
        ]);

        // Performance Optimization: 
        // 1. Extend execution time for large batch syncs
        set_time_limit(120);

        // 2. Try Exact Slug Match (Direct/Fastest)
        $video = Video::where('slug', $request->slug)->first();
        
        // 3. Try Exact Local Path Match (If slug was actually a filename)
        if (!$video) {
            $video = Video::where('local_path', 'like', '%' . $request->slug . '%')->first();
        }

        // 4. Try Prefix matching (Faster than double wildcard)
        if (!$video) {
            $video = Video::where('slug', 'like', 'video-' . $request->slug . '%')->first();
        }

        if (!$video) {
            return response()->json(['error' => "Video '{$request->slug}' not found after deep search"], 404);
        }

        $multiHost = app(\App\Services\MultiHostService::class);
        $multiHost->updateStatus($video, $request->host, $request->status, $request->mirror_link);

        return response()->json([
            'success' => true,
            'message' => "Mirror status for '{$video->slug}' updated on {$request->host}."
        ]);
    }
}
