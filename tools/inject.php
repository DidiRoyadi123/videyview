<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$txt = file_get_contents(__DIR__ . '/storage/app/doodstream_sync.txt');
$txt = mb_convert_encoding($txt, 'UTF-8', 'UTF-16LE');

// Simple validation to ensure we grabbed what we think we grabbed
if (strpos($txt, 'playmogo.com') === false && strpos($txt, 'dood') === false) {
    echo "Clipboard does not contain valid doodstream links.\n";
    exit(1);
}

// Ensure VideoDistributionService exists and is used
$service = app(\App\Services\VideoDistributionService::class);
$matched = $service->bulkSyncLinks($txt, '1'); // Pass User ID 1 for admin UI progress tracking

echo "Successfully injected/matched " . $matched . " videos from clipboard text.\n";
