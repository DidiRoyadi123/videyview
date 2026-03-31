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

        return response()->json([
            'success' => true,
            'message' => "Video '{$video->slug}' updated successfully.",
            'id' => $video->id
        ]);
    }
}
