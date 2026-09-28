<?php

declare(strict_types=1);

namespace App\Tests\Unit\Planning;

use App\Planning\Domain\Model\Category;
use App\Planning\Domain\Model\Stage;
use App\Planning\Domain\Model\StageStatus;
use App\Shared\Domain\Error\Conflict;
use App\Shared\Domain\Error\InvalidValue;
use PHPUnit\Framework\TestCase;

final class StageTest extends TestCase
{
    public function testALineTotalIsQuantityTimesUnitPriceRoundedHalfUpToTheMinorUnit(): void
    {
        $stage = $this->stage();
        $line = $stage->addLine($this->category(), 'Concreto 3000 PSI', 'm³', '12.5', 3500050);

        self::assertSame(43750625, $line->getTotal(), '12.5 × 35,000.50 = 437,506.25');
        self::assertSame(43750625, $stage->budgetTotal());
    }

    public function testMilestoneWeightsOfAStageCannotGoOverOneHundredPercent(): void
    {
        $stage = $this->stage();
        $stage->addMilestone('Excavación', 6000, null);

        try {
            $stage->addMilestone('Vaciado', 4001, null);
            self::fail('60% + 40.01% must be refused');
        } catch (InvalidValue $e) {
            self::assertArrayHasKey('weight', $e->extra['violations']);
        }
        self::assertSame(6000, $stage->milestoneWeightTotal(), 'the refused milestone is not kept');
    }

    public function testRaisingAWeightPastOneHundredPercentIsRefusedToo(): void
    {
        $stage = $this->stage();
        $stage->addMilestone('Excavación', 6000, null);
        $second = $stage->addMilestone('Vaciado', 4000, null);

        $this->expectException(InvalidValue::class);

        $stage->reweigh($second, 4500);
    }

    public function testProgressIsTheWeightOfTheCompletedMilestones(): void
    {
        $stage = $this->stage();
        $first = $stage->addMilestone('Excavación', 4000, null);
        $stage->addMilestone('Vaciado', 6000, null);

        $first->complete(new \DateTimeImmutable('2026-10-10'), 7, ' Terminada ', new \DateTimeImmutable('2026-10-11'));

        self::assertSame(4000, $stage->progress());
        self::assertSame('Terminada', $first->getCompletionNotes());
    }

    public function testAMilestoneCannotBeCompletedInTheFutureOrTwice(): void
    {
        $milestone = $this->stage()->addMilestone('Excavación', 10000, null);
        $today = new \DateTimeImmutable('2026-10-11');

        try {
            $milestone->complete(new \DateTimeImmutable('2026-10-12'), 7, null, $today);
            self::fail('a future date must be refused');
        } catch (InvalidValue $e) {
            self::assertArrayHasKey('completedAt', $e->extra['violations']);
        }
        $milestone->complete($today, 7, null, $today);

        $this->expectExceptionObject(new Conflict('milestone_already_completed'));
        $milestone->complete($today, 7, null, $today);
    }

    public function testAStageStartsOnceAndNotInTheFuture(): void
    {
        $stage = $this->stage();
        $today = new \DateTimeImmutable('2026-10-11');

        try {
            $stage->start(new \DateTimeImmutable('2026-10-12'), $today);
            self::fail('a future start must be refused');
        } catch (InvalidValue) {
        }
        $stage->start($today, $today);
        self::assertSame(StageStatus::InProgress, $stage->getStatus());

        $this->expectExceptionObject(new Conflict('stage_already_started'));
        $stage->start($today, $today);
    }

    public function testPlannedDatesMustBeInOrder(): void
    {
        $this->expectException(InvalidValue::class);

        $this->stage()->schedule(new \DateTimeImmutable('2026-12-01'), new \DateTimeImmutable('2026-11-01'));
    }

    private function stage(): Stage
    {
        return new Stage(1, 'Cimentación', 0);
    }

    private function category(): Category
    {
        return new Category(1, 'Materiales');
    }
}
