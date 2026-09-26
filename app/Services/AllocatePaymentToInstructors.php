<?php

namespace App\Services;

use App\Exceptions\NoEligibleInstructorsException;
use App\Models\EarningSchedule;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPaymentAllocation;
use App\Support\Money;
use App\Support\PlanTerms;

class AllocatePaymentToInstructors
{
    public function run(SubscriptionPayment $payment)
    {
        DB::transaction(function () use ($payment) {
            $locked = SubscriptionPayment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                SubscriptionPaymentAllocation::query()
                    ->where('subscription_payment_id', $locked->id)
                    ->exists()
            ) {
                return;
            }

            $instructorIds = $locked->enrollments()
                ->distinct()
                ->orderBy('instructor_id')
                ->pluck('instructor_id')
                ->all();

            if (empty($instructorIds)) {
                throw NoEligibleInstructorsException::forPayment($locked->id);
            }

            $pool = Money::fromMinorUnits(
                $locked->amount_minor - $locked->platform_cut_minor,
                $locked->currency
            );

            $shares = $pool->allocate(count($instructorIds));

            $subscription = $locked->subscription;
            $termMonths = PlanTerms::monthsFor($subscription->plan);

            foreach ($instructorIds as $index => $instructorId) {
                $share = $shares[$index];

                $allocation = SubscriptionPaymentAllocation::create([
                    'subscription_payment_id' => $locked->id,
                    'instructor_id' => $instructorId,
                    'amount_minor' => $share->minorUnits,
                    'currency' => $share->currency,
                    'created_at' => now(),
                ]);

                $this->spreadAcrossTerm($allocation, $subscription, $share, $termMonths);
            }
        });
    }

    private function spreadAcrossTerm(SubscriptionPaymentAllocation $allocation, $subscription, Money $share, int $termMonths): void
    {
        $monthlyShares = $share->allocate($termMonths);

        $rows = [];
        foreach ($monthlyShares as $monthShare) {
            $rows[] = [
                'allocation_id' => $allocation->id,
                'instructor_id' => $allocation->instructor_id,
                'earn_date' => $subscription->start_date->copy()->addMonthsNoOverflow($i)->toDateString(),
                'amount_minor' => $monthShare->minorUnits,
                'currency' => $monthShare->currency,
                'recognized_at' => null,
                'voided_at' => null,
                'voided_by_refund_id' => null,
                'created_at' => now(),
            ];
        }

        EarningSchedule::query()->insert($rows);
    }
}
