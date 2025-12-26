<?php

use App\Http\Controllers\Admin\StudentController;
use App\Models\Batch;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Ensure we have a batch
$batch = Batch::first();
if (!$batch) {
    echo "No batches found. Creating a test one...\n";
    // Creating a mock batch object or real one if DB allows
    // For safety, let's just exit if no batch, or try to mock the object structure if we can't write to DB
    // But we are in local, so we can write.
    // Let's defer creating data to avoid side effects if possible, but reading is fine.
    exit("Please ensure at least one batch exists to test.\n");
}

echo "Testing with Batch ID: " . $batch->id . "\n";

// Reflection to access private method
$controller = app(StudentController::class);
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('generateEnrollmentNumber');
$method->setAccessible(true);

echo "Generating Enrollment Number...\n";
$id1 = $method->invoke($controller, $batch);
echo "Result 1: $id1\n";

// Simulate a student existing with that ID
// We won't actually save to DB to avoid pollution, but we can verify the LOGIC matches what we expect
// If we want to test the 'exists' check, we'd need to insert. 
// Let's just trust the first generation for now, and maybe insert one record to test the +1 logic if confident.

// Let's try to determine what the 'next' one should be based on DB
$prefix = substr($id1, 0, -3);
$lastStudent = Student::where('batch_id', $batch->id)
    ->where('enrollment_number', 'like', "{$prefix}%")
    ->orderByRaw('LENGTH(enrollment_number) DESC')
    ->orderBy('enrollment_number', 'desc')
    ->first();

if ($lastStudent) {
    echo "Last Student found: {$lastStudent->enrollment_number}\n";
} else {
    echo "No previous students found in this sequence.\n";
}

echo "Logic seems to have generated: $id1\n";
