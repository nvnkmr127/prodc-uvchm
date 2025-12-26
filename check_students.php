<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use App\Models\Admission;

// 1. Check Total Counts
echo "Total Students: " . Student::withoutGlobalScope('academic_year')->count() . "\n";
echo "Total Admissions: " . Admission::withoutGlobalScope('academic_year')->count() . "\n";

// 2. Check Students with Referrals
$studentsWithReferrals = Student::withoutGlobalScope('academic_year')
    ->whereNotNull('referral_name')
    ->where('referral_name', '!=', '')
    ->get();

echo "Students with non-empty referral_name: " . $studentsWithReferrals->count() . "\n";

// 3. Check how many of these have linked Admissions
$linkedCount = 0;
$orphanCount = 0;

foreach ($studentsWithReferrals as $student) {
    // Check if student has admission_id and it exists
    if ($student->admission_id) {
        $adm = Admission::withoutGlobalScope('academic_year')->find($student->admission_id);
        if ($adm) {
            $linkedCount++;
        } else {
            echo " - Student ID {$student->id} has admission_id {$student->admission_id} but Admission not found.\n";
            $orphanCount++;
        }
    } else {
        // Double check reverse check if Admission points to Student?? 
        // Usually admission_id on student is the link if hasOne is used on Admission.
        echo " - Student ID {$student->id} ({$student->name}) has NO admission_id.\n";
        $orphanCount++;
    }
}

echo "Linked to valid Admission: $linkedCount\n";
echo "Orphan / No Admission: $orphanCount\n";

// 4. Sample Referral Data
if ($studentsWithReferrals->count() > 0) {
    echo "\nSample Referral Data from Students:\n";
    foreach ($studentsWithReferrals->take(5) as $s) {
        echo " - Name: {$s->name}, Referral: {$s->referral_name}, Source: {$s->source}\n";
    }
} else {
    echo "\nNo referral data found in Students table at all.\n";
}
