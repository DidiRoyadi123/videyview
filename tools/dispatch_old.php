<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$videos = App\Models\Video::where('download_status', 'pending')->where('url', 'like', '%videy.co%')->get();
$count = 0;
foreach ($videos as $video) {
    if(!isset($video->hosting_status['streamtape']) || ($video->hosting_status['streamtape'] !== 'success' && $video->hosting_status['streamtape'] !== 'uploading' && $video->hosting_status['streamtape'] !== 'pending')) {
        App\Jobs\DistributeRemoteHostJob::dispatch($video, 'streamtape', $video->url);
        $count++;
    }
}
echo "Dispatched $count jobs\n";
