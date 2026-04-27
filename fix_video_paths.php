<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$videosPath = storage_path('app/public/videos');
$allFiles = scandir($videosPath);
$allFiles = array_filter($allFiles, function($f) { return $f !== '.' && $f !== '..'; });

$mismatches = [];
$fixed = 0;
$total = 0;

\App\Models\Video::where('download_status', 'completed')->chunk(100, function($videos) use (&$mismatches, &$fixed, &$total, $videosPath) {
    foreach ($videos as $v) {
        /** @var \App\Models\Video $v */
        $total++;
        $cleanPath = ltrim(str_replace('\\', '/', $v->local_path ?? ''), '/');
        $path = $cleanPath ? storage_path('app/public/' . $cleanPath) : null;
        
        if ($path && file_exists($path)) {
            continue;
        }

        // Try to find the file
        $found = false;
        $possibleNames = [
            "{$v->id}.mp4",
            "video-{$v->slug}.mp4",
            "{$v->slug}.mp4"
        ];

        foreach ($possibleNames as $name) {
            if (file_exists("$videosPath/$name")) {
                $v->local_path = "videos/$name";
                $v->url = "/storage/videos/$name";
                $v->save();
                $fixed++;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $mismatches[] = [
                'id' => $v->id,
                'slug' => $v->slug,
                'db_path' => $v->local_path
            ];
        }
    }
});

echo "Total local videos checked: $total\n";
echo "Total fixed (path updated): $fixed\n";
echo "Total remaining mismatches (file still missing): " . count($mismatches) . "\n";
foreach (array_slice($mismatches, 0, 10) as $m) {
    echo "ID {$m['id']}: DB says '{$m['db_path']}', slug is '{$m['slug']}'\n";
}
