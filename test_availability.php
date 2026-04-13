<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$time = time();
$job = \Illuminate\Support\Facades\DB::table('jobs')->where('queue', 'checkers')->first();
if($job) {
    echo "Current time: $time\n";
    echo "Available at: {$job->available_at}\n";
    echo "Difference: " . ($job->available_at - $time) . " seconds\n";
    echo "Reserved at: " . ($job->reserved_at ?? 'Not reserved') . "\n";
} else {
    echo "No jobs in checkers queue.\n";
}
