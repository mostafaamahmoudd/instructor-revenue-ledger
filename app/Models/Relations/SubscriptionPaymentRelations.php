<?php

namespace App\Models\Relations;

use App\Models\Subscription;
use App\Models\SubscriptionEnrollment;
use App\Models\SubscriptionPaymentAllocation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait SubscriptionPaymentRelations
{
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(SubscriptionEnrollment::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SubscriptionPaymentAllocation::class);
    }
}
