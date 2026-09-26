<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plan = fake()->randomElement(['monthly', 'quarterly', 'annual']);
        $months = match ($plan) {
            'monthly' => 1,
            'quarterly' => 3,
            'annual' => 12
        };
        $start = fake()->dateTimeBetween('-2 months', 'now');

        return [
            'student_id' => Student::factory(),
            'plan' => $plan,
            'amount_minor' => match ($plan) {
                'monthly' => 2900,
                'quarterly' => 7900,
                'annual' => 29900
            },
            'currency' => 'USD',
            'starts_at' => $start,
            'ends_at' => (clone $start)->modify("+{$months} months"),
            'status' => 'active',
        ];
    }
}
