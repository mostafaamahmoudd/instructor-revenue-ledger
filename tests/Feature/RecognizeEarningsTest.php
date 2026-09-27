<?php

namespace Tests\Feature;

use App\Models\EarningSchedule;
use App\Models\SubscriptionPaymentAllocation;
use App\Services\RecognizeEarnings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RecognizeEarningsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_only_due_unrecognized_unvoided_rows_get_recognized(): void
    {
        $this->markExistingSchedulesRecognized();

        $due = $this->createSchedule([
            'earn_date' => now()->subDay()->toDateString(),
            'recognized_at' => null,
            'voided_at' => null,
        ]);
        $future = $this->createSchedule([
            'earn_date' => now()->addDay()->toDateString(),
            'recognized_at' => null,
            'voided_at' => null,
        ]);
        $alreadyRecognized = $this->createSchedule([
            'earn_date' => now()->subDay()->toDateString(),
            'recognized_at' => now()->subHour(),
            'voided_at' => null,
        ]);
        $voided = $this->createSchedule([
            'earn_date' => now()->subDay()->toDateString(),
            'recognized_at' => null,
            'voided_at' => now(),
        ]);

        $recognized = app(RecognizeEarnings::class)->run(now());

        $this->assertSame(1, $recognized);
        $this->assertNotNull($due->fresh()->recognized_at);
        $this->assertNull($future->fresh()->recognized_at);
        $this->assertNotNull($alreadyRecognized->fresh()->recognized_at);
        $this->assertNull($voided->fresh()->recognized_at);
    }

    public function test_running_twice_recognizes_zero_additional_rows_second_time(): void
    {
        $this->markExistingSchedulesRecognized();

        $this->createSchedule([
            'earn_date' => now()->subDay()->toDateString(),
            'recognized_at' => null,
            'voided_at' => null,
        ]);

        $service = app(RecognizeEarnings::class);

        $this->assertSame(1, $service->run(now()));
        $this->assertSame(0, $service->run(now()));
    }

    public function test_past_voided_row_is_not_recognized(): void
    {
        $this->markExistingSchedulesRecognized();

        $voided = $this->createSchedule([
            'earn_date' => now()->subDay()->toDateString(),
            'recognized_at' => null,
            'voided_at' => now(),
        ]);

        $this->assertSame(0, app(RecognizeEarnings::class)->run(now()));
        $this->assertNull($voided->fresh()->recognized_at);
    }

    private function createSchedule(array $attributes): EarningSchedule
    {
        $allocation = SubscriptionPaymentAllocation::factory()->create();

        return EarningSchedule::factory()->create(array_merge([
            'allocation_id' => $allocation->id,
            'instructor_id' => $allocation->instructor_id,
            'amount_minor' => 100,
            'currency' => 'USD',
            'voided_by_refund_id' => null,
        ], $attributes));
    }

    private function markExistingSchedulesRecognized(): void
    {
        EarningSchedule::query()
            ->whereNull('recognized_at')
            ->update(['recognized_at' => now()]);
    }
}
