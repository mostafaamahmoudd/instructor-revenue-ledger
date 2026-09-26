<?php

namespace App\Models\Relations;

use App\Models\EarningSchedule;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait SubscriptionRefundRelations
{
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function voidedEarnings(): HasMany
    {
        return $this->hasMany(EarningSchedule::class, 'voided_by_refund_id');
    }
}
