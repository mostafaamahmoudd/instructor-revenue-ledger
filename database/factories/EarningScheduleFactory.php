<?php

namespace Database\Factories;

use App\Models\EarningSchedule;
use App\Models\Instructor;
use App\Models\SubscriptionPaymentAllocation;
use App\Models\SubscriptionRefund;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EarningSchedule>
 */
class EarningScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'allocation_id' => SubscriptionPaymentAllocation::factory(),
            'instructor_id' => Instructor::factory(),
            'earn_date' => fake()->dateTimeBetween('-6 months', '+6 months')->format('Y-m-d'),
            'amount_minor' => 100,
            'currency' => 'USD',
            'created_at' => now(),
        ];
    }

    public function recognized(): static
    {
        return $this->state(fn() => ['recognized_at' => now()]);
    }

    public function voided(?SubscriptionRefund $refund = null): static
    {
        return $this->state(fn() => [
            'voided_at' => now(),
            'voided_by_refund_id' => $refund?->id ?? SubscriptionRefund::factory(),
        ]);
    }
}
