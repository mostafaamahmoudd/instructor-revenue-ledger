<?php

namespace App\Exceptions;

use RuntimeException;

class NoEligibleInstructorsException extends RuntimeException
{
    public static function forPayment(int $paymentId): self
    {
        return new self("SubscriptionPayment {$paymentId} has no enrolled instructors to allocate to.");
    }
}
