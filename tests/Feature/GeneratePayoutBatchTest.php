<?php

use App\Models\EarningSchedule;
use App\Models\Instructor;
use App\Models\Payout;
use App\Services\GeneratePayoutBatch;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneratePayoutBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sums_recognized_unpaid_earnings_into_one_payout(): void
    {
        $instructor = Instructor::factory()->create();
        EarningSchedule::factory()->recognized()->create(['instructor_id' => $instructor->id, 'currency' => 'USD', 'amount_minor' => 500]);
        EarningSchedule::factory()->recognized()->create(['instructor_id' => $instructor->id, 'currency' => 'USD', 'amount_minor' => 300]);

        $batch = (new GeneratePayoutBatch)->run('2026-09');
        $payout = Payout::where('payout_batch_id', $batch->id)->where('instructor_id', $instructor->id)->first();

        $this->assertNotNull($payout);
        $this->assertSame(800, $payout->amount_minor);
    }

    public function test_running_the_batch_twice_never_creates_a_second_payout_for_the_same_instructor_period_currency(): void
    {
        $instructor = Instructor::factory()->create();
        EarningSchedule::factory()->recognized()->create(['instructor_id' => $instructor->id, 'currency' => 'USD', 'amount_minor' => 500]);

        (new GeneratePayoutBatch)->run('2026-09');
        (new GeneratePayoutBatch)->run('2026-09');

        $this->assertSame(1, Payout::where('instructor_id', $instructor->id)->count());
    }

    public function test_the_database_constraint_itself_rejects_a_duplicate_payout(): void
    {
        $instructor = Instructor::factory()->create();
        Payout::factory()->create(['instructor_id' => $instructor->id, 'period_key' => '2026-09', 'currency' => 'USD']);

        $this->expectException(QueryException::class);
        Payout::factory()->create(['instructor_id' => $instructor->id, 'period_key' => '2026-09', 'currency' => 'USD']);
    }

    public function test_an_unrecognized_earning_is_excluded(): void
    {
        $instructor = Instructor::factory()->create();
        EarningSchedule::factory()->create(['instructor_id' => $instructor->id, 'currency' => 'USD', 'amount_minor' => 500]); // not recognized

        $batch = (new GeneratePayoutBatch)->run('2026-09');

        $this->assertNull(Payout::where('payout_batch_id', $batch->id)->where('instructor_id', $instructor->id)->first());
    }

    public function test_a_voided_earning_is_excluded_even_if_recognized(): void
    {
        $instructor = Instructor::factory()->create();
        EarningSchedule::factory()->recognized()->voided()->create(['instructor_id' => $instructor->id, 'currency' => 'USD', 'amount_minor' => 500]);

        $batch = (new GeneratePayoutBatch)->run('2026-09');

        $this->assertNull(Payout::where('payout_batch_id', $batch->id)->where('instructor_id', $instructor->id)->first());
    }

    public function test_an_earning_already_claimed_by_a_prior_payout_is_excluded_from_a_new_batch(): void
    {
        $instructor = Instructor::factory()->create();
        $earning = EarningSchedule::factory()->recognized()->create(['instructor_id' => $instructor->id, 'currency' => 'USD', 'amount_minor' => 500]);

        (new GeneratePayoutBatch)->run('2026-09');

        // simulate a second period's generation — this earning was already claimed
        $secondBatch = (new GeneratePayoutBatch)->run('2026-10');

        $this->assertNull(Payout::where('payout_batch_id', $secondBatch->id)->where('instructor_id', $instructor->id)->first());
    }
}
