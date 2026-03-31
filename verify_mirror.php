<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$allStatuses = App\Models\Video::whereNotNull('hosting_status')->pluck('hosting_status');
$counts = [
    'videy' => 0, 
    'doodstream' => 0,
    'streamtape' => 0,
    'filemoon' => 0,
    'seekstreaming' => 0,
];

foreach ($allStatuses as $status) {
    if (is_array($status)) {
        foreach ($counts as $host => $cnt) {
            if (isset($status[$host]) && $status[$host] === 'success') {
                $counts[$host]++;
            }
        }
    }
}

foreach ($counts as $host => $count) {
    echo ucfirst($host) . " Success Count: " . $count . "\n";
}
