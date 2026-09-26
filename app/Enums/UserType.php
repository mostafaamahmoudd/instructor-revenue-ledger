<?php

namespace App\Enums;

enum UserType: string
{
    case Student = 'student';
    case Instructor = 'instructor';
    case Admin = 'admin';
}
