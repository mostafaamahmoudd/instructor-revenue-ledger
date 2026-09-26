<?php

namespace Database\Factories;

use App\Models\Instructor;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPaymentAllocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPaymentAllocation>
 */
class SubscriptionPaymentAllocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_payment_id' => SubscriptionPayment::factory(),
            'instructor_id' => Instructor::factory(),
            'amount_minor' => 1160,
            'currency' => 'USD',
            'created_at' => now(),
        ];
    }
}
