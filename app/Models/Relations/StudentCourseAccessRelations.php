<?php

namespace App\Models\Relations;

use App\Models\Course;
use App\Models\Student;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait StudentCourseAccessRelations
{
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
