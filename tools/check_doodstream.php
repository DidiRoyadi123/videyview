<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $videos = \App\Models\Video::where('hosting_status', 'like', '%doodstream%')->get();
    
    if ($videos->isEmpty()) {
        echo "RESULT: No videos found with Doodstream mirroring status.\n";
    } else {
        echo "RESULT: Found " . $videos->count() . " videos with Doodstream status.\n";
        foreach ($videos as $v) {
            $status = $v->hosting_status['doodstream'] ?? 'unknown';
            $link = $v->mirror_links['doodstream'] ?? 'N/A';
            echo "- [{$v->id}] {$v->title}: Status={$status}, Link={$link}\n";
            if (str_contains($status, 'failed')) {
                echo "  ERROR_DETAIL: " . $status . "\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
