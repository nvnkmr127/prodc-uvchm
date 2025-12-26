<?php

use App\Services\ComponentPaymentService;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Verifying Analytics Queries...\n";

$service = app(ComponentPaymentService::class);
$reflection = new ReflectionClass($service);

// 1. Verify getPaymentFrequency
echo "1. Testing getPaymentFrequency...\n";
$methodFreq = $reflection->getMethod('getPaymentFrequency');
$methodFreq->setAccessible(true);
try {
    $freq = $methodFreq->invoke($service);
    echo "Success! Result: " . json_encode($freq) . "\n";
} catch (Exception $e) {
    echo "Error in getPaymentFrequency: " . $e->getMessage() . "\n";
}

// 2. Verify getSeasonalPatterns
echo "2. Testing getSeasonalPatterns...\n";
$methodSeason = $reflection->getMethod('getSeasonalPatterns');
$methodSeason->setAccessible(true);
try {
    $season = $methodSeason->invoke($service);
    echo "Success! Result: " . json_encode($season) . "\n";
} catch (Exception $e) {
    echo "Error in getSeasonalPatterns: " . $e->getMessage() . "\n";
}

echo "Done.\n";
