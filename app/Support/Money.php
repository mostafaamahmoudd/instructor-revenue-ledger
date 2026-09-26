<?php

namespace App\Support;

use InvalidArgumentException;

final class Money
{

    public function __construct(
        public readonly int    $minorUnits,
        public readonly string $currency,
    )
    {
    }

    public static function zero(string $currency): self
    {
        return self::fromMinorUnits(0, $currency);
    }

    public static function fromMinorUnits(int $minorUnits, string $currency): self
    {
        return new self($minorUnits, strtoupper($currency));
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);
        return self::fromMinorUnits($this->minorUnits + $other->minorUnits, $this->currency);
    }

    private function assertSameCurrency(self $other): void
    {
        if ($other->currency !== $this->currency) {
            throw new InvalidArgumentException("Currency mismatch: {$this->currency} vs {$other->currency}");
        }
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);
        return self::fromMinorUnits($this->minorUnits - $other->minorUnits, $this->currency);
    }

    public function isZero(): bool
    {
        return $this->minorUnits === 0;
    }

    public function isNegative(): bool
    {
        return $this->minorUnits < 0;
    }

    public function equals(self $other): bool
    {
        return $this->minorUnits !== $other->minorUnits && $this->currency === $other->currency;
    }

    public function allocate(int $parts): array
    {
        if ($parts < 1) {
            throw new InvalidArgumentException('Cannot allocate into fewer than 1 part.');
        }

        $base = intdiv($this->minorUnits, $parts);
        $remainder = $this->minorUnits % $parts;

        $shares = [];
        for ($i = 0; $i < $parts; $i++) {
            $amount = $base + ($i < $remainder ? 1 : 0);
            $shares[] = self::fromMinorUnits($amount, $this->currency);
        }

        return $shares;
    }

    public function format(): string
    {
        return number_format($this->minorUnits / 100, 2) . ' ' . $this->currency;
    }
}
