<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'ad_banner_728x90' => Setting::getValue('ad_banner_728x90', ''),
            'ad_social_bar' => Setting::getValue('ad_social_bar', ''),
            'ad_banner_468x60' => Setting::getValue('ad_banner_468x60', ''),
            'ad_native_banner' => Setting::getValue('ad_native_banner', ''),
            'ad_banner_300x250' => Setting::getValue('ad_banner_300x250', ''),
            'ad_smartlink' => Setting::getValue('ad_smartlink', ''),
            'ad_popunder' => Setting::getValue('ad_popunder', ''),
        ];

        \Illuminate\Support\Facades\Log::info('Loaded settings for admin:', $settings);

        return Inertia::render('Admin/Settings', [
            'settings' => $settings
        ]);
    }

    public function update(Request $request)
    {
        $settings = $request->all();
        $isEncoded = $request->input('_is_encoded', false);

        foreach ($settings as $key => $value) {
            if (str_starts_with($key, 'ad_')) {
                $finalValue = $value;
                if ($isEncoded && $key !== 'ad_smartlink' && $value) {
                    $finalValue = base64_decode($value);
                }
                Setting::setValue($key, $finalValue);
            }
        }

        return back()->with('success', 'Settings updated successfully.');
    }
}
