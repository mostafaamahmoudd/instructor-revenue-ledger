<?php

namespace App\Models;

use App\Enums\UserType;
use App\Models\Relations\InstructorRelations;

class Instructor extends TypedUser
{
    use InstructorRelations;

    protected static function userType(): UserType
    {
        return UserType::Instructor;
    }
}
