<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\Setting;
use App\Models\VideoLike;
use App\Models\Watchlist;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class VideoController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');
        $search = $request->query('search');
        $tagSlug = $request->query('tag');
        $sort = $request->query('sort', 'latest');
        $page = $request->query('page', 1);

        $cacheKey = "public_videos_{$filter}_s_{$search}_t_{$tagSlug}_sort_{$sort}_page_{$page}";

        $videos = \Illuminate\Support\Facades\Cache::remember($cacheKey, 60, function () use ($filter, $search, $tagSlug, $sort) {
            $query = Video::query();

            // Search by title
            if ($search) {
                $query->where('title', 'like', "%{$search}%");
            }

            // Filter by Tag
            if ($tagSlug) {
                $query->whereHas('tags', function($q) use ($tagSlug) {
                    $q->where('slug', $tagSlug);
                });
            }

            // Category Filters
            if ($filter === 'free') {
                $query->where(function($q) {
                    $q->where('is_free_to_all', true)->orWhere('is_premium', false);
                });
            } elseif ($filter === 'premium') {
                $query->where('is_premium', true);
            }

            // Sorting Logic 2.0
            if ($filter === 'trending' || $sort === 'popular') {
                $query->whereNotNull('thumbnail_url')->orderBy('views', 'desc');
            } elseif ($sort === 'oldest') {
                $query->oldest();
            } else {
                // Default: Latest with thumbnail priority
                $query->orderByRaw('thumbnail_url IS NULL ASC')->latest();
            }

            return $query->paginate(24)->withQueryString();
        });

        // Use local proxy for thumbnails to enable on-demand caching/generation
        $videos->getCollection()->transform(function ($v) {
            // Apply proxy if it's a local video OR if it has a remote thumbnail needing caching
            if (($v->download_status === 'completed' && $v->local_path) || ($v->thumbnail_url && !str_contains($v->thumbnail_url, 'video-thumbnail'))) {
                $v->thumbnail_url = route('video.thumbnail', $v->slug);
            }
            return $v;
        });

        $trendingVideos = \Illuminate\Support\Facades\Cache::remember('trending_hero_videos', 60, function() {
            return Video::whereNotNull('thumbnail_url')
                ->where('thumbnail_url', '!=', '')
                ->orderBy('views', 'desc')
                ->take(5)
                ->get(['id', 'slug', 'title', 'thumbnail_url', 'is_premium', 'is_free_to_all', 'views']);
        });

        $trendingVideos->transform(function ($v) {
            if ($v->thumbnail_url && !str_contains($v->thumbnail_url, 'video-thumbnail')) {
                $v->thumbnail_url = route('video.thumbnail', $v->slug);
            }
            return $v;
        });

        return Inertia::render('Videos/Index', [
            'videos' => $videos,
            'trendingVideos' => $trendingVideos,
            'currentFilter' => $filter,
            'currentSort' => $sort,
        ]);
    }

    public function show(Video $video)
    {
        $video->increment('views');
        
        $localUrl = null;
        $cdnUrl = $video->url;

        if ($video->download_status === 'completed' && $video->local_path) {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($video->local_path)) {
                $localUrl = asset('storage/' . $video->local_path);
            } else {
                // Self-healing: file is gone, revert to Cloud CDN
                $video->update(['download_status' => null]);
            }
        }
        
        if (Str::contains($cdnUrl, 'videy.co')) {
            $cdnUrl = route('video.proxy', ['url' => $cdnUrl, 'video_id' => $video->id]);
        }

        if (($video->download_status === 'completed' && $video->local_path) || ($video->thumbnail_url && !str_contains($video->thumbnail_url, 'video-thumbnail'))) {
            $video->thumbnail_url = route('video.thumbnail', $video->slug);
        }

        $is_allowed = ! $video->is_premium || (Auth::check() && Auth::user()->hasActiveSubscription());

        $recommended = Video::where('id', '!=', $video->id)
            ->orderByRaw('thumbnail_url IS NULL ASC')
            ->inRandomOrder()
            ->take(5)
            ->get(['id', 'slug', 'title', 'url', 'thumbnail_url', 'is_premium', 'is_free_to_all', 'views']);

        $recommended->transform(function ($v) {
            if (($v->download_status === 'completed' && $v->local_path) || ($v->thumbnail_url && !str_contains($v->thumbnail_url, 'video-thumbnail'))) {
                $v->thumbnail_url = route('video.thumbnail', $v->slug);
            }
            return $v;
        });

        // Social States
        $userLike = null;
        $is_watchlisted = false;
        if (Auth::check()) {
            $userLike = VideoLike::where('video_id', $video->id)->where('user_id', Auth::id())->first();
            $is_watchlisted = Watchlist::where('video_id', $video->id)->where('user_id', Auth::id())->exists();
        }

        $watermarkText = Setting::where('key', 'watermark_text')->value('value') ?? 'VIDEYVIEW PROTECT';

        // Hide sensitive fields before sending to the frontend
        $secureVideo = $video->makeHidden(['url', 'mirror_links', 'hosting_status', 'local_path', 'remote_id'])->load(['comments.user.activeSubscription']);

        return Inertia::render('Videos/Show', [
            'video' => $secureVideo,
            'is_allowed' => $is_allowed,
            'auth_user' => Auth::user(),
            'user_like_status' => $userLike ? ($userLike->is_like ? 'like' : 'dislike') : null,
            'is_watchlisted' => $is_watchlisted,
            'watermark_text' => $watermarkText,
            'recommended' => $recommended,
            'has_local' => ($video->download_status === 'completed' && $video->local_path),
            'has_videy' => str_contains($video->url ?? '', 'videy.co'),
            'available_mirrors' => collect($video->mirror_links)->keys(),
            'premium_count' => Video::where('is_premium', true)->count(),
        ]);
    }

    public function maskedRedirect(Video $video)
    {
        return redirect()->route('videos.show', $video->slug);
    }

    public function thumbnail(Video $video)
    {
        $thumbName = $video->slug . '.jpg';
        $thumbPath = 'thumbnails/' . $thumbName;
        $absolutePath = storage_path('app/public/' . $thumbPath);

        // If exists, serve it
        if (file_exists($absolutePath)) {
            return response()->file($absolutePath, [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        }

        // Generate on-the-fly for local videos if missing
        if ($video->download_status === 'completed' && $video->local_path) {
            $absVideoPath = storage_path('app/public/' . $video->local_path);
            if (file_exists($absVideoPath)) {
                $generated = \App\Helpers\VideoHelper::generateThumbnail($video, $absVideoPath);
                if ($generated && file_exists($absolutePath)) {
                    return response()->file($absolutePath, [
                        'Content-Type' => 'image/jpeg',
                        'Cache-Control' => 'public, max-age=31536000, immutable',
                    ]);
                }
            }
        }

        // Fallback to placeholder or external
        if ($video->thumbnail_url && !str_contains($video->thumbnail_url, '/storage/')) {
            return redirect($video->thumbnail_url);
        }

        return redirect('https://placehold.co/600x400/1e293b/4f46e5?text=' . urlencode($video->title));
    }

    public function proxy(Request $request)
    {
        // Prevent session locking for long-running stream
        if (session_id()) session_write_close();
        
        // Ensure the script doesn't time out during extraction
        set_time_limit(0);
        ignore_user_abort(true);

        $videoId = $request->query('video_id');
        $video = Video::find($videoId);
        
        if (!$video) {
            abort(404, 'Video not found.');
        }

        $url = $video->url;
        
        if ($video->is_premium) {
            $user = Auth::user();
            if (!$user || (!$user->is_admin && !$user->hasActiveSubscription())) {
                abort(403, 'Premium subscription required for this proxy stream.');
            }
        }

        $host = parse_url($url, PHP_URL_HOST);
        $ip = '172.67.73.18'; // Real Cloudflare IP
        
        if (ob_get_level()) ob_end_clean();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 1024000); // 1MB buffer
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        
        // ISP BYPASS
        curl_setopt($ch, CURLOPT_RESOLVE, ["$host:443:$ip", "$host:80:$ip"]);

        // STEALTH HEADERS (Chrome Mirror)
        $headers = [
            'Host: ' . $host,
            'Accept: */*',
            'Accept-Encoding: identity;q=1, *;q=0',
            'Accept-Language: en-US,en;q=0.9',
            'Connection: keep-alive',
            'Referer: https://videy.co/',
            'Sec-Ch-Ua: "Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
            'Sec-Ch-Ua-Mobile: ?0',
            'Sec-Ch-Ua-Platform: "Windows"',
            'Sec-Fetch-Dest: video',
            'Sec-Fetch-Mode: no-cors',
            'Sec-Fetch-Site: cross-site',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ];
        
        if ($request->header('Range')) {
            $headers[] = 'Range: ' . $request->header('Range');
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $header) {
            $len = strlen($header);
            $clean = strtolower(trim($header));
            
            // Forward status code safely
            if (str_starts_with($clean, 'http/')) {
                $parts = explode(' ', $header, 3);
                if (count($parts) >= 2) {
                    http_response_code((int)$parts[1]);
                }
            }
            
            // Forward critical headers
            $forwarded = [
                'content-type:', 'content-length:', 'content-range:', 'accept-ranges:',
                'cache-control:', 'pragma:', 'expires:', 'etag:', 'last-modified:'
            ];
            foreach ($forwarded as $prefix) {
                if (str_starts_with($clean, $prefix)) {
                    // Force video MIME for safety
                    if ($prefix === 'content-type:' && str_contains($clean, 'text/html')) {
                        header('Content-Type: video/mp4');
                        continue;
                    }
                    header($header);
                    break;
                }
            }
            
            return $len;
        });

        curl_exec($ch);
        
        if (curl_errno($ch)) {
            \Log::error("Proxy Fatal Error: " . curl_error($ch));
        }

        curl_close($ch);
        exit;
    }

    public function getStreamUrl(Video $video, Request $request)
    {
        // Permission check
        if ($video->is_premium) {
            $user = Auth::user();
            if (!$user || (!$user->is_admin && !$user->hasActiveSubscription())) {
                return response()->json(['error' => 'Premium subscription required'], 403);
            }
        }

        $host = $request->input('host', 'local');
        $url = null;

        if ($host === 'local' && $video->download_status === 'completed' && $video->local_path) {
            $url = url('storage/' . $video->local_path);
        } elseif ($host === 'videy' && str_contains($video->url ?? '', 'videy.co')) {
            $url = route('video.proxy', ['video_id' => $video->id]);
        } else {
            $mirrors = $video->mirror_links;
            if (isset($mirrors[$host])) {
                $url = $mirrors[$host];
            }
        }

        if (!$url) {
            return response()->json(['error' => 'Stream source not available'], 404);
        }

        return response()->json(['url' => $url]);
    }
}
