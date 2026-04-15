<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Jobs\DistributeToHostJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Models\Category;

class VideoController extends Controller
{
    public function index()
    {
        $videos = Video::with('category')
            ->orderByRaw("CASE WHEN download_status = 'completed' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(10);

        // Append absolute URLs for frontend reliability
        $videos->getCollection()->transform(function ($video) {
            $video->local_url = $video->download_status === 'completed' && $video->local_path 
                ? url('storage/' . $video->local_path) 
                : null;
            
            $video->proxy_url = Str::contains($video->url, 'videy.co')
                ? route('video.proxy', ['url' => $video->url, 'video_id' => $video->id])
                : $video->url;
                
            return $video;
        });

        // Aggregated Host Stats (O(N) but restricted to memory since we use a collection usually, 
        // but for 1,040 we use a raw query for speed)
        // Use PHP aggregation for host stats to avoid MariaDB 10.4 JSON operator incompatibilities
        $allStatuses = Video::whereNotNull('hosting_status')->pluck('hosting_status');
        $hostStats = [
            'streamtape' => 0,
            'doodstream' => 0,
        ];

        foreach ($allStatuses as $status) {
            if (is_array($status)) {
                foreach ($hostStats as $key => $val) {
                    if (($status[$key] ?? null) === 'success') {
                        $hostStats[$key]++;
                    }
                }
            }
        }

        $recentActivity = Video::whereNotNull('mirror_links')
            ->where('mirror_links', '!=', '[]')
            ->latest('updated_at')
            ->take(5)
            ->get(['id', 'title', 'slug', 'hosting_status', 'updated_at']);

        return Inertia::render('Admin/Videos/Index', [
            'videos' => $videos,
            'total_local' => Video::where('download_status', 'completed')->count(),
            'total_pending' => Video::whereNotIn('download_status', ['completed'])
                ->orWhereNull('download_status')
                ->count(),
            'total_download_pending' => Video::where(function($q) {
                    $q->whereNotIn('download_status', ['completed'])
                      ->orWhereNull('download_status');
                })->whereNotNull('url')->count(),
            'total_active_downloads' => Video::where('download_status', 'downloading')->count(),
            'total_active_mirrors' => Video::where('hosting_status', 'like', '%"uploading"%')
                ->orWhere('hosting_status', 'like', '%"pending"%')
                ->count(),
            'total_mirrored' => Video::where('hosting_status', 'like', '%"success"%')->count(),
            'total_mirror_pending' => Video::where('download_status', 'completed')
                ->where(function($q) {
                    $q->whereNull('hosting_status')
                      ->orWhere('hosting_status', 'not like', '%"success"%');
                })->count(),
            'host_stats' => $hostStats,
            'recent_activity' => $recentActivity,
            'proxy_enabled' => \App\Models\Setting::getValue('proxy_enabled', '1') === '1',
            'categories' => Category::orderBy('order')->get(['id', 'name']),
        ]);
    }

    public function extractor()
    {
        return Inertia::render('Admin/Videos/Extractor');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'url' => 'required_without:video_file|nullable|url',
            'video_file' => 'required_without:url|nullable|file|mimes:mp4,mov,avi,wmv|max:512000', // 500MB max
            'is_premium' => 'boolean',
            'skip_download' => 'boolean',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $title = $request->title;
        $is_premium = $request->is_premium ?? true;
        $slug = Str::slug($title ?: 'video-' . Str::random(5)) . '-' . Str::random(5);

        if ($request->hasFile('video_file')) {
            $file = $request->file('video_file');
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
                'download_status' => $request->skip_download ? 'completed' : 'completed',
                'is_premium' => $request->is_premium ?? true,
                'category_id' => $request->category_id,
            ]);

            // Auto-chain: mirroring to Streamtape after manual PC upload
            DistributeToHostJob::dispatch($video, 'streamtape');
            DistributeToHostJob::dispatch($video, 'videy');
        } else {
            // URL Upload (Same as before)
            $url = $request->url;
            if (!$title) {
                $filename = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME);
                $title = 'Video ' . ($filename ?: Str::random(8));
            }

