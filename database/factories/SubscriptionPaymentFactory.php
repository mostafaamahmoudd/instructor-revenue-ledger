<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SubscriptionPayment>
 */
class SubscriptionPaymentFactory extends Factory
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
            'amount_minor' => 2900,
            'platform_cut_minor' => 580,
            'currency' => 'USD',
            'idempotency_key' => (string)Str::uuid(),
            'status' => 'confirmed',
            'paid_at' => now(),
            'created_at' => now(),
        ];
    }
}
