<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Setting;

$ads = [
    'ad_banner_728x90' => '<script>atOptions = {\'key\' : \'31e4cf4cdad8747a32fd14d1f6004d96\',\'format\' : \'iframe\',\'height\' : 90,\'width\' : 728,\'params\' : {}};</script><script src="https://recollectsideway.com/31e4cf4cdad8747a32fd14d1f6004d96/invoke.js"></script>',
    'ad_banner_300x250' => '<script>atOptions = {\'key\' : \'15a61d8a2b88f87510d1f7b44e609e49\',\'format\' : \'iframe\',\'height\' : 250,\'width\' : 300,\'params\' : {}};</script><script src="https://recollectsideway.com/15a61d8a2b88f87510d1f7b44e609e49/invoke.js"></script>',
    'ad_banner_468x60' => '<script>atOptions = {\'key\' : \'15d9f441fd0cc15fa44df9ab48e755b2\',\'format\' : \'iframe\',\'height\' : 60,\'width\' : 468,\'params\' : {}};</script><script src="https://recollectsideway.com/15d9f441fd0cc15fa44df9ab48e755b2/invoke.js"></script>',
];

foreach ($ads as $key => $value) {
    Setting::setValue($key, $value);
    echo "Set $key: SUCCESS\n";
}
echo "All ads updated.\n";
