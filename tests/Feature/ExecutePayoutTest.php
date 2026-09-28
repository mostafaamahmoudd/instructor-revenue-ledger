<?php

use App\Jobs\ExecutePayout;
use App\Jobs\ReconcilePayout;
use App\Models\Payout;
use App\Services\MockPaymentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ExecutePayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_successful_pay_marks_the_payout_succeeded(): void
    {
        $this->app->instance(MockPaymentProvider::class, new MockPaymentProvider(successRate: 1.0, permanentFailureRate: 0));

        $payout = Payout::factory()->create(['status' => Payout::STATUS_PENDING, 'attempts' => 0]);

        Bus::dispatchSync(new ExecutePayout($payout->id));

        $payout->refresh();
        $this->assertSame(Payout::STATUS_SUCCEEDED, $payout->status);
        $this->assertSame(1, $payout->attempts);
        $this->assertNotNull($payout->provider_reference);
    }

    public function test_a_permanent_failure_marks_the_payout_failed(): void
    {
        $this->app->instance(MockPaymentProvider::class, new MockPaymentProvider(successRate: 0, permanentFailureRate: 1.0));

        $payout = Payout::factory()->create(['status' => Payout::STATUS_PENDING, 'attempts' => 0]);

        Bus::dispatchSync(new ExecutePayout($payout->id));

        $payout->refresh();
        $this->assertSame(Payout::STATUS_FAILED, $payout->status);
    }

    public function test_a_timeout_marks_the_payout_uncertain_and_queues_reconciliation(): void
    {
        $this->app->instance(MockPaymentProvider::class, new MockPaymentProvider(successRate: 0, permanentFailureRate: 0));
        Queue::fake();

        $payout = Payout::factory()->create(['status' => Payout::STATUS_PENDING, 'attempts' => 0]);

        Bus::dispatchSync(new ExecutePayout($payout->id));

        $payout->refresh();
        $this->assertSame(Payout::STATUS_UNCERTAIN, $payout->status);
        Queue::assertPushed(ReconcilePayout::class);
    }

    public function test_running_execute_payout_twice_on_an_already_succeeded_payout_never_calls_pay_again(): void
    {
        $provider = new MockPaymentProvider(successRate: 1.0, permanentFailureRate: 0);
        $this->app->instance(MockPaymentProvider::class, $provider);

        $payout = Payout::factory()->create(['status' => Payout::STATUS_PENDING, 'attempts' => 0]);

        Bus::dispatchSync(new ExecutePayout($payout->id));
        $payout->refresh();
        $key = $payout->provider_idempotency_key;

        Bus::dispatchSync(new ExecutePayout($payout->id)); // retried job / redelivered message

        $payout->refresh();
        $this->assertSame(1, $payout->attempts); // NOT 2 — proves the second call never re-entered pay()
        $this->assertSame(1, $provider->attemptsFor($key)); // proves the provider itself was hit exactly once
    }

    public function test_reconciling_an_uncertain_payout_resolves_to_succeeded_without_a_second_pay_call(): void
    {
        $provider = new MockPaymentProvider(successRate: 0, permanentFailureRate: 0);
        $this->app->instance(MockPaymentProvider::class, $provider);

        $payout = Payout::factory()->create(['status' => Payout::STATUS_PENDING, 'attempts' => 0]);
        Bus::dispatchSync(new ExecutePayout($payout->id));

        $payout->refresh();
        $this->assertSame(Payout::STATUS_UNCERTAIN, $payout->status);
        $key = $payout->provider_idempotency_key;
        $attemptsAfterExecute = $provider->attemptsFor($key);

        Bus::dispatchSync(new ReconcilePayout($payout->id));

        $payout->refresh();
        $this->assertSame(Payout::STATUS_SUCCEEDED, $payout->status);
        // attempts on the PROVIDER's pay() ledger must be unchanged —
        // reconciliation only ever calls checkStatus(), never pay() again
        $this->assertSame($attemptsAfterExecute, $provider->attemptsFor($key));
    }

    public function test_a_payout_not_in_pending_or_failed_status_is_untouched_by_execute_payout(): void
    {
        $this->app->instance(MockPaymentProvider::class, new MockPaymentProvider(successRate: 1.0, permanentFailureRate: 0));

        $payout = Payout::factory()->create(['status' => Payout::STATUS_PROCESSING, 'attempts' => 1]);

        Bus::dispatchSync(new ExecutePayout($payout->id));

        $payout->refresh();
        $this->assertSame(Payout::STATUS_PROCESSING, $payout->status); // untouched
        $this->assertSame(1, $payout->attempts); // untouched
    }
}
