<?php
/**
 * VideyView Doodstream Sync Utility
 * Scans Doodstream account for manual uploads and syncs them to local DB.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Video;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

$apiKey = Setting::where('key', 'doodstream_key')->first()?->value;

if (!$apiKey) {
    die("Error: Doodstream API key not found in settings.\n");
}

echo "--- Doodstream Manual Sync Started ---\n";
echo "Scanning for records...\n";

// 1. Fetch File List from Doodstream
// Note: We use the ISP Bypass technique
$response = Http::withoutVerifying()
    ->withOptions([
        'curl' => [
            CURLOPT_RESOLVE => ["doodapi.co:443:172.67.70.170"],
        ],
    ])
    ->get("https://doodapi.co/api/file/list?key=$apiKey");

if ($response->failed()) {
    die("Error: Failed to connect to Doodstream API. " . $response->body() . "\n");
}

$data = $response->json();
$files = $data['result']['files'] ?? [];

if (empty($files)) {
    die("No files found on Doodstream account.\n");
}

echo "Found " . count($files) . " files on Doodstream. Starting matching process...\n\n";

$matchedCount = 0;
foreach ($files as $file) {
    $title = $file['title'];
    $fileCode = $file['file_code'];
    
    // Try to extract slug from title (e.g., VideyView_video-abc-123 or just video-abc-123)
    $slug = null;
    if (preg_match('/video-[a-z0-9-]+/', $title, $matches)) {
        $slug = $matches[0];
    } else {
        $slug = $title; // Fallback to entire title
    }

    echo "Checking file: $title (Code: $fileCode) | Possible Slug: $slug\n";

    // Find in DB
    $video = Video::where('slug', $slug)->first();
    
    if (!$video) {
        // Try fuzzy match
        $video = Video::where('title', $title)->first();
    }

    if ($video) {
        echo "   [MATCH FOUND] Video: {$video->id} | Slug: {$video->slug}\n";
        
        $multiHost = app(\App\Services\MultiHostService::class);
        $mirrorUrl = "https://doodapi.co/e/$fileCode";
        
        $multiHost->updateStatus($video, 'doodstream', 'success', $mirrorUrl);
        echo "   [SYNCED] Updated database record.\n";
        $matchedCount++;
    } else {
        echo "   [SKIP] No match in local database.\n";
    }
}

echo "\n--- Sync Completed ---\n";
echo "Total Matched & Synced: $matchedCount\n";
