<?php

namespace App\Models;

use App\Enums\UserType;
use App\Models\Relations\InstructorRelations;
use App\Models\Relations\StudentRelations;

class Student extends TypedUser
{
    use StudentRelations;

    protected static function userType(): UserType
    {
        return UserType::Student;
    }
}
