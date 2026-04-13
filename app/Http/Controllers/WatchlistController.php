<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\Watchlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WatchlistController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $videos = Video::whereHas('watchlists', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->latest()
            ->paginate(12);

        return Inertia::render('Watchlist/Index', [
            'videos' => $videos
        ]);
    }

    public function toggle(Video $video)
    {
        $user = Auth::user();

        $existing = Watchlist::where('video_id', $video->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $status = 'removed from';
        } else {
            Watchlist::create([
                'video_id' => $video->id,
                'user_id' => $user->id
            ]);
            $status = 'added to';
        }

        return back()->with('success', "Video $status your watchlist.");
    }
}
