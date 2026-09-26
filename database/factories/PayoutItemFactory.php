<?php

namespace Database\Factories;

use App\Models\EarningSchedule;
use App\Models\Payout;
use App\Models\PayoutItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayoutItem>
 */
class PayoutItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payout_id' => Payout::factory(),
            'earning_schedule_id' => EarningSchedule::factory()->recognized(),
            'amount_minor' => 100,
        ];
    }
}
