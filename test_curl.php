<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $response = Illuminate\Support\Facades\Http::withoutVerifying()->get('https://api.streamtape.to/account/info', [
        'login' => 'ba1402c48869ff3f5d71',
        'key' => 'ZKbxp4w8VjIqxXb'
    ]);
    
    echo "Status: " . $response->status() . "\n";
    echo "Body: " . $response->body() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
