<?php

namespace App\Models\Relations;

use App\Models\Payout;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait PayoutBatchRelations
{
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }
}
