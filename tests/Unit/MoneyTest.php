<?php

namespace Tests\Unit;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_even_split_with_no_remainder_produces_equal_shares(): void
    {
        $shares = Money::fromMinorUnits(100, 'USD')->allocate(4);

        $this->assertSame([25, 25, 25, 25], array_map(
            fn (Money $share) => $share->minorUnits,
            $shares
        ));
    }

    public function test_uneven_split_remainder_goes_to_earlier_shares(): void
    {
        $shares = Money::fromMinorUnits(10, 'USD')->allocate(3);

        $this->assertSame([4, 3, 3], array_map(
            fn (Money $share) => $share->minorUnits,
            $shares
        ));
    }

    public function test_allocated_shares_always_sum_to_original_amount(): void
    {
        $amounts = [0, 1, 99, 100, 12345, 999999];
        $partCounts = [1, 2, 3, 5, 7, 13, 50];

        foreach ($amounts as $amount) {
            foreach ($partCounts as $partCount) {
                $shares = Money::fromMinorUnits($amount, 'USD')->allocate($partCount);

                $this->assertCount($partCount, $shares);
                $this->assertSame($amount, array_sum(array_map(
                    fn (Money $share) => $share->minorUnits,
                    $shares
                )));
            }
        }
    }

    public function test_splitting_into_fewer_than_one_part_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromMinorUnits(100, 'USD')->allocate(0);
    }

    public function test_arithmetic_across_mismatched_currencies_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromMinorUnits(100, 'USD')->add(Money::fromMinorUnits(100, 'EUR'));
    }

    public function test_equals_distinguishes_amount_and_currency(): void
    {
        $money = Money::fromMinorUnits(100, 'USD');

        $this->assertTrue($money->equals(Money::fromMinorUnits(100, 'USD')));
        $this->assertFalse($money->equals(Money::fromMinorUnits(101, 'USD')));
        $this->assertFalse($money->equals(Money::fromMinorUnits(100, 'EUR')));
    }
}
