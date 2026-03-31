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
            ];
        });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
        ]);
    }
}
