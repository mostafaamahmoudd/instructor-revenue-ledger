<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionRefund;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SubscriptionRefund>
 */
class SubscriptionRefundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'amount_minor' => 1000,
            'idempotency_key' => (string)Str::uuid(),
            'status' => 'pending',
            'requested_at' => now(),
            'created_at' => now(),
        ];
    }
}
