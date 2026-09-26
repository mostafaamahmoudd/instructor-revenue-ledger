<?php

namespace App\Models\Relations;

use App\Models\Instructor;
use App\Models\PayoutItem;
use App\Models\SubscriptionPaymentAllocation;
use App\Models\SubscriptionRefund;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait EarningScheduleRelations
{
    public function allocation(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPaymentAllocation::class, 'allocation_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'instructor_id');
    }

    public function voidedByRefund(): BelongsTo
    {
        return $this->belongsTo(SubscriptionRefund::class, 'voided_by_refund_id');
    }

    public function payoutItem(): HasOne
    {
        return $this->hasOne(PayoutItem::class);
    }
}
