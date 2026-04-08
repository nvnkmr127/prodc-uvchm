<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Batch>
 */
class BatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start_date = fake()->dateTimeBetween('now', '+1 month');

        return [
            'course_id' => \App\Models\Course::factory(), // Associates with a course
            'name' => 'Intake '.fake()->monthName().' '.fake()->year(),
            'start_date' => $start_date,
            'end_date' => (clone $start_date)->modify('+1 year'),
        ];
    }
}
