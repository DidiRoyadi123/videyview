<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$out = "DATABASE CHECK:\n";
$videos = App\Models\Video::where(function($q) {
    $q->where('download_status', '!=', 'completed')
      ->orWhereNull('download_status');
})
->orWhereNull('thumbnail_url')
->get();

$out .= "Total videos to sync: " . count($videos) . "\n";
$target = $videos->where('id', 2752)->first();
$out .= "Video ID 2752 in sync list: " . ($target ? 'YES' : 'NO') . "\n";

foreach ($videos as $v) {
    $out .= "-------------------\n";
    $out .= "ID: [" . $v->id . "]\n";
    $out .= "TITLE: [" . $v->title . "]\n";
    $out .= "SLUG: [" . $v->slug . "]\n";
    $out .= "THUMB: [" . $v->thumbnail_url . "]\n";
    $out .= "STATUS: [" . $v->download_status . "]\n";
    
    $path = 'videos/' . $v->slug . '.mp4';
    $exists = \Illuminate\Support\Facades\Storage::disk('public')->exists($path);
    $out .= "FILE_EXISTS (" . $path . "): [" . ($exists ? 'YES' : 'NO') . "]\n";
    
    $thumbPath = 'thumbnails/' . $v->slug . '.jpg';
    $thumbExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($thumbPath);
    $out .= "THUMB_EXISTS (" . $thumbPath . "): [" . ($thumbExists ? 'YES' : 'NO') . "]\n";
}

file_put_contents('debug_output.txt', $out);
echo "Done. Check debug_output.txt\n";
