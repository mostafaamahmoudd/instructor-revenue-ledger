<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MockPaymentProvider
{
    public const OUTCOME_SUCCEEDED = 'succeeded';
    public const OUTCOME_FAILED = 'failed';
    public const OUTCOME_TIMED_OUT = 'timed_out';
    public const OUTCOME_UNKNOWN = 'unknown';

    public function __construct(
        private readonly float $successRate = 0.6,
        private readonly float $permanentFailureRate = 0.2,
    )
    {
    }

    public function pay(string $idempotencyKey, int $amountMinor, string $currency): array
    {
        $existing = DB::table('mock_provider_ledger')
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            DB::table('mock_provider_ledger')
                ->where('idempotency_key', $idempotencyKey)
                ->increment('attempts');

            return $existing->outcome === self::OUTCOME_SUCCEEDED
                ? ['outcome' => self::OUTCOME_SUCCEEDED, 'provider_reference' => $existing->provider_reference]
                : ['outcome' => self::OUTCOME_FAILED, 'provider_reference' => null];
        }

        $roll = mt_rand() / mt_getrandmax();

        if ($roll < $this->successRate) {
            $reference = 'mock_' . Str::uuid();
            $this->recordOutcome($idempotencyKey, self::OUTCOME_SUCCEEDED, $reference);
            return ['outcome' => self::OUTCOME_SUCCEEDED, 'provider_reference' => $reference];
        }

        if ($roll < $this->successRate + $this->permanentFailureRate) {
            $this->recordOutcome($idempotencyKey, self::OUTCOME_FAILED, null);
            return ['outcome' => self::OUTCOME_FAILED, 'provider_reference' => null];
        }

        $reference = 'mock_' . Str::uuid();
        $this->recordOutcome($idempotencyKey, self::OUTCOME_SUCCEEDED, $reference);
        return ['outcome' => self::OUTCOME_TIMED_OUT, 'provider_reference' => null];
    }

    private function recordOutcome(string $idempotencyKey, string $outcome, ?string $reference): void
    {
        DB::table('mock_provider_ledger')->insert([
            'idempotency_key' => $idempotencyKey,
            'outcome' => $outcome,
            'provider_reference' => $reference,
            'attempts' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function checkStatus(string $idempotencyKey): array
    {
        $row = DB::table('mock_provider_ledger')
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if (!$row) {
            return ['outcome' => self::OUTCOME_UNKNOWN, 'provider_reference' => null];
        }

        return $row->outcome === self::OUTCOME_SUCCEEDED
            ? ['outcome' => self::OUTCOME_SUCCEEDED, 'provider_reference' => $row->provider_reference]
            : ['outcome' => self::OUTCOME_FAILED, 'provider_reference' => null];
    }

    public function attemptsFor(string $idempotencyKey): int
    {
        return (int)DB::table('mock_provider_ledger')
            ->where('idempotency_key', $idempotencyKey)
            ->value('attempts');
    }
}
