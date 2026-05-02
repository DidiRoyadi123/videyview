<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$mismatches = [];
$total = 0;
\App\Models\Video::where('download_status', 'completed')->whereNotNull('local_path')->get()->each(function($v) use (&$mismatches, &$total) {
    $total++;
    $cleanPath = ltrim(str_replace('\\', '/', $v->local_path), '/');
    $path = storage_path('app/public/' . $cleanPath);
    if (!file_exists($path)) {
        $mismatches[] = [
            'id' => $v->id,
            'slug' => $v->slug,
            'db_path' => $v->local_path,
            'full_path' => $path
        ];
    }
});

echo "Total local videos in DB: $total\n";
echo "Total mismatches (file missing): " . count($mismatches) . "\n";
foreach (array_slice($mismatches, 0, 10) as $m) {
    echo "ID {$m['id']}: {$m['db_path']}\n";
}
