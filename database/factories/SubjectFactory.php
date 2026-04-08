<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Front Office Operations', 'Food & Beverage Production', 'Housekeeping Management',
                'Hospitality Law', 'Financial Management in Hospitality', 'Event Management',
                'Human Resource Management', 'Tourism Marketing', 'Advanced Culinary Arts',
            ]),
            'code' => strtoupper(fake()->lexify('???')).fake()->numerify('###'),
            'requires_lab' => fake()->boolean(30), // 30% chance of requiring a lab
        ];
    }
}
