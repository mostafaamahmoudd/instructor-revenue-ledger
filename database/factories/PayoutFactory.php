<?php

namespace Database\Factories;

use App\Models\Instructor;
use App\Models\Payout;
use App\Models\PayoutBatch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payout>
 */
class PayoutFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payout_batch_id' => PayoutBatch::factory(),
            'instructor_id' => Instructor::factory(),
            'instructor_type' => 'instructor',
            'period_key' => now()->format('Y-m'),
            'amount_minor' => 1000,
            'currency' => 'USD',
            'status' => 'pending',
            'provider_idempotency_key' => (string)Str::uuid(),
            'attempts' => 0,
        ];
    }
}
