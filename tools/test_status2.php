<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$driver = app(App\Services\MultiHostService::class)->driver('streamtape');
echo json_encode($driver->checkRemoteStatus('Te8NdEId-to'), JSON_PRETTY_PRINT);
