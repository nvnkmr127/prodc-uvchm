<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Student;
use Illuminate\Support\Facades\Storage;

echo "\n=== Configuration ===\n";
echo "APP_URL: " . config('app.url') . "\n";
echo "ASSET_URL: " . config('app.asset_url') . "\n";

echo "\n=== URL Generation ===\n";
$testFile = 'student_photos/test.jpg';
echo "Test File: $testFile\n";
echo "asset('storage/' . \$testFile): " . asset('storage/' . $testFile) . "\n";
echo "Storage::url(\$testFile): " . Storage::url($testFile) . "\n";
echo "Storage::disk('public')->url(\$testFile): " . Storage::disk('public')->url($testFile) . "\n";

echo "\n=== Student Data Sample ===\n";
$student = Student::whereNotNull('photo')->where('photo', '!=', '')->first();
if ($student) {
    echo "Student ID: " . $student->id . "\n";
    echo "DB Photo Value: '" . $student->photo . "'\n";
    echo "Storage::exists(photo): " . (Storage::disk('public')->exists($student->photo) ? 'YES' : 'NO') . "\n";
    $cleaned = str_replace(['public/', 'storage/'], '', $student->photo);
    echo "Storage::exists(cleaned): " . (Storage::disk('public')->exists($cleaned) ? 'YES' : 'NO') . "\n";
} else {
    echo "No student with photo found.\n";
}

echo "\n=== Filesystem Check ===\n";
echo "public/storage link target: " . @readlink(public_path('storage')) . "\n";
