<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicYear;

class AcademicYearSeeder extends Seeder
{
    public function run()
    {
        $academicYears = [
            [
                'name' => '2023-2024',
                'start_date' => '2023-04-01',
                'end_date' => '2024-03-31',
                'is_current' => false
            ],
            [
                'name' => '2024-2025',
                'start_date' => '2024-04-01',
                'end_date' => '2025-03-31',
                'is_current' => false
            ],
            [
                'name' => '2025-2026',
                'start_date' => '2025-04-01',
                'end_date' => '2026-03-31',
                'is_current' => true  // Mark as current
            ],
            [
                'name' => '2026-2027',
                'start_date' => '2026-04-01',
                'end_date' => '2027-03-31',
                'is_current' => false
            ]
        ];

        foreach ($academicYears as $year) {
            AcademicYear::updateOrCreate(
                ['name' => $year['name']],
                $year
            );
        }

        $this->command->info('Academic years created successfully!');
    }
}