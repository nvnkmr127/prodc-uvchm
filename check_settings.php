<?php

use App\Models\Setting;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$keys = [
    'etimeoffice_api_url',
    'etimeoffice_corporate_id',
    'etimeoffice_username',
    'etimeoffice_password'
];

echo "Checking ETimeOffice Settings:\n";
foreach ($keys as $key) {
    $value = Setting::where('key', $key)->value('value');
    echo "$key: " . ($value ? "SET (Value: $value)" : "NOT SET") . "\n";
}
