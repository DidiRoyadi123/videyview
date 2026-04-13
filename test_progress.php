<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$videoPath = storage_path('app/public/videos/test.mp4');
file_put_contents($videoPath, str_repeat('A', 1024 * 1024 * 10)); // 10MB test file

echo "Starting upload test...\n";

// simulate streamtape upload
try {
    $uploadResponse = Http::withOptions([
        'progress' => function ($downloadTotal, $downloadedBytes, $uploadTotal, $uploadedBytes) {
            echo "Progress: $uploadedBytes / $uploadTotal (Download: $downloadedBytes / $downloadTotal)\n";
        }
    ])->attach('file1', fopen($videoPath, 'r'), basename($videoPath))
    // we use a dummy endpoint that accepts POST like httpbin
    ->post('https://httpbin.org/post');

    echo "Status: " . $uploadResponse->status() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

unlink($videoPath);
