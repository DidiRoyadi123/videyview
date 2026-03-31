<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$v = App\Models\Video::where('title', 'LIKE', '%fvtrraoi1%')->first();
if ($v) {
    echo "ID: " . $v->id . "\n";
    echo "TITLE: " . $v->title . "\n";
    echo "SLUG: " . $v->slug . "\n";
    echo "THUMB: " . $v->thumbnail_url . "\n";
    echo "STATUS: " . $v->download_status . "\n";
} else {
    echo "Not found\n";
}
