<?php

namespace App\Models\Relations;

use App\Models\Course;
use App\Models\EarningSchedule;
use App\Models\Payout;
use App\Models\SubscriptionPaymentAllocation;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait InstructorRelations
{
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SubscriptionPaymentAllocation::class, 'instructor_id');
    }

    public function earningSchedules(): HasMany
    {
        return $this->hasMany(EarningSchedule::class, 'instructor_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'instructor_id');
    }
}
