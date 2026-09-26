<?php

namespace App\Models\Relations;

use App\Models\Instructor;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait CourseRelations
{
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'instructor_id');
    }
}
