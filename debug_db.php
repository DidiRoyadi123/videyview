<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TABLE STRUCTURE: videos ===\n";
$columns = DB::select('SHOW COLUMNS FROM videos');
foreach ($columns as $column) {
    echo "Field: {$column->Field} | Type: {$column->Type}\n";
}

echo "\n=== SAMPLE DATA (5 Latest) ===\n";
$samples = DB::table('videos')->latest()->limit(5)->get();
foreach ($samples as $sample) {
    print_r($sample);
}
