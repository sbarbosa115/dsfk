<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared;

use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Money\MinorUnits;
use PHPUnit\Framework\TestCase;

final class MinorUnitsTest extends TestCase
{
    public function testMajorAmountsBecomeMinorUnitsOfTheCurrency(): void
    {
        self::assertSame(150000000, MinorUnits::fromMajor('1500000', 'COP'), 'COP has two minor digits in ISO 4217');
        self::assertSame(1250, MinorUnits::fromMajor('12.50', 'USD'));
        self::assertSame('1500000.00', MinorUnits::toMajor(150000000, 'COP'));
    }

    public function testAnAmountWithTooManyDecimalsIsRefusedInsteadOfRounded(): void
    {
        $this->expectException(InvalidValue::class);

        MinorUnits::fromMajor('10.001', 'COP');
    }

    public function testLineTotalsRoundHalfUpToTheMinorUnit(): void
    {
        self::assertSame(334, MinorUnits::multiply(100, '3.335'), '333.5 minor units rounds up');
        self::assertSame(333, MinorUnits::multiply(100, '3.334'));
        self::assertSame(0, MinorUnits::multiply(0, '12.5'));
    }

    public function testBasisPointsAreAShareOfTheWholeAndZeroWhenThereIsNoWhole(): void
    {
        self::assertSame(3333, MinorUnits::basisPoints(1, 3));
        self::assertSame(10000, MinorUnits::basisPoints(5, 5));
        self::assertSame(0, MinorUnits::basisPoints(5, 0));
    }

    public function testEmailFormatUsesColombianSeparators(): void
    {
        self::assertSame('$ 1.500.000', MinorUnits::format(150000000, 'COP'));
        self::assertSame('$ -2.500', MinorUnits::format(-250000, 'COP'));
        self::assertSame('USD 1.234,50', MinorUnits::format(123450, 'USD'));
        self::assertSame('$ 0', MinorUnits::format(0, 'COP'));
    }
}
