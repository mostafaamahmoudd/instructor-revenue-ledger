<?php

namespace App\Models\Relations;

use App\Models\EarningSchedule;
use App\Models\Payout;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait PayoutItemRelations
{
    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }

    public function earningSchedule(): BelongsTo
    {
        return $this->belongsTo(EarningSchedule::class);
    }
}
