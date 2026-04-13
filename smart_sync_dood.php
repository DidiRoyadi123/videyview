<?php
/**
 * VideyView Smart Doodstream Sync Utility (V2)
 * Matches manual uploads using Videy ID pattern extraction.
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

echo "--- Smart Doodstream Matching Operation Started ---\n";
echo "Fetching file list from Doodstream...\n";

// Fetch All Files from Doodstream (using ISP Bypass)
$response = Http::withoutVerifying()->withOptions([
    'curl' => [CURLOPT_RESOLVE => ["doodapi.co:443:172.67.70.170"]],
])->get("https://doodapi.co/api/file/list?key=$apiKey");

$files = $response->json()['result']['files'] ?? [];

if (empty($files)) {
    die("No files found on Doodstream account.\n");
}

echo "Found " . count($files) . " files. Starting Smart Pattern Matching...\n\n";

$matchedCount = 0;
foreach ($files as $file) {
    $doodTitle = $file['title'];
    $fileCode = $file['file_code'];
    
    // 1. Extract potential Videy ID (usually 8-11 alphanumeric characters)
    // We clean the title first to remove "video-" prefix which confuses the regex
    $cleanedTitle = str_replace('video-', '', strtolower($doodTitle));
    $videyId = null;
    
    if (preg_match('/([a-z0-9]{8,11})/', $cleanedTitle, $matches)) {
        $videyId = $matches[1];
    } else {
        $videyId = $cleanedTitle;
    }

    echo "Checking Dood File: '$doodTitle' (Extracted Core ID: $videyId)\n";

    // 2. Search Database for this ID in slug, url, or title (Case-Insensitive)
    $video = Video::whereRaw('LOWER(slug) LIKE ?', ["%$videyId%"])
        ->orWhereRaw('LOWER(url) LIKE ?', ["%$videyId%"])
        ->orWhereRaw('LOWER(title) LIKE ?', ["%$videyId%"])
        ->first();

    if ($video) {
        echo "   [MATCHED] Found Video ID: {$video->id} | Slug: {$video->slug}\n";
        
        $multiHost = app(\App\Services\MultiHostService::class);
        $mirrorUrl = "https://doodapi.co/e/$fileCode";
        
        // Update DB
        $multiHost->updateStatus($video, 'doodstream', 'success', $mirrorUrl);
        echo "   [SUCCESS] Synced to database.\n";
        $matchedCount++;
    } else {
        echo "   [NO MATCH] Could not find any video record for this ID.\n";
    }
}

echo "\n=========================================\n";
echo "Smart Matching Results:\n";
echo "Total Records Processed: " . count($files) . "\n";
echo "Total Matches Synced   : $matchedCount\n";
echo "=========================================\n";
