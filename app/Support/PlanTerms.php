<?php

namespace App\Support;

use InvalidArgumentException;

final class PlanTerms
{
    private const MONTHS = [
        'monthly' => 1,
        'quarterly' => 3,
        'annual' => 12,
    ];

    public static function monthsFor(string $plan): int
    {
        return self::MONTHS[$plan]
            ?? throw new InvalidArgumentException("Unknown plan: {$plan}");
    }
}
