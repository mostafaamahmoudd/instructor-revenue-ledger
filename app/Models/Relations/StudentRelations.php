<?php

namespace App\Models\Relations;

use App\Models\StudentCourseAccess;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait StudentRelations
{
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'student_id');
    }

    public function courseAccess(): HasMany
    {
        return $this->hasMany(StudentCourseAccess::class, 'student_id');
    }
}
