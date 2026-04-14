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
            'streamtape_login' => Setting::getValue('streamtape_login', ''),
            'streamtape_key' => Setting::getValue('streamtape_key', ''),
            'doodstream_login' => Setting::getValue('doodstream_login', ''),
            'doodstream_key' => Setting::getValue('doodstream_key', ''),
            'anti_adblock_enabled' => Setting::getValue('anti_adblock_enabled', '1'),
            'watermark_text' => Setting::getValue('watermark_text', 'VIDEYVIEW PROTECT'),
            'ui_template' => Setting::getValue('ui_template', 'classic'),
        ];

        return Inertia::render('Admin/Settings', [
            'settings' => $settings
        ]);
    }

    public function update(Request $request)
    {
        $settings = $request->except(['_is_encoded']);
        $isEncoded = $request->input('_is_encoded', false);

        foreach ($settings as $key => $value) {
            $finalValue = $value;
            
            // Apply base64 decoding for encoded AD scripts if needed
            if ($isEncoded && str_starts_with($key, 'ad_') && $key !== 'ad_smartlink' && $value) {
                $finalValue = base64_decode($value);
            }
            
            Setting::setValue($key, $finalValue);
        }

        return back()->with('success', 'Settings updated successfully.');
    }
}
