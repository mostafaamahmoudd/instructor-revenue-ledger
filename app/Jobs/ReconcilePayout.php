<?php

namespace App\Jobs;

use App\Models\Payout;
use App\Services\MockPaymentProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ReconcilePayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MAX_ATTEMPTS_BEFORE_MANUAL_REVIEW = 5;
    private const BACKOFF_MINUTES = [1, 5, 15, 60];

    public function __construct(public int $payoutId)
    {
    }

    public function handle(MockPaymentProvider $provider): void
    {
        $payout = Payout::query()->whereKey($this->payoutId)->lockForUpdate()->first();

        if (!$payout || $payout->status !== Payout::STATUS_UNCERTAIN) {
            return;
        }

        $payout->increment('attempts');

        DB::commit();

        $result = $provider->checkStatus($payout->provider_idempotency_key);

        DB::transaction(function () use ($result) {
            $payout = Payout::query()->whereKey($this->payoutId)->lockForUpdate()->first();

            if (!$payout || $payout->status !== Payout::STATUS_UNCERTAIN) {
                return;
            }

            match ($result['outcome']) {
                MockPaymentProvider::OUTCOME_SUCCEEDED => $payout->update([
                    'status' => Payout::STATUS_SUCCEEDED,
                    'provider_reference' => $result['provider_reference'],
                    'last_checked_at' => now(),
                ]),
                MockPaymentProvider::OUTCOME_FAILED => $payout->update([
                    'status' => Payout::STATUS_FAILED,
                    'last_checked_at' => now(),
                ]),

                default => $payout->update(['last_checked_at' => now()]),
            };
        });

        $payout->refresh();

        if ($payout->status === Payout::STATUS_UNCERTAIN) {
            $nextAttemptIndex = min($payout->attempts, count(self::BACKOFF_MINUTES) - 1);

            if ($payout->attempts >= self::MAX_ATTEMPTS_BEFORE_MANUAL_REVIEW) {
                $payout->update(['requires_manual_review' => true]);
                return;
            }

            self::dispatch($this->payoutId)
                ->delay(now()->addMinutes(self::BACKOFF_MINUTES[$nextAttemptIndex]));
        }
    }
}
