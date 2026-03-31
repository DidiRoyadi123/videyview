<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Setting;

$data = [
    'ad_social_bar' => '<script src="https://recollectsideway.com/7e/71/b2/7e71b2bea03207179a6711c6acc7eb8c.js"></script>',
    'ad_popunder' => '<script src="https://recollectsideway.com/f7/8c/03/f78c03cdb21a4d953f9fb33f640a09af.js"></script>',
    'ad_smartlink' => 'https://recollectsideway.com/y7n41hja?key=e44b00287bb151517c07f7b7388920bb'
];

foreach ($data as $key => $value) {
    Setting::setValue($key, $value);
    echo "Saved $key\n";
}
echo "All ads updated successfully!\n";
