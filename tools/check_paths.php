<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Video;

foreach (Video::whereNotNull('local_path')->get() as $video) {
    echo "ID: {$video->id}\n";
    echo "Title: {$video->title}\n";
    echo "Local Path: {$video->local_path}\n";
    echo "Full Path Check: " . storage_path('app/public/' . $video->local_path) . "\n";
    echo "File Exists: " . (file_exists(storage_path('app/public/' . $video->local_path)) ? 'YES' : 'NO') . "\n";
    echo "-------------------\n";
}
