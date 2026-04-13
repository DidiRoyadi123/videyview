<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\Video;
use App\Services\MultiHostService;

$slug = "59zA6NvB1";
$video = Video::where('slug', 'like', '%'.$slug.'%')->first();

if ($video) {
    echo "Video Found: " . $video->slug . "\n";
    $service = app(MultiHostService::class);
    $service->updateStatus($video, 'doodstream', 'success', 'https://doodapi.co/e/VERIFIED_SYNC_TEST');
    
    $v2 = $video->fresh();
    echo "Saved Links: " . json_encode($v2->mirror_links) . "\n";
} else {
    echo "Video Not Found: " . $slug . "\n";
}
