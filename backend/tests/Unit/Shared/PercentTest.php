<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared;

use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Money\Percent;
use PHPUnit\Framework\TestCase;

final class PercentTest extends TestCase
{
    public function testAPercentBecomesBasisPointsWithoutFloats(): void
    {
        self::assertSame(1250, Percent::toBasisPoints('12.5'));
        self::assertSame(10000, Percent::toBasisPoints('100'));
        self::assertSame(4001, Percent::toBasisPoints('40.01'));
    }

    public function testMoreThanTwoDecimalsIsRefused(): void
    {
        $this->expectException(InvalidValue::class);

        Percent::toBasisPoints('12.345');
    }

    public function testBasisPointsPrintAsAPercentString(): void
    {
        self::assertSame('12.5', Percent::fromBasisPoints(1250));
        self::assertSame('100', Percent::fromBasisPoints(10000));
        self::assertSame('0.01', Percent::fromBasisPoints(1));
    }
}
