<?php

namespace App\Rules;

use App\Enums\UserType;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class UserOfType implements ValidationRule
{
    public function __construct(private UserType $type)
    {
    }

    public static function make(UserType $type): self
    {
        return new self($type);
    }

    /**
     * Run the validation rule.
     *
     * @param Closure(string, ?string=): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = User::query()->where('id', $value)->where('type', $this->type->value)->exists();

        if (!$exists) {
            $fail("The selected {$attribute} must be a user of type {$this->type->value}.");
        }
    }
}
