<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\Category;
use App\Models\Setting;
use App\Services\GeminiService;
use App\Services\VideoManagementService;
use App\Services\VideoWorkerService;
use App\Services\VideoDistributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;

class VideoController extends Controller
{
    protected $management;
    protected $worker;
    protected $distribution;

    public function __construct(
        VideoManagementService $management,
        VideoWorkerService $worker,
        VideoDistributionService $distribution
    ) {
        $this->management = $management;
        $this->worker = $worker;
        $this->distribution = $distribution;
    }

    /**
     * AI Suggestion Engine: Get SEO-friendly metadata from Gemini 1.5 Flash.
     */
    public function suggestMetadata(Request $request, GeminiService $gemini)
    {
        $request->validate(['input' => 'required|string|min:3']);
        $result = $gemini->suggestMetadata($request->input('input'));
        return !$result['success'] ? response()->json($result, 422) : response()->json($result);
    }

    public function index()
    {
        $videos = Video::with('category')
            ->orderByRaw("CASE WHEN download_status = 'completed' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(10);

        $videos->getCollection()->transform(function ($video) {
            $video->local_url = $video->download_status === 'completed' && $video->local_path 
                ? url('storage/' . $video->local_path) : null;
            $video->proxy_url = Str::contains($video->url, 'videy.co')
                ? route('video.proxy', ['url' => $video->url, 'video_id' => $video->id]) : $video->url;
            return $video;
        });

        return Inertia::render('Admin/Videos/Index', [
            'videos' => $videos,
            'total_local' => fn() => Video::where('download_status', 'completed')->count(),
            'total_pending' => fn() => Video::whereNotIn('download_status', ['completed'])->orWhereNull('download_status')->count(),
            'total_download_pending' => fn() => Video::where(function($q) {
                    $q->whereNotIn('download_status', ['completed'])->orWhereNull('download_status');
                })->whereNotNull('url')->count(),
            'total_active_downloads' => fn() => Video::where('download_status', 'downloading')->count(),
            'total_active_mirrors' => fn() => Video::where('hosting_status', 'like', '%"uploading"%')->orWhere('hosting_status', 'like', '%"pending"%')->count(),
            'total_mirrored' => fn() => Video::where('hosting_status', 'like', '%"success"%')->count(),
            'total_mirror_pending' => fn() => Video::where('download_status', 'completed')->where(function($q) {
                    $q->whereNull('hosting_status')->orWhere('hosting_status', 'not like', '%"success"%');
                })->count(),
            'host_stats' => fn() => $this->calculateHostStats(),
            'recent_activity' => fn() => $this->getRecentActivity(),
            'proxy_enabled' => Setting::getValue('proxy_enabled', '1') === '1',
            'categories' => Category::orderBy('order')->get(['id', 'name']),
        ]);
    }

    private function calculateHostStats()
    {
        $allStatuses = Video::whereNotNull('hosting_status')->pluck('hosting_status');
        $hostStats = ['streamtape' => 0, 'doodstream' => 0];
        foreach ($allStatuses as $status) {
            if (is_array($status)) {
                foreach ($hostStats as $key => $val) {
                    if (($status[$key] ?? null) === 'success') $hostStats[$key]++;
                }
            }
        }
        return $hostStats;
    }

    private function getRecentActivity()
    {
        return Video::whereNotNull('mirror_links')->where('mirror_links', '!=', '[]')->latest('updated_at')->take(5)->get(['id', 'title', 'slug', 'hosting_status', 'updated_at']);
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
            'video_file' => 'required_without:url|nullable|file|mimes:mp4,mov,avi,wmv|max:512000',
            'is_premium' => 'boolean',
            'skip_download' => 'boolean',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $this->management->storeVideo($request->all());
        return back()->with('success', 'Video berhasil ditambahkan.');
    }

    public function update(Request $request, Video $video)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'is_premium' => 'boolean',
        ]);

        $this->management->updateVideo($video, $request->only(['title', 'category_id', 'is_premium']));
        return back()->with('success', 'Video berhasil diperbarui.');
    }

    public function storeBulk(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:json,csv,txt']);
        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        if ($extension === 'json') {
            $content = file_get_contents($file->getRealPath());
            $cleanContent = preg_replace('/,\s*([\]\}])/', '$1', $content);
            $data = json_decode($cleanContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) return back()->withErrors(['file' => 'Invalid JSON: ' . json_last_error_msg()]);
            foreach ((array)$data as $item) $this->management->createVideoFromData($item);
        } else {
            $handle = fopen($file->getRealPath(), 'r');
            $header = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) $this->management->createVideoFromData(array_combine($header, $row));
            fclose($handle);
        }
        return back()->with('success', 'Unggahan massal selesai.');
    }

    public function bulkStoreFromUrls(Request $request)
    {
        $request->validate(['urls' => 'required|array', 'urls.*' => 'required|url', 'is_premium' => 'boolean']);
        $count = 0;
        foreach ($request->urls as $url) {
            if ($this->management->createVideoFromData(['url' => $url, 'is_premium' => $request->is_premium])) $count++;
        }
        return back()->with('success', "$count videos added successfully.");
    }

    public function destroy(Video $video)
    {
        $this->management->deleteVideo($video);
        return back()->with('success', 'Video dan file terkait berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:videos,id']);
        Video::whereIn('id', $request->ids)->get()->each(fn($v) => $this->management->deleteVideo($v));
        return back()->with('success', count($request->ids) . ' video berhasil dihapus.');
    }

    public function download(Video $video)
    {
        $this->worker->queueDownload($video);
        return back()->with('success', 'Unduhan video telah masuk antrean.');
    }

    public function bulkDownload(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:videos,id']);
        Video::whereIn('id', $request->ids)->get()->each(fn($v) => $this->worker->queueDownload($v));
        return back()->with('success', count($request->ids) . ' antrean unduhan video dimulai.');
    }

    public function downloadAllPending()
    {
        $videos = Video::where('download_status', '!=', 'completed')->where('download_status', '!=', 'downloading')->orWhereNull('download_status')->get();
        $videos->each(fn($v) => $this->worker->queueDownload($v));
        return back()->with('success', $videos->count() . ' video tertunda telah masuk antrean unduhan.');
    }

    public function progress(Request $request)
    {
        $data = $this->worker->getProgressData($request->input('ids', []));
        $data['recent_activity'] = Video::whereNotNull('hosting_status')->latest('updated_at')->take(5)->get(['id', 'title', 'slug', 'hosting_status', 'updated_at']);
        return response()->json($data);
    }

    public function syncStorage(Video $video)
    {
        return $this->worker->syncStorage($video) 
            ? back()->with('success', 'File video ditemukan dan disinkronkan.')
            : back()->with('error', 'Video file not found in storage.');
    }

    public function syncAllStorage()
    {
        $res = $this->worker->syncAllStorage();
        return to_route('admin.videos.index')->with('success', "Audit Flash Selesai. Ditemukan {$res['synced']} video lokal. ({$res['missing']} video hilang).");
    }

    public function exportDownloadList()
    {
        $videos = Video::where(function($q) { $q->where('download_status', '!=', 'completed')->orWhereNull('download_status'); })->whereNotNull('url')->get(['id', 'title', 'url', 'slug']);
        return response()->streamDownload(fn() => print(json_encode($videos, JSON_PRETTY_PRINT)), 'sync_list.json', ['Content-Type' => 'application/json']);
    }

    public function updateSetting(Request $request)
    {
        $request->validate(['key' => 'required|string', 'value' => 'required']);
        Setting::setValue($request->key, $request->value);
        return back()->with('success', 'Pengaturan berhasil diperbarui.');
    }

    public function distribute(Video $video, Request $request)
    {
        $this->distribution->distribute($video, $request->input('host'));
        return to_route('admin.videos.index')->with('success', 'Tugas sinkronisasi dikirim.');
    }

    public function mirrorBulk(Request $request)
    {
        \Illuminate\Support\Facades\Artisan::call('app:distribute-bulk', ['host' => $request->input('host'), '--limit' => 1000]);
        return to_route('admin.videos.index')->with('success', 'Antrean sinkronisasi massal dimulai.');
    }

    public function mirrorSelected(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:videos,id', 'host' => 'nullable|string']);
        $count = $this->distribution->mirrorSelected($request->ids, $request->input('host'));
        return back()->with('success', "{$count} tugas sinkronisasi dikirim.");
    }

    public function checkHealth()
    {
        Video::all()->each(fn($v) => \App\Jobs\CheckVideoHealthJob::dispatch($v));
        return redirect()->back()->with('success', 'Tugas pemeriksaan kesehatan dikirim.');
    }

    public function bulkSyncView()
    {
        return Inertia::render('Admin/Videos/BulkSync');
    }

    public function bulkSyncLinks(Request $request)
    {
        $request->validate(['content' => 'required|string']);
        $matchedCount = $this->distribution->bulkSyncLinks($request->input('content'));
        return back()->with('success', "Berhasil menyinkronkan $matchedCount tautan.");
    }

    public function bulkUploadView()
    {
        return Inertia::render('Admin/Videos/BulkUpload', ['categories' => Category::orderBy('order')->get(['id', 'name'])]);
    }

    public function storeAjax(Request $request)
    {
        $request->validate(['video_file' => 'required|file|mimes:mp4,mov,avi,wmv|max:1048576', 'is_premium' => 'boolean', 'category_id' => 'nullable|exists:categories,id', 'auto_mirror' => 'boolean']);
        try {
            $video = $this->management->storeVideo($request->all());
            return response()->json(['success' => true, 'message' => 'Video uploaded successfully.', 'video' => $video]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }
}