            $video = Video::create([
                'title' => $title,
                'slug' => $slug,
                'url' => $url,
                'download_status' => $request->skip_download ? 'completed' : null,
                'is_premium' => $request->is_premium ?? true,
                'category_id' => $request->category_id,
            ]);

            if (!$request->skip_download) {
                \App\Jobs\DownloadVideoJob::dispatch($video);
                $video->update(['download_status' => 'pending']);
            }
        }

        $this->applyAutoFreeLogic($video);

        return back()->with('success', 'Video berhasil ditambahkan.');
    }

    public function update(Request $request, Video $video)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'is_premium' => 'boolean',
        ]);

        $video->update([
            'title' => $request->title,
            'category_id' => $request->category_id,
            'is_premium' => $request->is_premium,
        ]);

        Cache::forget("video_show_{$video->slug}");

        return back()->with('success', 'Video berhasil diperbarui.');
    }

    public function storeBulk(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json,csv,txt',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        if ($extension === 'json') {
            $content = file_get_contents($file->getRealPath());
            // Attempt to fix common JSON errors like trailing commas
            $cleanContent = preg_replace('/,\s*([\]\}])/', '$1', $content);
            $data = json_decode($cleanContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(['file' => 'Invalid JSON format: ' . json_last_error_msg()]);
            }

            if (!is_array($data)) {
                return back()->withErrors(['file' => 'JSON must be an array of video URLs or objects.']);
            }

            foreach ($data as $item) {
                $this->createVideo($item);
            }
        } else {
            // CSV Logic
            $handle = fopen($file->getRealPath(), 'r');
            $header = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                $item = array_combine($header, $row);
                $this->createVideo($item);
            }
            fclose($handle);
        }

        return back()->with('success', 'Unggahan massal selesai.');
    }

    public function bulkStoreFromUrls(Request $request)
    {
        $request->validate([
            'urls' => 'required|array',
            'urls.*' => 'required|url',
            'is_premium' => 'boolean',
        ]);

        $count = 0;
        foreach ($request->urls as $url) {
            // Re-use logic for duplicate check and title generation
            if (Video::where('url', $url)->exists()) continue;

            $filename = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME);
            $title = 'Video ' . ($filename ?: Str::random(8));

            $video = Video::create([
                'title' => $title,
                'slug' => Str::slug($title) . '-' . Str::random(5),
                'url' => $url,
                'is_premium' => $request->is_premium ?? true,
            ]);

            // Just mark pending for sync.py manual download
            if (Str::contains($video->url, 'videy.co')) {
                $video->update(['download_status' => 'pending']);
                \App\Jobs\DistributeRemoteHostJob::dispatch($video, 'streamtape', $video->url);
            }
            $count++;
        }

        return back()->with('success', "$count videos added successfully.");
    }

    protected function createVideo($data)
    {
        // Handle both object-like arrays and plain strings
        $url = is_array($data) ? ($data['url'] ?? $data[0] ?? null) : $data;
        
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            \Log::warning('Invalid URL ignored in bulk upload: ' . (is_scalar($url) ? $url : gettype($url)));
            return;
        }

        // Duplicate check: ignore if link already exists
        if (Video::where('url', $url)->exists()) {
            return;
        }

        // Auto-title logic
        $title = is_array($data) ? ($data['title'] ?? null) : null;
        if (! $title) {
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

        // Just mark pending for sync.py manual download
        if (Str::contains($video->url, 'videy.co')) {
            $video->update(['download_status' => 'pending']);
            \App\Jobs\DistributeRemoteHostJob::dispatch($video, 'streamtape', $video->url);
        }

        $this->applyAutoFreeLogic($video);
    }

    protected function applyAutoFreeLogic($video)
    {
        $totalVideos = Video::count();
        if ($totalVideos % 10 === 0) {
            $video->update(['is_free_to_all' => true, 'is_premium' => false]);
        }
    }

    public function destroy(Video $video)
    {
        // Delete file if local
        if ($video->download_status === 'completed' && $video->local_path) {
            $fileToDelete = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('app/public/' . $video->local_path));
            if (is_file($fileToDelete)) {
                unlink($fileToDelete);
            }

            // Safe Delete thumbnail (Check multiple patterns)
            $thumbSlug = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('app/public/thumbnails/' . $video->slug . '.jpg'));
            $thumbId = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('app/public/thumbnails/' . $video->id . '.jpg'));
            
            if (is_file($thumbSlug)) {
                unlink($thumbSlug);
            }
            if (is_file($thumbId)) {
                unlink($thumbId);
            }
        }
        $video->delete();
        Cache::forget("video_show_{$video->slug}");
        Cache::forget('admin_stats');
        
        return back()->with('success', 'Video dan file terkait berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:videos,id',
        ]);

        Video::destroy($request->ids);

        return back()->with('success', count($request->ids) . ' video berhasil dihapus.');
    }

    public function download(Video $video)
    {
        \App\Jobs\DownloadVideoJob::dispatch($video);
        return back()->with('success', 'Unduhan video telah masuk antrean.');
    }

    public function bulkDownload(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:videos,id',
        ]);

        $videos = Video::whereIn('id', $request->ids)->get();
        foreach ($videos as $video) {
            \App\Jobs\DownloadVideoJob::dispatch($video);
        }

        return back()->with('success', count($request->ids) . ' antrean unduhan video dimulai.');
    }

    public function downloadAllPending()
    {
        $videos = Video::where('download_status', '!=', 'completed')
                    ->where('download_status', '!=', 'downloading')
                    ->orWhereNull('download_status')
                    ->get();

        $count = 0;
        foreach ($videos as $video) {
            \App\Jobs\DownloadVideoJob::dispatch($video);
            $count++;
        }

        return back()->with('success', "$count video tertunda telah masuk antrean unduhan.");
    }

    public function progress(Request $request)
    {
        $ids = $request->input('ids', []);
        $progress = [];
        $uploadProgress = [];
        $mirroring = [];

        $videos = Video::whereIn('id', $ids)->get(['id', 'hosting_status']);

        foreach ($ids as $id) {
            $progress[$id] = Cache::get('video_download_' . $id, 0);
            $uploadProgress[$id] = Cache::get('video_upload_' . $id, 0);
            
            $video = $videos->where('id', $id)->first();
            if ($video) {
                $mirroring[$id] = $video->hosting_status;
            }
        }

        // Compute global averages
        $activeDownloadIds = Video::where('download_status', 'downloading')->pluck('id');
        $activeMirrorIds = Video::where('hosting_status', 'like', '%"uploading"%')
            ->orWhere('hosting_status', 'like', '%"pending"%')
            ->pluck('id');

        $globalDownloadAvg = 0;
        if ($activeDownloadIds->count() > 0) {
            $sum = 0;
            $countValid = 0;
            foreach ($activeDownloadIds as $adId) {
                $val = Cache::get('video_download_' . $adId, 0);
                if ($val > 0) { $sum += $val; $countValid++; }
            }
            $globalDownloadAvg = $countValid ? round($sum / $countValid) : 0;
        }

        $globalUploadAvg = 0;
        if ($activeMirrorIds->count() > 0) {
            $sum = 0;
            $countValid = 0;
            foreach ($activeMirrorIds as $amId) {
                $val = Cache::get('video_upload_' . $amId, 0);
                if ($val > 0) { $sum += $val; $countValid++; }
            }
            $globalUploadAvg = $countValid ? round($sum / $countValid) : 0;
        }

        $allStatuses = Video::whereNotNull('hosting_status')->pluck('hosting_status');
        $hostStats = [
            'streamtape' => 0,
            'doodstream' => 0,
        ];
        foreach ($allStatuses as $status) {
            if (is_array($status)) {
                foreach ($status as $host => $s) {
                    if ($s === 'success' && isset($hostStats[$host])) {
                        $hostStats[$host]++;
                    }
                }
            }
        }

        return response()->json([
            'progress' => $progress,
            'uploadProgress' => $uploadProgress,
            'mirroring' => $mirroring,
            'global_stats' => [
                'total_active_downloads' => Video::where('download_status', 'downloading')->count(),
                'total_active_mirrors' => Video::where('hosting_status', 'like', '%"uploading"%')
                    ->orWhere('hosting_status', 'like', '%"pending"%')
                    ->count(),
                'total_mirrored' => Video::where('hosting_status', 'like', '%"success"%')->count(),
                'total_local' => Video::where('download_status', 'completed')->count(),
                'total_download_pending' => Video::where(function($q) {
                    $q->where('download_status', '!=', 'completed')
                      ->orWhereNull('download_status');
                })->whereNotNull('url')->count(),
                'global_download_avg' => $globalDownloadAvg,
                'global_upload_avg' => $globalUploadAvg,
                'host_stats' => $hostStats,
            ],
            'recent_activity' => Video::whereNotNull('hosting_status')
                ->latest('updated_at')
                ->take(5)
                ->get(['id', 'title', 'slug', 'hosting_status', 'updated_at'])
        ]);
    }

    public function syncStorage(Video $video)
    {
        $storageRoot = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('app/public'));
        
        $path1 = $storageRoot . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'video-' . $video->slug . '.mp4';
        $path2 = $storageRoot . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . $video->slug . '.mp4';

        $foundPath = null;
        if (is_file($path1)) {
            $foundPath = 'videos/video-' . $video->slug . '.mp4';
        } elseif (is_file($path2)) {
            $foundPath = 'videos/' . $video->slug . '.mp4';
        }

        if ($foundPath) {
            $video->download_status = 'completed';
            $video->local_path = $foundPath;
            
            // Sync thumbnail
            $thumbPath = $storageRoot . DIRECTORY_SEPARATOR . 'thumbnails' . DIRECTORY_SEPARATOR . $video->slug . '.jpg';
            if (is_file($thumbPath)) {
                $video->thumbnail_url = '/storage/thumbnails/' . $video->slug . '.jpg';
            }

            $video->save();
            Cache::forget("video_show_{$video->slug}");
            Cache::forget('admin_stats');

            return back()->with('success', 'File video ditemukan dan disinkronkan.');
        }

        $video->update([
            'download_status' => null,
            'local_path' => null,
        ]);
        Cache::forget('admin_stats');

        return back()->with('error', 'Video file not found in storage.');
    }

    public function syncAllStorage()
    {
        set_time_limit(0);
        $videos = Video::all();
        $synced = 0;
        $missing = 0;

        // 1. FLASH AUDIT: Scan the entire storage directory ONCE to avoid 2,714 separate disk lookups
        $storageRoot = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('app/public'));
        $vDir = $storageRoot . DIRECTORY_SEPARATOR . 'videos';
        $tDir = $storageRoot . DIRECTORY_SEPARATOR . 'thumbnails';
        
        $existingVideos = is_dir($vDir) ? array_flip(scandir($vDir)) : [];
        $existingThumbs = is_dir($tDir) ? array_flip(scandir($tDir)) : [];

        foreach ($videos as $video) {
            $foundPath = null;
            
            // Try naming conventions based on the slug (High Speed O(1) lookup)
            // Goal: Filename must be exactly "video-{identifier}.mp4"
            if (str_starts_with($video->slug, 'video-')) {
                $f1 = $video->slug . '.mp4';
                $f2 = str_replace('video-', '', $video->slug) . '.mp4'; // Fallback to raw id
            } else {
                $f1 = 'video-' . $video->slug . '.mp4';
                $f2 = $video->slug . '.mp4';
            }

            if (isset($existingVideos[$f1])) {
                $foundPath = 'videos/' . $f1;
            } elseif (isset($existingVideos[$f2])) {
                $foundPath = 'videos/' . $f2;
            }

            if ($foundPath) {
                if ($video->download_status !== 'completed' || $video->local_path !== $foundPath) {
                    $video->download_status = 'completed';
                    $video->local_path = $foundPath;
                }
                $synced++;
            } else {
                if ($video->download_status === 'completed') {
                    $video->download_status = null;
                    $video->local_path = null;
                }
                $missing++;
            }

            // Sync thumbnail if found in pre-scanned list
            $tFile = $video->slug . '.jpg';
            if (isset($existingThumbs[$tFile])) {
                $video->thumbnail_url = '/storage/thumbnails/' . $tFile;
            }

            if ($video->isDirty()) {
                $video->save();
                Cache::forget("video_show_{$video->slug}");
            }
        }

        Cache::forget('admin_stats');

        return to_route('admin.videos.index')->with('success', "Audit Flash Selesai. Ditemukan $synced video lokal. ($missing video hilang dari penyimpanan).");
    }

    public function exportDownloadList()
    {
        $videos = Video::where(function($q) {
                        $q->where('download_status', '!=', 'completed')
                          ->orWhereNull('download_status');
                    })
                    ->whereNotNull('url')
                    ->get(['id', 'title', 'url', 'slug']);

        $content = json_encode($videos, JSON_PRETTY_PRINT);
        
        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, 'sync_list.json', [
            'Content-Type' => 'application/json',
        ]);
    }

    public function updateSetting(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'required',
        ]);

        \App\Models\Setting::setValue($request->key, $request->value);

        return back()->with('success', 'Pengaturan berhasil diperbarui.');
    }

    public function distribute(Video $video, Request $request)
    {
        $host = $request->input('host');
        $service = app(\App\Services\MultiHostService::class);
        
        $hosts = $host ? [$host] : array_keys($service->allDrivers());

        foreach ($hosts as $h) {
            DistributeToHostJob::dispatch($video, $h);
        }

        return to_route('admin.videos.index')->with('success', 'Tugas sinkronisasi dikirim.');
    }

    public function mirrorBulk(Request $request)
    {
        $host = $request->input('host');
        
        // Use the artisan command logic but for all local videos
        \Illuminate\Support\Facades\Artisan::call('app:distribute-bulk', [
            'host' => $host,
            '--limit' => 1000 // Process up to 1000 at a time for the full backlog
        ]);

        return to_route('admin.videos.index')->with('success', 'Antrean sinkronisasi massal dimulai untuk semua video yang hilang.');
    }
    public function mirrorSelected(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:videos,id',
            'host' => 'nullable|string',
        ]);

        $host = $request->input('host');
        $service = app(\App\Services\MultiHostService::class);
        $hosts = $host ? [$host] : array_keys($service->allDrivers());

        $videos = Video::whereIn('id', $request->ids)->where('download_status', 'completed')->get();

        $count = 0;
        foreach ($videos as $index => $video) {
            foreach ($hosts as $h) {
                // Skip videy simulator unless explicitly requested
                if ($h === 'videy' && !$host) continue;
                DistributeToHostJob::dispatch($video, $h)->delay(now()->addSeconds($index * 3));
                $count++;
            }
        }

        return back()->with('success', "{$count} tugas sinkronisasi dikirim untuk " . count($request->ids) . " video.");
    }

    public function checkHealth()
    {
        $videos = Video::all();
        foreach ($videos as $video) {
            \App\Jobs\CheckVideoHealthJob::dispatch($video);
        }

        return redirect()->back()->with('success', 'Tugas pemeriksaan kesehatan dikirim untuk ' . $videos->count() . ' video.');
    }

    public function exportSocialLinks(Request $request)
    {
        // ... (existing export logic)
    }

    /**
     * Display the Bulk Sync Manual Links page.
     */
    public function bulkSyncView()
    {
        return Inertia::render('Admin/Videos/BulkSync');
    }

    /**
     * Parse and sync pasted Doodstream links.
     */
    public function bulkSyncLinks(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $content = $request->input('content');
        // Split by lines and clean
        $lines = explode("\n", $content);
        
        $currentMarker = null;
        $matchedCount = 0;
        $multiHost = app(\App\Services\MultiHostService::class);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // 1. Detect Marker: <!-- video-ID --> or <!-- ID -->
            if (preg_match('/<!--\s*(?:video-)?([a-z0-9-]+)\s*-->/i', $line, $matches)) {
                $currentMarker = $matches[1];
                continue;
            }

            // 2. Detect URL (only if we have a marker waiting)
            if ($currentMarker && filter_var($line, FILTER_VALIDATE_URL)) {
                // Find video using Case-Insensitive Smart Matching (Slug or URL)
                $video = Video::whereRaw('LOWER(slug) LIKE ?', ["%$currentMarker%"])
                    ->orWhereRaw('LOWER(url) LIKE ?', ["%$currentMarker%"])
                    ->first();

                if ($video) {
                    $multiHost->updateStatus($video, 'doodstream', 'success', $line);
                    $matchedCount++;
                }
                
                // Clear marker for next pair
                $currentMarker = null;
            }
        }

        return back()->with('success', "Berhasil mencocokkan dan menyinkronkan $matchedCount tautan Doodstream.");
    }
}
