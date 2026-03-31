<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Video;

$videos = Video::all();
$count = 0;
foreach ($videos as $v) {
    if (!$v->slug) continue;
    
    $old = storage_path('app/public/thumbnails/' . $v->id . '.jpg');
    $new = storage_path('app/public/thumbnails/' . $v->slug . '.jpg');
    
    if (file_exists($old)) {
        if (file_exists($new)) {
            unlink($old); // New one already exists, just remove old
        } else {
            rename($old, $new);
        }
        $count++;
        echo "Renamed ID {$v->id} to Slug {$v->slug}\n";
    }
}
echo "Done! Processed $count files.\n";
