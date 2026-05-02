<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Update existing checkers in queue to be on 'checkers' queue
$updated = DB::table('jobs')
    ->where('payload', 'like', '%CheckRemoteUploadJob%')
    ->update(['queue' => 'checkers']);

echo "Moved $updated jobs to 'checkers' queue.\n";

// Also let's check one manually right now just to update Dashboard
$videos = App\Models\Video::where('hosting_status', 'like', '%uploading%')->take(10)->get();
$service = app(App\Services\MultiHostService::class);

foreach($videos as $video) {
    /** @var \App\Models\Video $video */
    // Check which host is uploading
    foreach($video->hosting_status as $host => $status) {
        if ($status === 'uploading') {
            $driver = $service->driver($host);
            if (!$driver) continue;

            // Try to find its remote_id from queue payload if possible (hacky but works for now)
            $payload = DB::table('jobs')->where('payload', 'like', "%{$video->id}%")->where('payload', 'like', '%CheckRemoteUploadJob%')->value('payload');
            if($payload) {
                $data = json_decode($payload, true);
                $command = unserialize($data['data']['command']);
                $reflection = new ReflectionClass($command);
                
                // Try to get remoteId property
                try {
                    $property = $reflection->getProperty('remoteId');
                    $property->setAccessible(true);
                    $remoteId = $property->getValue($command);
                    
                    $statusData = $driver->checkRemoteStatus($remoteId);
                    if(isset($statusData['extid']) && $statusData['extid']) {
                        $service->updateStatus($video, $host, 'success', ['embed_url' => "https://{$host}.com/e/".$statusData['extid']]);
                        echo "Manually marked success for video {$video->id} on {$host}\n";
                    }
                } catch (\Exception $e) {
                    echo "Could not resolve remoteId for video {$video->id}: " . $e->getMessage() . "\n";
                }
            }
        }
    }
}
