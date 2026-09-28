<?php

namespace App\Services;

use App\Models\EarningSchedule;
use App\Models\Subscription;
use App\Models\SubscriptionRefund;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class ProcessRefund
{
    /**
     * Rule: void only earning rows that are unrecognized, unvoided, unpaid, and whose
     * earn_date is STRICTLY AFTER the current month. Recognized rows stay owed; paid rows
     * are never touched. Money columns are never updated, only voided_at/voided_by_refund_id.
     */
    public function run(Subscription $subscription, int $amountMinor, string $idempotencyKey): SubscriptionRefund
    {
        return DB::transaction(function () use ($subscription, $amountMinor, $idempotencyKey) {
            try {
                $refund = SubscriptionRefund::create([
                    'subscription_id' => $subscription->id,
                    'amount_minor' => $amountMinor,
                    'idempotency_key' => $idempotencyKey,
                    'status' => SubscriptionRefund::STATUS_PENDING,
                    'requested_at' => now(),
                    'created_at' => now(),
                ]);
            } catch (UniqueConstraintViolationException) {
                return SubscriptionRefund::where('idempotency_key', $idempotencyKey)->firstOrFail();
            }

            Subscription::query()->whereKey($subscription->id)->lockForUpdate()->first();

            $cutoff = now()->endOfMonth()->toDateString();

            EarningSchedule::query()
                ->whereIn('allocation_id', function ($q) use ($subscription) {
                    $q->select('a.id')
                        ->from('subscription_payment_allocations as a')
                        ->join('subscription_payments as p', 'p.id', '=', 'a.subscription_payment_id')
                        ->where('p.subscription_id', $subscription->id);
                })
                ->where('earn_date', '>', $cutoff)
                ->whereNull('recognized_at')
                ->whereNull('voided_at')
                ->unpaid()
                ->update([
                    'voided_at' => now(),
                    'voided_by_refund_id' => $refund->id,
                ]);

            $subscription->update([
                'status' => Subscription::STATUS_REFUNDED,
                'refunded_at' => now(),
            ]);

            $refund->update([
                'status' => SubscriptionRefund::STATUS_COMPLETED,
                'processed_at' => now(),
            ]);

            return $refund->fresh();
        });
    }
}
