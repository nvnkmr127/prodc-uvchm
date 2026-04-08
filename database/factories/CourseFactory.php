<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Diploma in Hotel Management', 'Advanced Diploma in Hotel Management', 'Master in Hotel Management']),
            'duration_in_years' => fake()->randomElement([1, 1.5, 2]),
            'max_batch_size' => 30,
            'description' => fake()->paragraph(),
        ];
    }
}
