<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        // 1. Mirroring Status Distribution
        $allStatuses = Video::whereNotNull('hosting_status')->pluck('hosting_status');
        $mirrorStats = [
            'success' => 0,
            'pending' => 0,
            'failed' => 0,
        ];

        foreach ($allStatuses as $status) {
            foreach ($status as $hostStatus) {
                if ($hostStatus === 'success') $mirrorStats['success']++;
                elseif ($hostStatus === 'pending' || $hostStatus === 'uploading') $mirrorStats['pending']++;
                elseif (str_starts_with($hostStatus, 'failed')) $mirrorStats['failed']++;
            }
        }

        // 2. Host Performance
        $hostStats = [
            'streamtape' => Video::where('hosting_status', 'like', '%"streamtape":"success"%')->count(),
            'doodstream' => Video::where('hosting_status', 'like', '%"doodstream":"success"%')->count(),
        ];

        // 3. Health Report Analysis
        $healthReports = Video::whereNotNull('health_report')->pluck('health_report');
        $healthSummary = [
            'healthy' => 0,
            'down' => 0,
            'unknown' => 0,
        ];

        foreach ($healthReports as $report) {
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

        // 4. Top Videos
        $topVideos = Video::orderBy('views', 'desc')
            ->take(10)
            ->get(['id', 'title', 'views', 'slug']);

        return Inertia::render('Admin/Analytics/Index', [
            'mirrorStats' => $mirrorStats,
            'hostStats' => $hostStats,
            'healthSummary' => $healthSummary,
            'topVideos' => $topVideos,
            'totalVideos' => Video::count(),
        ]);
    }
}
