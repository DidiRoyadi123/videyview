<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Video;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

$apiKey = Setting::where('key', 'doodstream_key')->first()?->value;

echo "=== DATABASE SAMPLES (10 Latest) ===\n";
foreach(Video::latest()->take(10)->get() as $v) {
    echo "ID: {$v->id} | Slug: {$v->slug} | Title: {$v->title}\n";
}

echo "\n=== DOODSTREAM FILES (All 17) ===\n";
$response = Http::withoutVerifying()->withOptions([
    'curl' => [CURLOPT_RESOLVE => ["doodapi.co:443:172.67.70.170"]],
])->get("https://doodapi.co/api/file/list?key=$apiKey");

$files = $response->json()['result']['files'] ?? [];
foreach($files as $f) {
    echo "Dood Title: {$f['title']} | Code: {$f['file_code']}\n";
}
