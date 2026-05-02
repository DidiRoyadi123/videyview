<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

list($remoteId, $vidId) = \Illuminate\Support\Facades\DB::table('jobs')
    ->where('payload', 'like', '%CheckRemoteUploadJob%')
    ->get()
    ->map(function($job) {
        $data = json_decode($job->payload, true);
        $command = unserialize($data['data']['command']);
        $reflection = new ReflectionClass($command);
        
        $property = $reflection->getProperty('remoteId');
        $property->setAccessible(true);
        $remoteId = $property->getValue($command);
        
        $propertyVideo = $reflection->getProperty('video');
        $propertyVideo->setAccessible(true);
        $video = $propertyVideo->getValue($command);

        return [$remoteId, $video->id];
    })->first();

if($remoteId) {
    echo "Testing Remote ID: $remoteId for video $vidId\n";
    $driver = app(App\Services\MultiHostService::class)->driver('streamtape');
    print_r($driver->checkRemoteStatus($remoteId));
} else {
    echo "No checker jobs left.";
}
