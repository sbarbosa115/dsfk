<?php

namespace App\Tests\Unit;

use App\Service\MoneyConverter;
use PHPUnit\Framework\TestCase;

class MoneyConverterTest extends TestCase
{
    public function testConvertsBetweenMajorAndMinorUnits(): void
    {
        self::assertSame(150000000, MoneyConverter::toMinor('1500000', 'COP'));
        self::assertSame(150000050, MoneyConverter::toMinor('1500000.5', 'COP'));
        self::assertSame('1500000.50', MoneyConverter::toMajor(150000050, 'COP'));
    }

    public function testRejectsMoreDecimalsThanTheCurrencyAllows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MoneyConverter::toMinor('10.555', 'COP');
    }

    public function testMultiplyRoundsHalfUpToMinorUnits(): void
    {
        // 12.5 × 35,000.50 = 437,506.25
        self::assertSame(43750625, MoneyConverter::multiply(3500050, '12.5'));
        // 0.333 × 0.01 = 0.00333 → 0 ; 0.5 × 0.01 = 0.005 → 0.01
        self::assertSame(0, MoneyConverter::multiply(1, '0.333'));
        self::assertSame(1, MoneyConverter::multiply(1, '0.5'));
    }
}
