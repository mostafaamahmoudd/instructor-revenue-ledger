<?php

namespace App\Models\Relations;

use App\Models\Student;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionRefund;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait SubscriptionRelations
{
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(SubscriptionRefund::class);
    }
}
