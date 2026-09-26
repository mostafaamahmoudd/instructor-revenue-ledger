<?php

namespace Database\Factories;

use App\Models\PayoutBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayoutBatch>
 */
class PayoutBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'period_key' => now()->format('Y-m'),
            'triggered_at' => now(),
            'status' => 'running',
            'created_at' => now(),
        ];
    }
}
