<?php

namespace App\Models\Relations;

use App\Models\EarningSchedule;
use App\Models\Instructor;
use App\Models\SubscriptionPayment;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait SubscriptionPaymentAllocationRelations
{
    public function payment(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPayment::class, 'subscription_payment_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'instructor_id');
    }

    public function earningSchedules(): HasMany
    {
        return $this->hasMany(EarningSchedule::class, 'allocation_id');
    }
}
