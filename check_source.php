<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;

// Check Source Column
echo "Checking Source Column...\n";
$studentsWithSource = Student::withoutGlobalScope('academic_year')
    ->whereNotNull('source')
    ->where('source', '!=', '')
    ->get();

echo "Students with non-empty 'source': " . $studentsWithSource->count() . "\n";
foreach ($studentsWithSource as $s) {
    echo "ID: {$s->id}, Source: {$s->source}, Referral Name: '{$s->referral_name}'\n";
}
