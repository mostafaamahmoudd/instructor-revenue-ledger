<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InstructorBalanceSnapshot>
 */
class InstructorBalanceSnapshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'instructor_id' => \App\Models\Instructor::factory(),
            'currency' => 'USD',
            'earned_total_minor' => 0,
            'paid_total_minor' => 0,
            'computed_at' => now(),
        ];
    }
}
