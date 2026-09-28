<?php

namespace App\Console\Commands;

use App\Jobs\ExecutePayout;
use App\Models\Payout;
use App\Services\GeneratePayoutBatch;
use Illuminate\Console\Command;

class GeneratePayouts extends Command
{
    protected $signature = 'payouts:run {period_key? : e.g. 2026-09, defaults to the current month}';

    protected $description = 'Generate a payout batch for a period and dispatch execution jobs for each pending payout.';

    public function handle(GeneratePayoutBatch $service): int
    {
        $periodKey = $this->argument('period_key') ?? now()->format('Y-m');

        $this->info("Generating payout batch for period {$periodKey}...");

        $batch = $service->run($periodKey);

        $pending = Payout::query()
            ->where('payout_batch_id', $batch->id)
            ->where('status', Payout::STATUS_PENDING)
            ->get();

        foreach ($pending as $payout) {
            ExecutePayout::dispatch($payout->id);
        }

        $this->info("Batch {$batch->id}: {$pending->count()} payout(s) dispatched for execution.");

        return self::SUCCESS;
    }
}
