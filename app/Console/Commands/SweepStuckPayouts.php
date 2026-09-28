<?php

namespace App\Console\Commands;

use App\Jobs\ReconcilePayout;
use App\Models\Payout;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SweepStuckPayouts extends Command
{
    protected $signature = 'payouts:sweep-stuck {--minutes=5 : age threshold for a processing payout}';

    protected $description = 'Move stale processing payouts to uncertain and reconcile via checkStatus (never pay again).';

    public function handle(): int
    {
        $cutoff = now()->subMinutes((int)$this->option('minutes'));
        $ids = [];

        Payout::query()
            ->where('status', Payout::STATUS_PROCESSING)
            ->where('updated_at', '<', $cutoff)
            ->pluck('id')
            ->each(function ($id) use (&$ids, $cutoff) {
                DB::transaction(function () use ($id, $cutoff, &$ids) {
                    $payout = Payout::query()->whereKey($id)->lockForUpdate()->first();

                    if ($payout && $payout->status === Payout::STATUS_PROCESSING && $payout->updated_at < $cutoff) {
                        $payout->update(['status' => Payout::STATUS_UNCERTAIN, 'last_checked_at' => now()]);
                        $ids[] = $payout->id;
                    }
                });
            });

        foreach ($ids as $id) {
            ReconcilePayout::dispatch($id);
        }

        $this->info(count($ids) . ' stuck payout(s) moved to uncertain and queued for reconciliation.');

        return self::SUCCESS;
    }
}
