<?php

namespace App\Services;

use App\Models\EarningSchedule;
use Carbon\CarbonInterface;

class RecognizeEarnings
{
    public function run(?CarbonInterface $asOf = null): int
    {
        $asOf ??= now();

        return EarningSchedule::query()
            ->where('earn_date', '<=', $asOf->toDateString())
            ->whereNull('recognized_at')
            ->whereNull('voided_at')
            ->update(['recognized_at' => now()]);
    }
}
