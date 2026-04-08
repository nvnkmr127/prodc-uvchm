<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // This ensures a batch exists before trying to assign one
        $batch = \App\Models\Batch::inRandomOrder()->first() ?? \App\Models\Batch::factory()->create();

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'father_name' => fake()->name('male'),
            'student_mobile' => fake()->numerify('##########'),
            'father_mobile' => fake()->numerify('##########'),
            'village' => fake()->city(),
            'admission_date' => fake()->dateTimeBetween('-1 years', 'now'),
            'enrollment_number' => 'ENR-'.fake()->unique()->randomNumber(8),
            'status' => 'active',
            'batch_id' => $batch->id, // Assign to a random, existing batch
            'admission_id' => null,
        ];
    }
}
