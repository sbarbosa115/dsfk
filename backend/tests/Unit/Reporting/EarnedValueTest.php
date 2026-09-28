<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reporting;

use App\Reporting\Domain\Model\StagePlan;
use App\Reporting\Domain\Service\EarnedValue;
use PHPUnit\Framework\TestCase;

final class EarnedValueTest extends TestCase
{
    private static function day(string $date): \DateTimeImmutable
    {
        return new \DateTimeImmutable($date);
    }

    /**
     * @param list<array{weight: int, plannedDate: ?\DateTimeImmutable, completed: bool}> $milestones
     */
    private static function stage(int $id, int $budget, int $progress, ?string $start, ?string $end, array $milestones = [], string $status = 'IN_PROGRESS', ?string $actualEnd = null): StagePlan
    {
        return new StagePlan($id, "Etapa $id", $status, $budget, $progress, null === $start ? null : self::day($start), null === $end ? null : self::day($end), null, null === $actualEnd ? null : self::day($actualEnd), $milestones);
    }

    public function testThePlannedShareFollowsTheMilestoneDatesWhenEveryMilestoneHasOne(): void
    {
        $stage = self::stage(1, 1000, 0, '2026-10-01', '2026-12-31', [
            ['weight' => 4000, 'plannedDate' => self::day('2026-10-20'), 'completed' => true],
            ['weight' => 6000, 'plannedDate' => self::day('2026-11-30'), 'completed' => false],
        ]);

        self::assertSame(0, EarnedValue::plannedShare($stage, self::day('2026-10-19')));
        self::assertSame(4000, EarnedValue::plannedShare($stage, self::day('2026-10-20')));
        self::assertSame(10000, EarnedValue::plannedShare($stage, self::day('2026-12-01')));
    }

    public function testOtherwiseItIsLinearBetweenThePlannedDates(): void
    {
        $stage = self::stage(1, 1000, 0, '2026-10-01', '2026-10-11', [['weight' => 10000, 'plannedDate' => null, 'completed' => false]]);

        self::assertSame(0, EarnedValue::plannedShare($stage, self::day('2026-09-30')));
        self::assertSame(5000, EarnedValue::plannedShare($stage, self::day('2026-10-06')));
        self::assertSame(10000, EarnedValue::plannedShare($stage, self::day('2026-10-11')));
        self::assertSame(0, EarnedValue::plannedShare(self::stage(2, 1000, 0, null, null), self::day('2026-10-06')));
    }

    public function testTheIndicesCompareWhatWasEarnedWithWhatWasSpentAndPlanned(): void
    {
        // BAC 10,000: stage 1 is half done and on its dates half due; it spent 6,000 of its 4,000 earned.
        $figures = EarnedValue::of([
            self::stage(1, 8000, 5000, '2026-10-01', '2026-10-11'),
            self::stage(2, 2000, 0, '2026-11-01', '2026-11-30', status: 'PENDING'),
        ], [1 => 6000], self::day('2026-10-06'));

        self::assertSame(10000, $figures->budget);
        self::assertSame(4000, $figures->earned);
        self::assertSame(4000, $figures->planned);
        self::assertSame(6000, $figures->spent);
        self::assertSame(0.67, $figures->cpi);
        self::assertSame(1.0, $figures->spi);
        self::assertSame(15000, $figures->forecast, 'BAC / CPI');
        self::assertSame(-5000, $figures->varianceAtCompletion);
        self::assertSame(4000, $figures->plannedProgress);
        self::assertSame(6000, $figures->executed);
        self::assertSame(7500, $figures->stages[0]->executed);
        self::assertNull($figures->stages[1]->cpi, 'nothing spent: no index');
    }

    public function testAStagePastItsPlannedEndIsLate(): void
    {
        $open = self::stage(1, 1000, 0, '2026-10-01', '2026-10-10');
        $done = self::stage(2, 1000, 10000, '2026-10-01', '2026-10-10', status: 'COMPLETED', actualEnd: '2026-10-12');

        self::assertTrue(EarnedValue::isLate($open, self::day('2026-10-11')));
        self::assertFalse(EarnedValue::isLate($open, self::day('2026-10-10')));
        self::assertTrue(EarnedValue::isLate($done, self::day('2026-10-20')), 'finished after its planned end');
    }
}
