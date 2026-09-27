<?php

namespace Tests\Unit;

use App\Services\MockPaymentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MockPaymentProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_always_succeeding_provider_is_idempotent_and_tracks_attempts(): void
    {
        $provider = new MockPaymentProvider(successRate: 1.0);
        $key = $this->key('succeeded');

        $first = $provider->pay($key, 1000, 'USD');
        $status = $provider->checkStatus($key);
        $second = $provider->pay($key, 1000, 'USD');

        $this->assertSame(MockPaymentProvider::OUTCOME_SUCCEEDED, $first['outcome']);
        $this->assertNotNull($first['provider_reference']);
        $this->assertSame(MockPaymentProvider::OUTCOME_SUCCEEDED, $status['outcome']);
        $this->assertSame($first['provider_reference'], $status['provider_reference']);
        $this->assertSame(MockPaymentProvider::OUTCOME_SUCCEEDED, $second['outcome']);
        $this->assertSame($first['provider_reference'], $second['provider_reference']);
        $this->assertSame(2, $provider->attemptsFor($key));
    }

    public function test_always_failing_provider_records_permanent_failure(): void
    {
        $provider = new MockPaymentProvider(successRate: 0, permanentFailureRate: 1.0);
        $key = $this->key('failed');

        $result = $provider->pay($key, 1000, 'USD');
        $status = $provider->checkStatus($key);

        $this->assertSame(MockPaymentProvider::OUTCOME_FAILED, $result['outcome']);
        $this->assertNull($result['provider_reference']);
        $this->assertSame(MockPaymentProvider::OUTCOME_FAILED, $status['outcome']);
        $this->assertNull($status['provider_reference']);
    }

    public function test_timed_out_payments_are_recorded_as_succeeded_for_later_status_checks(): void
    {
        $provider = new MockPaymentProvider(successRate: 0, permanentFailureRate: 0);
        $key = $this->key('timed-out');

        $result = $provider->pay($key, 1000, 'USD');
        $status = $provider->checkStatus($key);

        $this->assertSame(MockPaymentProvider::OUTCOME_TIMED_OUT, $result['outcome']);
        $this->assertNull($result['provider_reference']);
        $this->assertSame(MockPaymentProvider::OUTCOME_SUCCEEDED, $status['outcome']);
        $this->assertNotNull($status['provider_reference']);
    }

    public function test_check_status_for_never_attempted_key_returns_unknown(): void
    {
        $provider = new MockPaymentProvider();

        $status = $provider->checkStatus($this->key('unknown'));

        $this->assertSame(MockPaymentProvider::OUTCOME_UNKNOWN, $status['outcome']);
        $this->assertNull($status['provider_reference']);
    }

    public function test_default_rates_can_observe_all_raw_pay_outcomes(): void
    {
        $provider = new MockPaymentProvider();
        $outcomes = [];

        for ($i = 0; $i < 200; $i++) {
            $result = $provider->pay($this->key("default-{$i}"), 1000, 'USD');
            $outcomes[$result['outcome']] = true;
        }

        $this->assertArrayHasKey(MockPaymentProvider::OUTCOME_SUCCEEDED, $outcomes);
        $this->assertArrayHasKey(MockPaymentProvider::OUTCOME_FAILED, $outcomes);
        $this->assertArrayHasKey(MockPaymentProvider::OUTCOME_TIMED_OUT, $outcomes);
    }

    private function key(string $prefix): string
    {
        return $prefix . '-' . uniqid('', true);
    }
}
