<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\VideoLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    public function toggle(Video $video, Request $request)
    {
        $request->validate([
            'type' => 'required|in:like,dislike'
        ]);

        $user = Auth::user();
        $isLike = $request->type === 'like';

        $existing = VideoLike::where('video_id', $video->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            if ($existing->is_like === $isLike) {
                // Remove if clicking the same button
                $existing->delete();
                $status = 'removed';
            } else {
                // Switch if clicking the other button
                $existing->update(['is_like' => $isLike]);
                $status = 'switched';
            }
        } else {
            // Create new
            VideoLike::create([
                'video_id' => $video->id,
                'user_id' => $user->id,
                'is_like' => $isLike
            ]);
            $status = 'added';
        }

        return back()->with('success', "Interaction $status.");
    }
}
