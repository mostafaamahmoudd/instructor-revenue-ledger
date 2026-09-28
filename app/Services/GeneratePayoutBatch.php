<?php

namespace App\Services;

use App\Models\EarningSchedule;
use App\Models\Payout;
use App\Models\PayoutBatch;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class GeneratePayoutBatch
{
    public function run(string $periodKey): PayoutBatch
    {
        $batch = PayoutBatch::create([
            'period_key' => $periodKey,
            'triggered_at' => now(),
            'status' => PayoutBatch::STATUS_RUNNING,
            'created_at' => now(),
        ]);

        $group = EarningSchedule::query()
            ->recognized()
            ->notVoided()
            ->unpaid()
            ->select('instructor_id', 'currency')
            ->distinct()
            ->get();

        foreach ($group as $group) {
            $this->generateForInstructor($batch, $group->instructor_id, $group->currency, $periodKey);
        }

        $batch->update(['status' => PayoutBatch::STATUS_COMPLETED]);

        return $batch->fresh();
    }

    private function generateForInstructor(PayoutBatch $batch, int $instructorId, string $currency, string $periodKey): void
    {
        DB::transaction(function () use ($batch, $instructorId, $currency, $periodKey) {
            $candidates = EarningSchedule::query()
                ->where('instructor_id', $instructorId)
                ->where('currency', $currency)
                ->recognized()
                ->notVoided()
                ->unpaid()
                ->lockForUpdate()
                ->get();

            if ($candidates->isEmpty()) {
                return;
            }

            $amountMinor = $candidates->sum('amount_minor');

            try {
                $payout = Payout::create([
                    'payout_batch_id' => $batch->id,
                    'instructor_id' => $instructorId,
                    'instructor_type' => 'instructor',
                    'period_key' => $periodKey,
                    'amount_minor' => $amountMinor,
                    'currency' => $currency,
                    'status' => Payout::STATUS_PENDING,
                    'provider_idempotency_key' => (string)Str::uuid(),
                    'attempts' => 0,
                ]);
            } catch (QueryException $e) {
                DB::rollBack();
                if ($e->getCode() !== 23000) {
                    throw $e;
                }

                return;
            }

            $items = $candidates->map(fn($earning) => [
                'payout_id' => $payout->id,
                'earning_schedule_id' => $earning->id,
                'amount_minor' => $earning->amount_minor,
            ])->all();

            DB::table('payout_items')->insert($items);
        });
    }
}
