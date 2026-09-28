<?php

namespace App\Services;

use App\Models\EarningSchedule;
use App\Models\Instructor;
use App\Models\Payout;

class InstructorBalanceSummary
{
    public function forInstructor(Instructor $instructor): array
    {
        $recognized = $this->recognizedByCurrency($instructor);
        $paid = $this->claimedByCurrency($instructor, [Payout::STATUS_SUCCEEDED]);
        $reserved = $this->claimedByCurrency($instructor, [
            Payout::STATUS_PENDING,
            Payout::STATUS_PROCESSING,
            Payout::STATUS_FAILED,
            Payout::STATUS_UNCERTAIN,
        ]);
        $available = $this->availableByCurrency($instructor);

        $currencies = array_unique([
            ...array_keys($recognized),
            ...array_keys($paid),
            ...array_keys($reserved),
            ...array_keys($available),
        ]);
        sort($currencies);

        $summary = [];
        foreach ($currencies as $currency) {
            $recognizedMinor = $recognized[$currency] ?? 0;
            $paidMinor = $paid[$currency] ?? 0;
            $availableMinor = $available[$currency] ?? 0;
            $reservedMinor = $reserved[$currency] ?? 0;

            $summary[$currency] = [
                'recognized_minor' => $recognizedMinor,
                'paid_minor' => $paidMinor,
                'available_minor' => $availableMinor,
                'reserved_minor' => $reservedMinor,
                'outstanding_minor' => $recognizedMinor - $paidMinor,
            ];
        }

        return $summary;
    }

    public function formatMinor(int $amountMinor, string $currency): string
    {
        $sign = $amountMinor < 0 ? '-' : '';
        $absolute = abs($amountMinor);

        return $sign . number_format(intdiv($absolute, 100)) . '.' . str_pad((string)($absolute % 100), 2, '0', STR_PAD_LEFT) . ' ' . strtoupper($currency);
    }

    public function formatLines(array $summary, string $key): string
    {
        return implode("\n", $this->formatList($summary, $key));
    }

    public function formatList(array $summary, string $key): array
    {
        if ($summary === []) {
            return ['0.00'];
        }

        return collect($summary)
            ->map(fn(array $totals, string $currency): string => $this->formatMinor($totals[$key], $currency))
            ->values()
            ->all();
    }

    private function recognizedByCurrency(Instructor $instructor): array
    {
        return EarningSchedule::query()
            ->where('instructor_id', $instructor->id)
            ->recognized()
            ->notVoided()
            ->selectRaw('currency, COALESCE(SUM(amount_minor), 0) as total_minor')
            ->groupBy('currency')
            ->pluck('total_minor', 'currency')
            ->map(fn($value): int => (int)$value)
            ->all();
    }

    private function availableByCurrency(Instructor $instructor): array
    {
        return EarningSchedule::query()
            ->where('instructor_id', $instructor->id)
            ->recognized()
            ->notVoided()
            ->unpaid()
            ->selectRaw('currency, COALESCE(SUM(amount_minor), 0) as total_minor')
            ->groupBy('currency')
            ->pluck('total_minor', 'currency')
            ->map(fn($value): int => (int)$value)
            ->all();
    }

    private function claimedByCurrency(Instructor $instructor, array $statuses): array
    {
        return EarningSchedule::query()
            ->join('payout_items', 'payout_items.earning_schedule_id', '=', 'earning_schedules.id')
            ->join('payouts', 'payouts.id', '=', 'payout_items.payout_id')
            ->where('earning_schedules.instructor_id', $instructor->id)
            ->whereNotNull('earning_schedules.recognized_at')
            ->whereNull('earning_schedules.voided_at')
            ->whereIn('payouts.status', $statuses)
            ->selectRaw('earning_schedules.currency, COALESCE(SUM(payout_items.amount_minor), 0) as total_minor')
            ->groupBy('earning_schedules.currency')
            ->pluck('total_minor', 'currency')
            ->map(fn($value): int => (int)$value)
            ->all();
    }
}
