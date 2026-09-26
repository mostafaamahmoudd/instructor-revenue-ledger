<?php

namespace App\Models\Relations;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\SubscriptionPayment;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait SubscriptionEnrollmentRelations
{
    public function payment(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPayment::class, 'subscription_payment_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'instructor_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
