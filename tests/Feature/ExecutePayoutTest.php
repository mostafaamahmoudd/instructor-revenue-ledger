<?php

use App\Jobs\ExecutePayout;
use App\Jobs\ReconcilePayout;
use App\Models\Payout;
use App\Services\MockPaymentProvider;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ExecutePayoutTest extends TestCase
{
    use DatabaseMigrations;

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
        Queue::fake([ReconcilePayout::class]);

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

    public function test_duplicate_workers_with_stale_views_cannot_both_call_pay(): void
    {
        $provider = new MockPaymentProvider(successRate: 1.0, permanentFailureRate: 0);
        $this->app->instance(MockPaymentProvider::class, $provider);

        $payout = Payout::factory()->create(['status' => Payout::STATUS_PENDING, 'attempts' => 0]);
        $firstStaleView = Payout::findOrFail($payout->id);
        $secondStaleView = Payout::findOrFail($payout->id);
        $key = $payout->provider_idempotency_key;

        DB::beginTransaction();
        Payout::query()->whereKey($payout->id)->lockForUpdate()->first();

        $processes = [];
        foreach ([$firstStaleView, $secondStaleView] as $stalePayout) {
            $process = $this->executePayoutProcess($stalePayout->id);
            $process->start();
            $processes[] = $process;
        }

        usleep(500000);
        DB::commit();

        foreach ($processes as $process) {
            $process->wait();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput() ?: $process->getOutput());
        }

        $this->assertSame(Payout::STATUS_SUCCEEDED, Payout::findOrFail($payout->id)->status);
        $this->assertSame(1, $provider->attemptsFor($key));
    }

    private function executePayoutProcess(int $payoutId): Process
    {
        $autoload = var_export(base_path('vendor/autoload.php'), true);
        $bootstrap = var_export(base_path('bootstrap/app.php'), true);

        $code = <<<PHP
require {$autoload};
\$app = require {$bootstrap};
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
\$app->instance(App\Services\MockPaymentProvider::class, new App\Services\MockPaymentProvider(successRate: 1.0, permanentFailureRate: 0));
Illuminate\Support\Facades\Bus::dispatchSync(new App\Jobs\ExecutePayout({$payoutId}));
PHP;

        return new Process([PHP_BINARY, '-r', $code], base_path(), [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'instructor_revenue_ledger_test',
            'QUEUE_CONNECTION' => 'sync',
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
        ], null, 10);
    }

    public function test_reconciling_an_uncertain_payout_resolves_to_succeeded_without_a_second_pay_call(): void
    {
        $provider = new MockPaymentProvider(successRate: 0, permanentFailureRate: 0);
        $this->app->instance(MockPaymentProvider::class, $provider);
        Queue::fake([ReconcilePayout::class]);

        $payout = Payout::factory()->create(['status' => Payout::STATUS_PENDING, 'attempts' => 0]);
        Bus::dispatchSync(new ExecutePayout($payout->id));

        $payout->refresh();
        $this->assertSame(Payout::STATUS_UNCERTAIN, $payout->status);
        $key = $payout->provider_idempotency_key;
        $attemptsAfterExecute = $provider->attemptsFor($key);

        (new ReconcilePayout($payout->id))->handle($provider);

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

    public function test_stuck_processing_payout_is_reconciled_never_repaid(): void
    {
        $provider = new MockPaymentProvider(successRate: 0, permanentFailureRate: 0);
        $this->app->instance(MockPaymentProvider::class, $provider);

        $payout = Payout::factory()->create(['status' => Payout::STATUS_PROCESSING, 'attempts' => 1]);
        Payout::whereKey($payout->id)->update(['updated_at' => now()->subMinutes(30)]);

        // provider actually received it before the worker died
        $provider->pay($payout->provider_idempotency_key, $payout->amount_minor, $payout->currency);

        $this->artisan('payouts:sweep-stuck')->assertSuccessful();

        $payout->refresh();
        $this->assertSame(Payout::STATUS_SUCCEEDED, $payout->status);
        $this->assertSame(1, $provider->attemptsFor($payout->provider_idempotency_key)); // pay() not repeated
    }

    public function test_repeated_unknown_reconciliation_marks_payout_for_manual_review_without_pay(): void
    {
        $provider = new MockPaymentProvider(successRate: 1.0, permanentFailureRate: 0);
        $this->app->instance(MockPaymentProvider::class, $provider);
        Queue::fake([ReconcilePayout::class]);

        $payout = Payout::factory()->create([
            'status' => Payout::STATUS_UNCERTAIN,
            'attempts' => 0,
            'provider_reference' => null,
        ]);

        for ($i = 0; $i < 5; $i++) {
            (new ReconcilePayout($payout->id))->handle($provider);
        }

        $payout->refresh();
        $this->assertSame(Payout::STATUS_UNCERTAIN, $payout->status);
        $this->assertTrue($payout->requires_manual_review);
        $this->assertSame(0, $provider->attemptsFor($payout->provider_idempotency_key));
    }
}
