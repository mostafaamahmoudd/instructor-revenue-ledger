<?php

namespace App\Models;

use App\Enums\UserType;

abstract class TypedUser extends User
{
    protected $table = 'users';

    protected static function booted(): void
    {
        static::addGlobalScope('type', function ($query) {
            $query->where('type', static::userType()->value);
        });

        static::creating(function (User $model) {
            $model->type ??= static::userType();
        });
    }

    abstract protected static function userType(): UserType;
}
