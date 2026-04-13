<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Video;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        if (!Auth::user()->is_admin) {
            return redirect()->route('home');
        }

        $stats = \Illuminate\Support\Facades\Cache::remember('admin_stats', 300, function () {
            return [
                'total_videos' => Video::count(),
                'total_users' => User::count(),
                'total_premium_users' => User::whereHas('activeSubscription')->count(),
                'total_views' => Video::sum('views'),
                'total_comments' => Comment::count(),
                'total_local' => Video::where('download_status', 'completed')->count(),
                'total_mirror_success' => Video::where('hosting_status', 'like', '%"success"%')->count(),
                'host_stats' => [
                    'streamtape' => Video::where('hosting_status', 'like', '%"streamtape":"success"%')->count(),
                    'doodstream' => Video::where('hosting_status', 'like', '%"doodstream":"success"%')->count(),
                ],
                'recent_videos' => Video::latest()->select('id', 'title', 'slug', 'hosting_status', 'created_at')->take(5)->get(),
            ];
        });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
        ]);
    }
}
