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

        $stats = \Illuminate\Support\Facades\Cache::remember('admin_stats_v2', 300, function () {
            // Basic Stats
            $data = [
                'total_videos' => Video::count(),
                'total_users' => User::count(),
                'total_premium_users' => User::whereHas('activeSubscription')->count(),
                'total_views' => Video::sum('views') ?: 0,
                'total_comments' => Comment::count(),
                'total_local' => Video::where('download_status', 'completed')->count(),
                'total_mirror_success' => Video::where('hosting_status', 'like', '%"success"%')->count(),
                'host_stats' => [
                    'streamtape' => Video::where('hosting_status', 'like', '%"streamtape":"success"%')->count(),
                    'doodstream' => Video::where('hosting_status', 'like', '%"doodstream":"success"%')->count(),
                ],
                'recent_videos' => Video::latest()->select('id', 'title', 'slug', 'hosting_status', 'created_at')->take(5)->get(),
            ];

            // Analytics Logic (Ported from AnalyticsController)
            
            // 1. Mirroring Distribution
            $allHostingStatuses = Video::whereNotNull('hosting_status')->pluck('hosting_status');
            $mirrorStats = ['success' => 0, 'pending' => 0, 'failed' => 0];
            foreach ($allHostingStatuses as $status) {
                if (is_array($status)) {
                    foreach ($status as $hostStatus) {
                        if ($hostStatus === 'success') $mirrorStats['success']++;
                        elseif (in_array($hostStatus, ['pending', 'uploading'])) $mirrorStats['pending']++;
                        elseif (str_starts_with($hostStatus, 'failed')) $mirrorStats['failed']++;
                    }
                }
            }
            $data['mirrorStats'] = $mirrorStats;

            // 2. Health Analysis
            $healthReports = Video::whereNotNull('health_report')->pluck('health_report');
            $healthSummary = ['healthy' => 0, 'down' => 0, 'unknown' => 0];
            foreach ($healthReports as $report) {
                if (is_array($report)) {
                    $isHealthy = true;
                    foreach ($report as $host => $details) {
                        if (($details['status'] ?? '') !== 'alive') {
                            $isHealthy = false;
                            break;
                        }
                    }
                    if ($isHealthy) $healthSummary['healthy']++;
                    else $healthSummary['down']++;
                }
            }
            $data['healthSummary'] = $healthSummary;

            // 3. Top Content
            $data['topVideos'] = Video::orderBy('views', 'desc')
                ->take(10)
                ->get(['id', 'title', 'views', 'slug']);

            return $data;
        });

        $workerActive = \Illuminate\Support\Facades\Cache::has('video_worker_heartbeat');

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'worker_active' => $workerActive,
        ]);
    }
}
