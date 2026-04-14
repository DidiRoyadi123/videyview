<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\Tag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $shared = [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'is_admin' => $request->user()->is_admin,
                    'has_active_subscription' => $request->user()->hasActiveSubscription(),
                ] : null,
            ],
            'anti_adblock_enabled' => Setting::getValue('anti_adblock_enabled', '1') === '1',
            'ui_template' => Setting::getValue('ui_template', 'classic'),
            'ads' => function() {
                // Determine if we should show ads on the current request
                $user = Auth::user();
                $isAdmin = $user && $user->is_admin;
                
                // Get current route name
                $routeName = \Route::currentRouteName();
                $isAdminRoute = $routeName && (str_starts_with($routeName, 'admin.') || $routeName === 'dashboard');
                $isAuthRoute = $routeName && (str_starts_with($routeName, 'login') || str_starts_with($routeName, 'register') || str_starts_with($routeName, 'password.'));

                // Block ads for admins, total premium users, and on admin/auth routes
                if ($isAdmin || $isAdminRoute || $isAuthRoute) {
                    return null;
                }

                return [
                    'banner_728x90' => Setting::getValue('ad_banner_728x90'),
                    'social_bar' => Setting::getValue('ad_social_bar'),
                    'banner_468x60' => Setting::getValue('ad_banner_468x60'),
                    'native_banner' => Setting::getValue('ad_native_banner'),
                    'banner_300x250' => Setting::getValue('ad_banner_300x250'),
                    'smartlink' => Setting::getValue('ad_smartlink'),
                    'popunder' => Setting::getValue('ad_popunder'),
                ];
            },
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'message' => $request->session()->get('message'),
            ],
            'seo' => function() use ($request) {
                // Default SEO
                $seo = [
                    'title' => Setting::getValue('site_name', 'VideyView'),
                    'description' => Setting::getValue('site_description', 'Elite Media Empire'),
                    'image' => url('/pwa-icon.png'),
                ];

                // If viewing a video, override with video metadata
                $video = $request->route('video');
                // The route parameter 'video' might be a slug or a model depending on binding
                if ($video) {
                    // Check if it's already a model or we need to find it
                    if (!($video instanceof \App\Models\Video)) {
                        $video = \App\Models\Video::where('slug', $video)->first();
                    }

                    if ($video) {
                        $seo['title'] = $video->title . ' - ' . $seo['title'];
                        $seo['description'] = $video->description ?: 'Watch premium content on VideyView.';
                        $seo['image'] = $video->thumbnail_url ?: $seo['image'];
                    }
                }

                return $seo;
            },
            'popular_tags' => function() {
                $routeName = \Route::currentRouteName();
                $isProtected = $routeName && (
                    str_starts_with($routeName, 'admin.') || 
                    $routeName === 'dashboard' ||
                    str_starts_with($routeName, 'login') || 
                    str_starts_with($routeName, 'register')
                );
                if ($isProtected) return null;

                return Cache::remember('popular_tags_global', 300, function () {
                    return Tag::withCount('videos')
                        ->orderByDesc('videos_count')
                        ->take(12)
                        ->get(['name', 'slug']);
                });
            },
        ];

        return $shared;
    }
}
