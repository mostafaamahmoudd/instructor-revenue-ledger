<?php

namespace App\Models;

use App\Models\Relations\StudentCourseAccessRelations;
use Database\Factories\StudentCourseAccessFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentCourseAccess extends Model
{
    /** @use HasFactory<StudentCourseAccessFactory> */
    use HasFactory;
    use StudentCourseAccessRelations;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['student_id', 'course_id', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
