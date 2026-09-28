<?php

namespace Tests\Feature;

use App\Models\EarningSchedule;
use App\Models\Instructor;
use App\Models\Payout;
use App\Models\PayoutItem;
use App\Services\InstructorBalanceSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorBalanceSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_separates_recognized_paid_available_and_reserved_earnings(): void
    {
        $instructor = Instructor::factory()->create();

        EarningSchedule::factory()->recognized()->create([
            'instructor_id' => $instructor->id,
            'currency' => 'USD',
            'amount_minor' => 1000,
        ]);

        $paid = EarningSchedule::factory()->recognized()->create([
            'instructor_id' => $instructor->id,
            'currency' => 'USD',
            'amount_minor' => 2000,
        ]);
        PayoutItem::factory()->create([
            'earning_schedule_id' => $paid->id,
            'amount_minor' => 2000,
            'payout_id' => Payout::factory()->create([
                'instructor_id' => $instructor->id,
                'currency' => 'USD',
                'status' => Payout::STATUS_SUCCEEDED,
            ]),
        ]);

        $reserved = EarningSchedule::factory()->recognized()->create([
            'instructor_id' => $instructor->id,
            'currency' => 'USD',
            'amount_minor' => 3000,
        ]);
        PayoutItem::factory()->create([
            'earning_schedule_id' => $reserved->id,
            'amount_minor' => 3000,
            'payout_id' => Payout::factory()->create([
                'instructor_id' => $instructor->id,
                'currency' => 'USD',
                'period_key' => '2026-10',
                'status' => Payout::STATUS_UNCERTAIN,
            ]),
        ]);

        EarningSchedule::factory()->create([
            'instructor_id' => $instructor->id,
            'currency' => 'USD',
            'amount_minor' => 4000,
        ]);
        EarningSchedule::factory()->recognized()->voided()->create([
            'instructor_id' => $instructor->id,
            'currency' => 'USD',
            'amount_minor' => 5000,
        ]);

        $summary = app(InstructorBalanceSummary::class)->forInstructor($instructor);

        $this->assertSame([
            'recognized_minor' => 6000,
            'paid_minor' => 2000,
            'available_minor' => 1000,
            'reserved_minor' => 3000,
            'outstanding_minor' => 4000,
        ], $summary['USD']);
    }
}
