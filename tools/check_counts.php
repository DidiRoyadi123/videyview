<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Video;

$totalCompleted = Video::where('download_status', 'completed')->count();
$completedWithoutThumb = Video::where('download_status', 'completed')
    ->where(function($q){
        $q->whereNull('thumbnail_url')->orWhere('thumbnail_url', '');
    })->count();

echo "Total Completed: $totalCompleted\n";
echo "Completed without Thumb: $completedWithoutThumb\n";

$sample = Video::where('download_status', 'completed')->limit(5)->get();
foreach($sample as $v) {
    echo "ID: {$v->id}, Title: {$v->title}, Thumb: '{$v->thumbnail_url}', Local: '{$v->local_path}'\n";
}
