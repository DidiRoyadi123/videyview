<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$v = App\Models\Video::where('title', 'LIKE', '%fvtrRAoi1%')->first();
if ($v) {
    echo "ID: [" . $v->id . "]\n";
    echo "TITLE: [" . $v->title . "]\n";
    echo "SLUG: [" . $v->slug . "]\n";
    echo "THUMB: [" . $v->thumbnail_url . "]\n";
    echo "STATUS: [" . $v->download_status . "]\n";
    
    $path = 'videos/' . $v->slug . '.mp4';
    $exists = \Illuminate\Support\Facades\Storage::disk('public')->exists($path);
    echo "FILE_EXISTS (" . $path . "): [" . ($exists ? 'YES' : 'NO') . "]\n";
    
    $thumbPath = 'thumbnails/' . $v->slug . '.jpg';
    $thumbExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($thumbPath);
    echo "THUMB_EXISTS (" . $thumbPath . "): [" . ($thumbExists ? 'YES' : 'NO') . "]\n";

} else {
    echo "Video not found\n";
}
