<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $key = '561492l0isghhqftfzqpnv';
    \App\Models\Setting::setValue('doodstream_key', $key);
    echo "SUCCESS: Doodstream API Key has been injected into the database.\n";
    echo "STATUS: Doodstream Driver is now ACTIVE.\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
