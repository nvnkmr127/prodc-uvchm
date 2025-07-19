<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    // Create 200 students using the StudentFactory blueprint
    \App\Models\Student::factory()->count(200)->create();
}
}
