<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Student;
use Illuminate\Support\Facades\Storage;

echo "Checking student photos...\n";

$students = Student::whereNotNull('photo')->where('photo', '!=', '')->limit(5)->get();

if ($students->count() === 0) {
    echo "No students with photos found in DB.\n";
}

foreach ($students as $student) {
    echo "\n------------------------------------------------\n";
    echo "Student: " . $student->name . " (ID: " . $student->id . ")\n";
    echo "DB Photo Value: '" . $student->photo . "'\n";

    $assetUrl = asset('storage/' . $student->photo);
    echo "Asset URL: " . $assetUrl . "\n";

    $cleanPath = str_replace(['public/', 'storage/'], '', $student->photo);
    $storagePath = storage_path('app/public/' . $cleanPath);
    $publicPath = public_path('storage/' . $student->photo);

    echo "Check Storage Path ($storagePath): " . (file_exists($storagePath) ? "EXISTS" : "MISSING") . "\n";
    echo "Check Public Path ($publicPath): " . (file_exists($publicPath) ? "EXISTS" : "MISSING") . "\n";

    // Also check direct public path if 'public/' is in string
    if (strpos($student->photo, 'public/') === 0) {
        $directPublic = public_path('storage/' . str_replace('public/', '', $student->photo));
        echo "Check Strip Public ($directPublic): " . (file_exists($directPublic) ? "EXISTS" : "MISSING") . "\n";
    }
}
