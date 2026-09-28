<?php

use App\Models\EarningSchedule;
use App\Models\PayoutItem;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPaymentAllocation;
use App\Models\SubscriptionRefund;
use App\Services\GeneratePayoutBatch;
use App\Services\ProcessRefund;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_refund_voids_only_future_unrecognized_rows(): void
    {
        [$sub, $past1, $past2, $future1, $future2] = $this->scenario();

        (new ProcessRefund)->run($sub, 1000, 'refund-key-1');

        $this->assertNull($past1->fresh()->voided_at);
        $this->assertNull($past2->fresh()->voided_at);
        $this->assertNotNull($future1->fresh()->voided_at);
        $this->assertNotNull($future2->fresh()->voided_at);
        $this->assertNotNull($future1->fresh()->voided_by_refund_id);
        $this->assertSame(Subscription::STATUS_REFUNDED, $sub->fresh()->status);
    }

    private function scenario(): array
    {
        $subscription = Subscription::factory()->create();
        $payment = SubscriptionPayment::factory()->create(['subscription_id' => $subscription->id]);
        $allocation = SubscriptionPaymentAllocation::factory()->create(['subscription_payment_id' => $payment->id]);

        $mk = fn(string $date, bool $recognized) => EarningSchedule::factory()
            ->state($recognized ? ['recognized_at' => now()] : [])
            ->create([
                'allocation_id' => $allocation->id,
                'instructor_id' => $allocation->instructor_id,
                'earn_date' => $date,
            ]);

        return [
            $subscription,
            $mk(now()->subMonths(2)->toDateString(), true),
            $mk(now()->subMonth()->toDateString(), true),
            $mk(now()->addMonth()->toDateString(), false),
            $mk(now()->addMonths(2)->toDateString(), false),
        ];
    }

    public function test_refund_is_idempotent_on_the_same_key(): void
    {
        [$sub] = $this->scenario();

        $first = (new ProcessRefund)->run($sub, 1000, 'same-key');
        $processedAt = $first->processed_at;
        $second = (new ProcessRefund)->run($sub, 1000, 'same-key');

        $this->assertSame($first->id, $second->id);
        $this->assertEquals($processedAt, $second->fresh()->processed_at);
        $this->assertSame(1, SubscriptionRefund::where('idempotency_key', 'same-key')->count());
    }

    public function test_refund_never_changes_money_columns_or_touches_paid_rows(): void
    {
        [$sub, , , $future] = $this->scenario();
        $amount = $future->amount_minor;

        // a future row already claimed by a payout must not be voided
        $paid = EarningSchedule::factory()->create([
            'allocation_id' => $future->allocation_id,
            'instructor_id' => $future->instructor_id,
            'earn_date' => now()->addMonths(3)->toDateString(),
        ]);
        PayoutItem::factory()->create(['earning_schedule_id' => $paid->id]);

        (new ProcessRefund)->run($sub, 1000, 'k3');

        $this->assertSame($amount, $future->fresh()->amount_minor);
        $this->assertNull($paid->fresh()->voided_at);
    }

    public function test_voided_rows_are_excluded_from_the_next_payout_batch(): void
    {
        [$sub, , , $future] = $this->scenario();
        (new ProcessRefund)->run($sub, 1000, 'k4');

        // even if a voided row somehow got recognized, the payout query excludes it
        $future->fresh()->update(['recognized_at' => now()]);
        $batch = (new GeneratePayoutBatch)->run('2099-01');

        $claimed = PayoutItem::where('earning_schedule_id', $future->id)->exists();
        $this->assertFalse($claimed);
    }
}
