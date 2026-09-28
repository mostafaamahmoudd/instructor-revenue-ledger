<?php

namespace App\Jobs;

use App\Models\Payout;
use App\Services\MockPaymentProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ExecutePayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $payoutId)
    {
    }

    public function handle(MockPaymentProvider $provider): void
    {
        $claimed = DB::transaction(function () {
            $updated = Payout::query()
                ->whereKey($this->payoutId)
                ->whereIn('status', [Payout::STATUS_PENDING, Payout::STATUS_FAILED])
                ->update([
                    'status' => Payout::STATUS_PROCESSING,
                    'attempts' => DB::raw('attempts + 1'),
                    'updated_at' => now(),
                ]);

            if ($updated !== 1) {
                return null;
            }

            return Payout::query()->whereKey($this->payoutId)->first();
        });

        if (!$claimed) {
            return;
        }

        $result = $provider->pay(
            $claimed->provider_idempotency_key,
            $claimed->amount_minor,
            $claimed->currency,
        );

        DB::transaction(function () use ($result) {
            $payout = Payout::query()->whereKey($this->payoutId)->lockForUpdate()->first();

            if (!$payout || $payout->status !== Payout::STATUS_PROCESSING) {
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
                MockPaymentProvider::OUTCOME_TIMED_OUT => $payout->update([
                    'status' => Payout::STATUS_UNCERTAIN,
                    'last_checked_at' => now(),
                ]),
                default => null,
            };
        });

        if ($result['outcome'] === MockPaymentProvider::OUTCOME_TIMED_OUT) {
            ReconcilePayout::dispatch($this->payoutId)->delay(now()->addMinute());
        }
    }
}
