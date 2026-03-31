<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
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
            'ads' => [
                'banner_728x90' => ($ad_banner_728x90 = \App\Models\Setting::getValue('ad_banner_728x90')),
                'social_bar' => ($ad_social_bar = \App\Models\Setting::getValue('ad_social_bar')),
                'banner_468x60' => ($ad_banner_468x60 = \App\Models\Setting::getValue('ad_banner_468x60')),
                'native_banner' => ($ad_native_banner = \App\Models\Setting::getValue('ad_native_banner')),
                'banner_300x250' => ($ad_banner_300x250 = \App\Models\Setting::getValue('ad_banner_300x250')),
                'smartlink' => ($ad_smartlink = \App\Models\Setting::getValue('ad_smartlink')),
                'popunder' => ($ad_popunder = \App\Models\Setting::getValue('ad_popunder')),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'message' => $request->session()->get('message'),
            ],
        ];

        return $shared;
    }
}
